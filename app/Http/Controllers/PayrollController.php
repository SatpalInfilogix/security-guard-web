<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\FortnightDates;
use App\Models\PayrollDetail;
use App\Models\Leave;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use App\Imports\PayrollImport;
use App\Exports\PayrollExport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Gate;
use App\Exports\GuardPayrollExport;
use Dompdf\Dompdf;
use Dompdf\Options;
use ZipArchive;

class PayrollController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view payroll')) {
            abort(403);
        }

        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
        $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);

        return view('admin.payroll.index', compact('fortnightDays', 'previousFortnightEndDate', 'previousFortnightStartDate'));
    }

    public function getPayroll(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
        $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
        $payrolls = Payroll::with('user');

        if ($request->has('date') && !empty($request->date)) {
            $searchDate = $request->date;
            list($startDate, $endDate) = explode(' to ', $searchDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            $payrolls->whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate);
        } else {
            $payrolls->where('start_date', '>=', $previousFortnightStartDate)->whereDate('end_date', '<=', $previousFortnightEndDate);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $payrolls->where(function ($query) use ($searchValue) {
                $query->where('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('normal_hours', 'like', '%' . $searchValue . '%')
                    ->orWhere('overtime', 'like', '%' . $searchValue . '%')
                    ->orWhere('public_holidays', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($q) use ($searchValue) {
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

    public function create()
    {
        //
    }

    public function store(Request $request)
    {
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
        if (!Gate::allows('edit payroll')) {
            abort(403);
        }
        $payroll = Payroll::where('id', $payroll->id)->with('user', 'user.guardAdditionalInformation')->first();
        $month = $payroll->end_date;
        $fullYearPayroll = Payroll::where('guard_id', $payroll->guard_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();

        $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary_earned');
        $payroll['nis_total'] = $fullYearPayroll->sum('less_nis');
        $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
        $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
        $payroll['nht_total'] = $fullYearPayroll->sum('nht');

        $fortnightDayCount = FortnightDates::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();

        return view('admin.payroll.edit', compact('payroll', 'fortnightDayCount'));
    }

    public function update(Request $request, Payroll $payroll)
    {
        if (!Gate::allows('edit payroll')) {
            abort(403);
        }
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

    public function payrollExport(Request $request)
    {
        $selectedDate = $request->input('date');
        $spreadsheet = new Spreadsheet();

        $this->addPayrollSheet($spreadsheet, $selectedDate);

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Payroll.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    protected function addPayrollSheet($spreadsheet, $selectedDate)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Payrolls');

        $headers = [
            'ID',
            'Surename',
            'Firstname',
            'Middle Initials',
            'Employee TRN',
            'Employee NIS',
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


        if ($selectedDate) {
            list($startDate, $endDate) = explode(' to ', $selectedDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            $fortnightDays = FortnightDates::whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate)->get();
            if ($fortnightDays) {
                $previousFortnightStartDate = Carbon::parse($fortnightDays->first()->start_date);
                $previousFortnightEndDate = Carbon::parse($fortnightDays->last()->end_date);
            } else {
                $previousFortnightStartDate = '';
                $previousFortnightEndDate  = '';
            }
        } else {
            $today = Carbon::now()->startOfDay();
            $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
            $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
            $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
        }

        $Payrolls = Payroll::with('user', 'user.guardAdditionalInformation')->where('start_date', '>=', $previousFortnightStartDate)->whereDate('end_date', '<=', $previousFortnightEndDate)->get();

        foreach ($Payrolls as $key => $payroll) {
            $sheet->fromArray(
                [
                    $payroll->id,
                    $payroll->user->surname,
                    $payroll->user->first_name,
                    $payroll->user->middle_name,
                    $payroll->user->guardAdditionalInformation->trn,
                    $payroll->user->guardAdditionalInformation->nis,
                    $payroll->gross_salary_earned,
                    0,
                    $payroll->approved_pension_scheme,
                    0,
                    $payroll->less_nis + $payroll->employer_contribution_nis_tax,
                    $payroll->nht + $payroll->employer_contribution_nht_tax,
                    $payroll->education_tax + $payroll->employer_eduction_tax,
                    $payroll->paye
                ],
                NULL,
                'A' . ($key + 2)
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

    public function downloadPdf($payrollId)
    {
        $payroll = Payroll::where('id', $payrollId)->with('user', 'user.guardAdditionalInformation')->first();
        $month = $payroll->end_date;
        $fullYearPayroll = Payroll::where('guard_id', $payroll->guard_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();

        $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary_earned');
        $payroll['nis_total'] = $fullYearPayroll->sum('less_nis');
        $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
        $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
        $payroll['nht_total'] = $fullYearPayroll->sum('nht');

        $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
        $currentYear = now()->year;
        $approvedLeaves = Leave::where('guard_id', $payroll->guard_id)->where('status', 'Approved')->whereDate('date', '<=', $month)->whereYear('date', $currentYear)->count();
        $payroll['pendingLeaveBalance'] =  max(0, $paidLeaveBalanceLimit - $approvedLeaves);

        $VacationpaidLeaveBalanceLimit = (int) setting('vacation_leaves') ?: 10;
        $approvedVacationLeaves = Leave::where('guard_id', $payroll->guard_id)
            ->where('status', 'Approved')
            ->where('leave_type', 'Vacation Leave')
            ->whereDate('date', '<=', $month)
            ->whereYear('date', $currentYear)
            ->count();

        $payroll['pendingVacationLeaveBalance'] = max(0, $VacationpaidLeaveBalanceLimit - $approvedVacationLeaves);

        $fortnightDayCount = FortnightDates::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isPhpEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $html = view('admin.payroll.payroll-pdf.new-payroll', ['payroll' => $payroll, 'fortnightDayCount' => $fortnightDayCount])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // return view('admin.payroll.payroll-pdf.payroll', compact('payroll', 'fortnightDayCount'));
        return $dompdf->stream($payroll->user->first_name . '-' . $fortnightDayCount->id . '-' . \Carbon\Carbon::parse($payroll->start_date)->year . '.pdf');
    }

    public function bulkDownloadPdf(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $fortnightDays = FortnightDates::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousFortnightEndDate = Carbon::parse($fortnightDays->start_date)->subDay();
        $previousFortnightStartDate = $previousFortnightEndDate->copy()->subDays(13);
        $payrolls = Payroll::with('user');

        if ($request->has('date') && !empty($request->date)) {
            $searchDate = $request->date;
            list($startDate, $endDate) = explode(' to ', $searchDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            $payrolls->whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate);
        } else {
            $payrolls->where('start_date', '>=', $previousFortnightStartDate)->whereDate('end_date', '<=', $previousFortnightEndDate);
        }

        $payrolls = $payrolls->get();

        $tempZipFile = tempnam(sys_get_temp_dir(), 'payrolls-') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tempZipFile, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['error' => 'Failed to create zip file.'], 500);
        }

        foreach ($payrolls as $payroll) {
            $month = $payroll->end_date;
            $fullYearPayroll = Payroll::where('guard_id', $payroll->guard_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();

            $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary_earned');
            $payroll['nis_total'] = $fullYearPayroll->sum('less_nis');
            $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
            $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
            $payroll['nht_total'] = $fullYearPayroll->sum('nht');

            $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
            $currentYear = now()->year;
            $approvedLeaves = Leave::where('guard_id', $payroll->guard_id)->where('status', 'Approved')->whereDate('date', '<=', $month)->whereYear('date', $currentYear)->count();
            $payroll['pendingLeaveBalance'] =  max(0, $paidLeaveBalanceLimit - $approvedLeaves);

            $fortnightDayCount = FortnightDates::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();

            $html = view('admin.payroll.payroll-pdf.new-payroll', [
                'payroll' => $payroll,
                'fortnightDayCount' => $fortnightDayCount
            ])->render();

            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            $pdfContent = $dompdf->output();
            $pdfName = $payroll->user->first_name . '-' . $fortnightDayCount->id . '-' . Carbon::parse($payroll->start_date)->year . '.pdf';
            $zip->addFromString($pdfName, $pdfContent);
        }

        $zip->close();

        if (!file_exists($tempZipFile)) {
            return response()->json(['error' => 'Payroll data not exixt.'], 500);
        }

        $zipFileContent = file_get_contents($tempZipFile);

        return response($zipFileContent, 200)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="payrolls.zip"')
            ->header('Content-Length', strlen($zipFileContent));
    }

    public function exportGuardPayroll(Request $request)
    {
        $dateRange = $request->date;
        // dd($dateRange);
        return Excel::download(new GuardPayrollExport($dateRange), 'guard_payroll_export.xlsx');
    }
}
