<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeNSTDeductionExport;
use App\Models\EmployeeLeave;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Carbon\Carbon;
use App\Models\EmployeePayroll;
use App\Models\EmployeeRateMaster;
use App\Models\TwentyTwoDayInterval;
use App\Models\EmployeeOvertime;
use App\Models\LeaveEncashment;
use Dompdf\Dompdf;
use Dompdf\Options;
use ZipArchive;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EmployeePayrollExport;
use App\Models\EmployeeDeductionDetail;

class EmployeePayrollController extends Controller
{
    public function index(Request $request)
    {
        if (!Gate::allows('view employee payroll')) {
            abort(403);
        }
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);
        $page = $request->input('page', 1);
        $today = Carbon::now()->startOfDay();
        $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();
        $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
        $previousStartDate = Carbon::parse($previousEndDate)->startOfMonth();

        return view('admin.employee-payroll.index', compact('year', 'month', 'page', 'twentyTwoDays', 'previousEndDate', 'previousStartDate'));
    }

    public function getEmployeePayroll(Request $request)
    {
        $employeePayrolls = EmployeePayroll::with('user');

        if ($request->has('year') && $request->has('month') && !empty($request->year) && !empty($request->month)) {
            $year = (int) $request->year;
            $month = (int) $request->month;

            $startDate = Carbon::create($year, $month, 1)->startOfDay();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

            $employeePayrolls->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate);
        } elseif ($request->has('date') && !empty($request->date)) {
            $searchDate = $request->date;
            list($startDate, $endDate) = explode(' to ', $searchDate);
            $startDate = Carbon::parse($startDate)->startOfDay();
            $endDate = Carbon::parse($endDate)->endOfDay();

            $employeePayrolls->whereDate('start_date', '<=', $endDate)
                ->whereDate('end_date', '>=', $startDate);
        } else {
            $today = Carbon::now()->startOfDay();
            $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)
                ->whereDate('end_date', '>=', $today)
                ->first();

            if ($twentyTwoDays) {
                $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
                $previousStartDate = Carbon::parse($previousEndDate)->startOfMonth();

                $employeePayrolls->where('start_date', '>=', $previousStartDate)
                    ->whereDate('end_date', '<=', $previousEndDate);
            }
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $employeePayrolls->where(function ($query) use ($searchValue) {
                $query->where('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('normal_days', 'like', '%' . $searchValue . '%')
                    ->orWhere('leave_paid', 'like', '%' . $searchValue . '%')
                    ->orWhere('leave_not_paid', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($q) use ($searchValue) {
                        $q->where('first_name', 'like', '%' . $searchValue . '%')
                            ->orWhere('surname', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        $totalRecords = EmployeePayroll::count();
        $filteredRecords = $employeePayrolls->count();

        $length = $request->input('length', 10);
        $start = $request->input('start', 0);

        $employeePayrolls = $employeePayrolls->skip($start)->take($length)->get();

        return response()->json([
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $employeePayrolls,
        ]);
    }

    /* public function getEmployeePayroll(Request $request)
    {
        $today = Carbon::now()->startOfDay();
        $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $today)->whereDate('end_date', '>=', $today)->first();

        $previousEndDate = Carbon::parse($twentyTwoDays->start_date)->subDay();
        $previousStartDate = Carbon::parse($previousEndDate)->startOfMonth();

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
            $employeePayrolls->where(function ($query) use ($searchValue) {
                $query->where('start_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('end_date', 'like', '%' . $searchValue . '%')
                    ->orWhere('normal_days', 'like', '%' . $searchValue . '%')
                    ->orWhere('leave_paid', 'like', '%' . $searchValue . '%')
                    ->orWhere('leave_not_paid', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('user', function ($q) use ($searchValue) {
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
    }*/

    public function edit(EmployeePayroll $employeePayroll)
    {
        if (!Gate::allows('edit employee payroll')) {
            abort(403);
        }
        $employeePayroll = EmployeePayroll::where('id', $employeePayroll->id)->with('user', 'user.guardAdditionalInformation')->first();
        $month = $employeePayroll->end_date;
        $fullYearPayroll = EmployeePayroll::where('employee_id', $employeePayroll->employee_id)->whereDate('end_date', '<=', $month)->whereYear('created_at', now()->year)->orderBy('created_at', 'desc')->get();
        $employeeRate = EmployeeRateMaster::where('employee_id', $employeePayroll->employee_id)->first();
        $employeeAllowance = $employeeRate?->employee_allowance ?? 0;
        $daySalary = $employeeRate?->daily_income ?? 0;


        $employeePayroll['gross_total'] = $fullYearPayroll->sum('gross_salary');
        $employeePayroll['nis_total'] = $fullYearPayroll->sum('nis');
        $employeePayroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
        $employeePayroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
        $employeePayroll['nht_total'] = $fullYearPayroll->sum('nht');

        $overtimeTotal = EmployeeOvertime::where('employee_id', $employeePayroll->employee_id)
            ->whereBetween('work_date', [$employeePayroll->start_date, $employeePayroll->end_date])
            ->sum('overtime_income');
        $overtimeHours = EmployeeOvertime::where('employee_id', $employeePayroll->employee_id)
            ->whereBetween('work_date', [$employeePayroll->start_date, $employeePayroll->end_date])
            ->sum('hours');

        $employeePayroll['overtime_income_total'] = $overtimeTotal;
        $twentyTwoDayCount = TwentyTwoDayInterval::where('start_date', $employeePayroll->start_date)->where('end_date', $employeePayroll->end_date)->first();
        $leaveEncashments = LeaveEncashment::where('employee_id', $employeePayroll->employee_id)
            ->whereDate('created_at', '<=', $employeePayroll->end_date)
            ->get();

        $encashLeaveDays = $leaveEncashments->sum('encash_leaves');
        $encashLeaveAmount = $encashLeaveDays * $daySalary;

        return view('admin.employee-payroll.edit', compact(
            'employeePayroll',
            'twentyTwoDayCount',
            'employeeAllowance',
            'encashLeaveDays',
            'encashLeaveAmount',
            'overtimeHours',
        ));
    }

    public function bulkDownloadPdf(Request $request)
    {
        if (!$request->filled('year') || !$request->filled('month')) {
            return response()->json(['error' => 'Year and month are required.'], 422);
        }

        $year = (int) $request->year;
        $month = (int) $request->month;

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfDay();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth()->endOfDay();

        $payrolls = EmployeePayroll::with('user')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                    ->orWhereBetween('end_date', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_date', '<', $startDate)
                            ->where('end_date', '>', $endDate);
                    });
            })
            ->get();

        if ($payrolls->isEmpty()) {
            return response()->json(['error' => 'No payrolls found for the selected date range.'], 404);
        }

        $tempZipFile = tempnam(sys_get_temp_dir(), 'payrolls-') . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($tempZipFile, ZipArchive::CREATE) !== TRUE) {
            return response()->json(['error' => 'Failed to create zip file.'], 500);
        }

        foreach ($payrolls as $payroll) {
            $monthEnd = $payroll->end_date;

            $fullYearPayroll = EmployeePayroll::where('employee_id', $payroll->employee_id)
                ->whereDate('end_date', '<=', $monthEnd)
                ->whereYear('created_at', $year)
                ->orderBy('created_at', 'desc')
                ->get();

            $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary');
            $payroll['nis_total'] = $fullYearPayroll->sum('less');
            $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
            $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
            $payroll['nht_total'] = $fullYearPayroll->sum('nht');

            $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
            $approvedLeaves = EmployeeLeave::where('employee_id', $payroll->employee_id)
                ->where('status', 'Approved')
                ->whereDate('date', '<=', $monthEnd)
                ->whereYear('date', $year)
                ->get()
                ->sum(function ($leave) {
                    return $leave->type === 'Half Day' ? 0.5 : 1;
                });

            $payroll['pendingLeaveBalance'] = max(0, $paidLeaveBalanceLimit - $approvedLeaves);

            $employeeRate = EmployeeRateMaster::where('employee_id', $payroll->employee_id)->first();
            $payroll['employee_allowance'] = $employeeRate?->employee_allowance ?? 0;
            $daySalary = $employeeRate?->daily_income ?? 0;
            $payroll['daily_income'] = $daySalary;

            $payroll['overtime_total'] = EmployeeOvertime::where('employee_id', $payroll->employee_id)
                ->whereBetween('work_date', [$payroll->start_date, $payroll->end_date])
                ->sum('overtime_income');

            $payroll['overtime_hours'] = EmployeeOvertime::where('employee_id', $payroll->employee_id)
                ->whereBetween('work_date', [$payroll->start_date, $payroll->end_date])
                ->sum('hours');

            $leaveEncashments = LeaveEncashment::where('employee_id', $payroll->employee_id)
                ->whereDate('created_at', '<=', $payroll->end_date)
                ->get();

            $encashLeaveDays = $leaveEncashments->sum('encash_leaves');
            $payroll['encash_leave_days'] = $encashLeaveDays;
            $payroll['encash_leave_amount'] = $encashLeaveDays * $daySalary;
            $employeeAllowance = $payroll['employee_allowance'];
            $overtimeHours = $payroll['overtime_hours'];
            $fortnightDayCount = TwentyTwoDayInterval::where('start_date', $payroll->start_date)
                ->where('end_date', $payroll->end_date)
                ->first();

            $html = view('admin.employee-payroll.employee-payroll-pdf.employee-payroll-new', [
                'employeePayroll' => $payroll,
                'fortnightDayCount' => $fortnightDayCount,
                'employeeAllowance' => $employeeAllowance,
                'overtimeHours' => $overtimeHours,
            ])->render();

            $dompdf = new Dompdf((new Options())->set('isHtml5ParserEnabled', true)->set('isPhpEnabled', true));
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $pdfName = $payroll->user->first_name . '-' . $fortnightDayCount->id . '-' . $year . '.pdf';
            $zip->addFromString($pdfName, $dompdf->output());
        }

        $zip->close();

        if (!file_exists($tempZipFile)) {
            return response()->json(['error' => 'Zip file creation failed.'], 500);
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
        $employeeRate = EmployeeRateMaster::where('employee_id', $payroll->employee_id)->first();
        $employeeAllowance = $employeeRate?->employee_allowance ?? 0;
        $daySalary = $employeeRate?->daily_income ?? 0;

        $payroll['gross_total'] = $fullYearPayroll->sum('gross_salary');
        $payroll['nis_total'] = $fullYearPayroll->sum('nis');
        $payroll['paye_tax_total'] = $fullYearPayroll->sum('paye');
        $payroll['education_tax_total'] = $fullYearPayroll->sum('education_tax');
        $payroll['nht_total'] = $fullYearPayroll->sum('nht');

        $overtimeTotal = EmployeeOvertime::where('employee_id', $payroll->employee_id)
            ->whereBetween('work_date', [$payroll->start_date, $payroll->end_date])
            ->sum('overtime_income');
        $overtimeHours = EmployeeOvertime::where('employee_id', $payroll->employee_id)
            ->whereBetween('work_date', [$payroll->start_date, $payroll->end_date])
            ->sum('hours');
        $payroll['overtime_income_total'] = $overtimeTotal;
        $leaveEncashments = LeaveEncashment::where('employee_id', $payroll->employee_id)
            ->whereDate('created_at', '<=', $payroll->end_date)
            ->get();
        $encashLeaveDays = $leaveEncashments->sum('encash_leaves');
        $encashLeaveAmount = $encashLeaveDays * $daySalary;
        $paidLeaveBalanceLimit = (int) setting('yearly_leaves') ?: 10;
        $currentYear = now()->year;
        $approvedLeaves = EmployeeLeave::where('employee_id', $payroll->employee_id)->where('status', 'Approved')->whereDate('date', '<=', $month)->whereYear('date', $currentYear)->get()
            ->sum(function ($leave) {
                return ($leave->type == 'Half Day') ? 0.5 : 1;
            });
        $payroll['pendingLeaveBalance'] =  max(0, $paidLeaveBalanceLimit - $approvedLeaves);

        $fortnightDayCount = TwentyTwoDayInterval::where('start_date', $payroll->start_date)->where('end_date', $payroll->end_date)->first();
        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isPhpEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $html = view('admin.employee-payroll.employee-payroll-pdf.employee-payroll-new', [
            'employeePayroll' => $payroll,
            'fortnightDayCount' => $fortnightDayCount,
            'employeeAllowance' => $employeeAllowance,
            'encashLeaveDays' => $encashLeaveDays,
            'encashLeaveAmount' => $encashLeaveAmount,
            'overtimeHours' => $overtimeHours,
        ])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // return view('admin.payroll.payroll-pdf.payroll', compact('payroll', 'fortnightDayCount'));
        return $dompdf->stream($payroll->user->first_name . '-' . $fortnightDayCount->id . '-' . \Carbon\Carbon::parse($payroll->start_date)->year . '.pdf');
    }

    public function employeePayrollExport(Request $request)
    {
        $year = $request->input('year');
        $month = $request->input('month');

        if (!$year || !$month) {
            return response()->json(['error' => 'Year and month are required.'], 400);
        }

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        $selectedDateRange = $startDate->format('Y-m-d') . ' to ' . $endDate->format('Y-m-d');

        $spreadsheet = new Spreadsheet();
        $this->addPayrollSheet($spreadsheet, $selectedDateRange);

        $monthYear = $startDate->format('F-Y');
        $fileName = 'SO1-Employee-' . $monthYear . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $fileName . '"');
        header('Cache-Control: max-age=0');

        $writer = new Xlsx($spreadsheet);
        $writer->save('php://output');
        exit;
    }

    protected function addPayrollSheet($spreadsheet, $selectedDate)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('EmployeePayrolls');

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
            $twentyTwoDays = TwentyTwoDayInterval::whereDate('start_date', '<=', $endDate)->whereDate('end_date', '>=', $startDate)->get();
            if ($twentyTwoDays) {
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
            $previousStartDate = Carbon::parse($previousEndDate)->startOfMonth();
        }

        $Payrolls = EmployeePayroll::with('user', 'user.guardAdditionalInformation')->where('start_date', '>=', $previousStartDate)->whereDate('end_date', '<=', $previousEndDate)->get();
        foreach ($Payrolls as $key => $payroll) {
            $sheet->fromArray(
                [
                    $payroll->id,
                    $payroll->user->surname,
                    $payroll->user->first_name,
                    $payroll->user->middle_name,
                    trnFormat($payroll->user->guardAdditionalInformation->trn),
                    $payroll->user->guardAdditionalInformation->nis,
                    formatAmount($payroll->gross_salary),
                    0,
                    formatAmount($payroll->approved_pension_scheme),
                    0,
                    formatAmount($payroll->nis + $payroll->employer_contribution_nis_tax),
                    formatAmount($payroll->nht + $payroll->employer_contribution_nht_tax),
                    formatAmount($payroll->education_tax + $payroll->employer_eduction_tax),
                    formatAmount($payroll->paye)
                ],
                NULL,
                'A' . ($key + 2)
            );
        }

        $spreadsheet->createSheet();
    }

    public function export(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer'
        ]);

        $year = $request->year;
        $month = $request->month;

        $fileName = "employee-payroll-{$month}-{$year}.xlsx";

        return Excel::download(new EmployeePayrollExport($year, $month), $fileName);
    }

    public function exportNSTDeductions(Request $request)
    {
        $year  = $request->input('year');
        $month = $request->input('month');

        $deductionDetails = EmployeeDeductionDetail::with(['deduction.user'])
            ->when($year, function ($query) use ($year) {
                $query->whereYear('deduction_date', $year);
            })
            ->when($month, function ($query) use ($month) {
                $query->whereMonth('deduction_date', $month);
            })
            ->get();

        return Excel::download(
            new EmployeeNSTDeductionExport($deductionDetails),
            'employee_nst_deductions.xlsx'
        );
    }
}
