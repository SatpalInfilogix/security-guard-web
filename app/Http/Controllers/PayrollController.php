<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\FortnightDates;
use App\Models\PayrollDetail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\PayrollImport;
use App\Exports\PayrollExport;
use Maatwebsite\Excel\Facades\Excel;

class PayrollController extends Controller
{
    public function index()
    {
        return view('admin.payroll.index');
    }

    public function getPayroll(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
        $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
        $payrolls = Payroll::with('user');
        
        if ($request->has('date') && !empty($request->date)) {
            $searchDate = Carbon::parse($request->date);
            $payrolls->whereDate('start_date', '<=', $searchDate)->whereDate('end_date', '>=', $searchDate);
        } else {
            $payrolls->where('start_date', '>=', $previousFortnightStartDate)->whereDate('end_date', '<=', $previousFortnightEndDate);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $payrolls->where(function($query) use ($searchValue) {
                $query->where('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('normal_hours', 'like', '%' . $searchValue . '%')
                    ->orWhere('overtime', 'like', '%' . $searchValue . '%')
                    ->orWhere('public_holidays', '%'. $searchValue . '%')
                    ->orWhereHas('user', function($q) use ($searchValue) {
                        $q->where('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = Payroll::count();
        $filteredRecords = $payrolls->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $payrolls = $payrolls->skip($start)->take($length)->get();
        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $payrolls,
        ];

        return response()->json($data);
    }

    public function create() {
        //
    }

    public function store(Request $request) {
        //
    }
    
    public function show(Payroll $payroll)
    {
        $payroll = Payroll::where('id', $payroll->id)->with('user', 'user.guardAdditionalInformation')->first();
        $payrollDetails = PayrollDetail::with('user')->where('payroll_id', $payroll->id)->get();
        $fortnightDayCount = FortnightDates::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();

        return view('admin.payroll.show', compact('payrollDetails', 'payroll', 'fortnightDayCount'));
    }

    public function edit(Payroll $payroll)
    {
        $payroll = Payroll::where('id', $payroll->id)->with('user', 'user.guardAdditionalInformation')->first();
        $fortnightDayCount = FortnightDates::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();
        
        return view('admin.payroll.edit', compact('payroll', 'fortnightDayCount'));
    }

    public function update(Request $request, Payroll $payroll) 
    {
        $payroll->update([
            'paye'              => $request->paye,
            'staff_loan'        => $request->staff_loan,
            'medical_insurance' => $request->medical_insurance
        ]);

        session()->flash('success', 'Payroll details updated successfully');
        return response()->json([
            'success' => true
        ]);
    }

    public function payrollExport()
    {
        $spreadsheet = new Spreadsheet();

        $this->addPayrollSheet($spreadsheet);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Payroll.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addPayrollSheet($spreadsheet)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payrolls');

        $headers = [
            'ID', 'Surename', 'Firstname', 'Middle Initials', 'Employee TRN', 'Employee NIS',
            'Gross Emoluments Received in Cash (Salaries, Wages, Fees, Bonuses, Overtime, Commissions)',
            'Gross Emoluments Received in Kind',
            'Superannuation / Pension, Agreed Expenses, Employees Share Ownership Plan',
            'Number of weekly NIS and NHT Contributions for the month',
            'NIS (Employee’s Rate + Employer’s Rate) x (Total Gross Emoluments)',
            'NHT (Employee’s Rate + Employer’s Rate) x (Total Gross Emoluments)',
            'Education Tax (Employee’s Rate + Employer’s Rate) x (Total Gross Emoluments after Deductions and NIS)',
            'PAYE Income Tax / (Refunds) (Rate) x (Total Gross Emoluments after Deductions, NIS and Nil-Rate (NR))',
        ];

        $sheet->fromArray($headers, NULL, 'A1');

        foreach (range('A', 'N') as $column) {
            $sheet->getColumnDimension($column)->setWidth(15);
            $sheet->getStyle($column . '1')->getAlignment()->setWrapText(true);
            $sheet->getStyle($column . '1')->getFont()->setBold(true);
        }

        $sheet->getRowDimension(1)->setRowHeight(60);


        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
        $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
        $Payrolls = Payroll::with('user', 'user.guardAdditionalInformation')->where('start_date', '>=', $previousFortnightStartDate)->whereDate('end_date', '<=', $previousFortnightEndDate)->get();

        foreach ($Payrolls as $key => $payroll) {
            $sheet->fromArray(
                [
                    $payroll->id, $payroll->user->surname, $payroll->user->first_name, $payroll->user->middle_name,
                    $payroll->user->guardAdditionalInformation->trn, $payroll->user->guardAdditionalInformation->nis,
                    $payroll->gross_salary_earned, 0, $payroll->approved_pension_scheme, 0, $payroll->less_nis,
                    $payroll->nht, $payroll->education_tax, $payroll->paye
                ],
                NULL, 'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    public function importPayroll(Request $request)
    {
        $import = new PayrollImport;
        Excel::import($import, $request->file('file'));

        session(['importData' => $import]);
        session()->flash('success', 'Payroll imported successfully.');
        $downloadUrl = route('payrolls.download');

        return redirect()->route('payrolls.index')->with('downloadUrl', $downloadUrl); 
    }

    public function download()
    {
        $import = session('importData'); 
        $export = new PayrollExport($import);
        return Excel::download($export, 'payroll_import_results.csv');
    }
}
