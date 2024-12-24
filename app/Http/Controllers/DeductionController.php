<?php

namespace App\Http\Controllers;

use App\Models\Deduction;
use App\Models\DeductionDetail;
use App\Models\User;
use App\Models\FortnightDates;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class DeductionController extends Controller
{
    public function index()
    {
        $deductions = Deduction::with('user')->latest()->get();

        return view('admin.deductions.index', compact('deductions'));
    }

    public function getDeductionsData(Request $request)
    {
        $deductions = Deduction::with('user');

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $deductions->where(function($query) use ($searchValue) {
                $query->where('type', 'like', '%' . $searchValue . '%')
                    ->orWhere('amount', 'like', '%' . $searchValue . '%')
                    ->orWhere('no_of_payroll', 'like', '%' . $searchValue . '%')
                    ->orWhere('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function($subQuery) use ($searchValue) {
                        $subQuery->where('user_code', 'like', '%' . $searchValue . '%')
                                ->orWhere('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = Deduction::count();

        $filteredRecords = $deductions->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $deductions = $deductions->skip($start)->take($length)->get();

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $deductions,
        ];

        return response()->json($data);
    }

    public function create()
    {
        $userRole = Role::where('name', 'Security Guard')->first();

        $securityGuards = User::with('guardAdditionalInformation')->whereHas('roles', function ($query) use ($userRole) {
            $query->where('role_id', $userRole->id);
        })->where('status', 'Active')->latest()->get();
       
        return view('admin.deductions.create', compact('securityGuards'));
    }

    public function getEndDate(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $date = Carbon::parse($request->date);
        $noOfPayrolls = $request->no_of_payroll ?? 1;
        $fortnightStart = FortnightDates::where('start_date', '<=', $date)->orderBy('start_date', 'desc')->first();
        $nextFortnightDate = Carbon::parse($fortnightStart->start_date)->addDays(14);
        $fortnights  = FortnightDates::where('start_date', '>=', $nextFortnightDate)->orderBy('start_date', 'asc')->limit($noOfPayrolls)->get();

        if (!$fortnightStart) {
            return response()->json(['error' => 'No matching fortnight found for the selected start date.'], 404);
        }

        $endDate = Carbon::parse($fortnights->last()->end_date);
        $startDate = Carbon::parse($nextFortnightDate)->format('d-m-Y');

        return response()->json(['end_date' => $endDate->format('d-m-Y'), 'start_date' => $startDate]);
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            return null; 
        }

        try {
            return Carbon::createFromFormat('d-m-Y', $date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'guard_id'    => 'required|exists:users,id',
            'type'        => 'required|string',
            'amount'      => 'required|numeric|min:0',
            'document_date' => 'required|date',
            'start_date'  => 'required|date',
            'end_date'    => 'required|date|after_or_equal:start_date',
        ]);

        $noOfPayrolls = $request->no_of_payroll ?? 1;
        $oneInstallment = $request->amount / $noOfPayrolls;

        $existingDeduction = Deduction::where('guard_id', $request->guard_id)
            ->where('type', $request->type)
            ->where(function($query) use ($request) {
                $query->whereBetween('start_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
                      ->orWhereBetween('end_date', [$this->parseDate($request->start_date), $this->parseDate($request->end_date)])
                      ->orWhere(function($query) use ($request) {
                          $query->where('start_date', '<=', $this->parseDate($request->start_date))
                                ->where('end_date', '>=', $this->parseDate($request->end_date));
                      });
            })
            ->exists();

        if ($existingDeduction) {
            return redirect()->route('deductions.index')->with('error', 'A deduction of this type already exists for this guard.');
        }

        Deduction::create([
            'guard_id'     => $request->guard_id,
            'type'         => $request->type,
            'amount'       => $request->amount,
            'no_of_payroll'=> $noOfPayrolls,
            'document_date'=> $this->parseDate($request->document_date),
            'start_date'   => $this->parseDate($request->start_date),
            'end_date'     => $this->parseDate($request->end_date),
            'one_installment' => $oneInstallment,
            'pending_balance' => $request->amount
        ]);

        return redirect()->route('deductions.index')->with('success', 'Deduction created successfully.');
    }

    public function exportDeduction()
    {
        $spreadsheet = new Spreadsheet();

        $this->addDeductionsSheet($spreadsheet);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Deduction.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addDeductionsSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Deductions');

        $headers = ['ID', 'Employee No', 'Employee Name', 'Non Stat Deduction', 'Amount', 'No of deductions', 'Document Date', 'Date Deducted', 'Amount Deducted', 'Balance'];
        $sheet->fromArray($headers, NULL, 'A1');

        $deductionDetails = DeductionDetail::with('deduction', 'deduction.user')->get();

        $row = 2;
        $previousDeductionId = null;

        foreach ($deductionDetails as $key => $deductionDetail) {
            if ($deductionDetail->deduction->id != $previousDeductionId) {
                $sheet->fromArray(
                    [
                        $deductionDetail->deduction->id,
                        $deductionDetail->deduction->user->user_code,
                        $deductionDetail->deduction->user->first_name,
                        $deductionDetail->deduction->type,
                        $deductionDetail->deduction->amount,
                        $deductionDetail->deduction->no_of_payroll,
                        $deductionDetail->deduction->document_date,
                        $deductionDetail->deduction_date,
                        $deductionDetail->amount_deducted,
                        $deductionDetail->balance
                    ],
                    NULL,
                    'A' . $row
                );
                $previousDeductionId = $deductionDetail->deduction->id;
                $row++;
            } else {
                $sheet->fromArray(
                    [
                        '', '', '', '', '',
                        '', '', $deductionDetail->deduction_date,
                        $deductionDetail->amount_deducted,
                        $deductionDetail->balance
                    ],
                    NULL,
                    'A' . $row
                );
                $row++;
            }
        }

        $spreadsheet->createSheet();
    }
}
