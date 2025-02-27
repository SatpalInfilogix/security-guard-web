<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use App\Models\EmployeePayroll;
use App\Models\TwentyTwoDayInterval;
use Dompdf\Dompdf;
use Dompdf\Options;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EmployeePayrollController extends Controller
{
    public function index()
    {
        if(!Gate::allows('view employee payroll')) {
            abort(403);
        }

        $today = Carbon::now()->startOfDay();
        $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays(21);

        return view('admin.employee-payroll.index', compact('twentyTwoDays','previousEndDate', 'previousStartDate'));
    }

    public function getEmployeePayroll(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays(21);
        $employeePayrolls = EmployeePayroll::with('user');
        
        if ($request->has('date') && !empty($request->date)) {
            $searchDate = $request->date;
            list($startDate, $endDate) = explode(' to ', $searchDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            $employeePayrolls->whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate);
        } else {
            $employeePayrolls->where('start_date', '>=', $previousStartDate)->whereDate('end_date', '<=', $previousEndDate);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $employeePayrolls->where(function($query) use ($searchValue) {
                $query->where('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('normal_days', 'like', '%' . $searchValue . '%')
                    ->orWhere('leave_paid', 'like', '%' . $searchValue . '%')
                    ->orWhere('leave_not_paid', '%'. $searchValue . '%')
                    ->orWhereHas('user', function($q) use ($searchValue) {
                        $q->where('first_name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = EmployeePayroll::count();
        $filteredRecords = $employeePayrolls->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $employeePayrolls = $employeePayrolls->skip($start)->take($length)->get();

        $data = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $employeePayrolls,
        ];

        return response()->json($data);
    }

    public function edit(EmployeePayroll $employeePayroll)
    {
        if(!Gate::allows('edit employee payroll')) {
            abort(403);
        }
        $employeePayroll = EmployeePayroll::where('id', $employeePayroll->id)->with('user', 'user.guardAdditionalInformation')->first();
        $month = $employeePayroll->end_date;
        $fullYearPayroll = EmployeePayroll::where('employee_id', $employeePayroll->employee_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();

        $employeePayroll['gross_total'] = $fullYearPayroll->sum('gross_salary_earned');
        $employeePayroll['nis_total'] = $fullYearPayroll->sum('nis');
        $employeePayroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
        $employeePayroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
        $employeePayroll['nht_total'] = $fullYearPayroll->sum('nht');

        $twentyTwoDayCount = TwentyTwoDayInterval::where('start_date', $employeePayroll->start_date)->where('end_date', $employeePayroll->end_date)->first();

        return view('admin.employee-payroll.edit', compact('employeePayroll', 'twentyTwoDayCount'));
    }

    public function bulkDownloadPdf(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays(21);
        $payrolls = EmployeePayroll::with('user');

        if ($request->has('date') && !empty($request->date)) {
            $searchDate = $request->date;
            list($startDate, $endDate) = explode(' to ', $searchDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            $payrolls->whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate);
        } else {
            $payrolls->where('start_date', '>=', $previousStartDate)->whereDate('end_date', '<=', $previousEndDate);
        }

        $payrolls = $payrolls->get();

        $tempZipFile = tempnam(sys_get_temp_dir(), 'payrolls-') . '.zip';

        $zip = new ZipArchive();
        if ($zip->open($tempZipFile, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['error' => 'Failed to create zip file.'], 500);
        }

        foreach ($payrolls as $payroll) {
            $month = $payroll->end_date;
            $fullYearPayroll = EmployeePayroll::where('employee_id', $payroll->employee_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();

            $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary');
            $payroll['nis_total'] = $fullYearPayroll->sum('less');
            $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
            $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
            $payroll['nht_total'] = $fullYearPayroll->sum('nht');

            $fortnightDayCount = TwentyTwoDayInterval::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();

            $html = view('admin.employee-payroll.employee-payroll-pdf.employee-payroll', [
                'employeePayroll' => $payroll,
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
            return response()->json(['error' => 'Employee Payroll data not exixt.'], 500);
        }
        $zipFileContent = file_get_contents($tempZipFile);

        return response($zipFileContent, 200)
            ->header('Content-Type', 'application/zip')
            ->header('Content-Disposition', 'attachment; filename="payrolls.zip"')
            ->header('Content-Length', strlen($zipFileContent));
    }

    public function downloadPdf($payrollId)
    {
        $payroll = EmployeePayroll::where('id', $payrollId)->with('user', 'user.guardAdditionalInformation')->first();
        $month = $payroll->end_date;
        $fullYearPayroll = EmployeePayroll::where('employee_id', $payroll->employee_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();

        $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary');
        $payroll['nis_total'] = $fullYearPayroll->sum('nis');
        $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
        $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
        $payroll['nht_total'] = $fullYearPayroll->sum('nht');

        $fortnightDayCount = TwentyTwoDayInterval::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isPhpEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $html = view('admin.employee-payroll.employee-payroll-pdf.employee-payroll', ['employeePayroll' => $payroll, 'fortnightDayCount' => $fortnightDayCount])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // return view('admin.payroll.payroll-pdf.payroll', compact('payroll', 'fortnightDayCount'));
        return $dompdf->stream($payroll->user->first_name . '-' . $fortnightDayCount->id . '-' . \Carbon\Carbon::parse($payroll->start_date)->year . '.pdf');
    }

    public function employeePayrollExport(Request $request)
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
        $sheet->setTitle('EmployeePayrolls');

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


        if ($selectedDate) {
            list($startDate, $endDate) = explode(' to ', $selectedDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();
            $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate)->get();
            if($twentyTwoDays) {
                $previousStartDate = Carbon::parse($twentyTwoDays->first()->start_date);
                $previousEndDate = Carbon::parse($twentyTwoDays->last()->end_date);
            } else {
                $previousStartDate = '';
                $previousEndDate  = '';
            }
        } else {
            $today = Carbon::now()->startOfDay();
            
            $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
            $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
            $previousStartDate = $previousEndDate->copy()->subDays(21);
        }

        $Payrolls = EmployeePayroll::with('user', 'user.guardAdditionalInformation')->where('start_date', '>=', $previousStartDate)->whereDate('end_date', '<=', $previousEndDate)->get();
        foreach ($Payrolls as $key => $payroll) {
            $sheet->fromArray(
                [
                    $payroll->id, $payroll->user->surname, $payroll->user->first_name, $payroll->user->middle_name,
                    $payroll->user->guardAdditionalInformation->trn, $payroll->user->guardAdditionalInformation->nis,
                    $payroll->gross_salary, 0, $payroll->approved_pension_scheme, 0, $payroll->nis + $payroll->employer_contribution_nis_tax,
                    $payroll->nht + $payroll->employer_contribution_nht_tax, $payroll->education_tax + $payroll->employer_eduction_tax, $payroll->paye
                ],
                NULL, 'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }
}
