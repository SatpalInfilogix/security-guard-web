<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payslip</title>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        table,
        th,
        td {
            border: 1px solid black;
        }

        th,
        td {
            padding: 4px;
            text-align: left;
            font-size: 10px;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h4 style="text-align: center">Vanguard Security Limited, 6 East Wood Avenue, Kingston 10, Jamaica Payslip</h4>
    {{-- <h4 style="text-align: center">Payslip</h4> --}}
    <table>
        <tr>
            <th>Employee Name</th>
            <td>{{ $employeePayroll->user->first_name }} {{ $employeePayroll->user->surname }}</td>
            <th>Employee NIS</th>
            <td>{{ $employeePayroll->user->guardAdditionalInformation->nis }}</td>
        </tr>
        <tr>
            <th>Department</th>
            <td>Operations</td>
            <th>Employee TRN</th>
            <td>{{ $employeePayroll->user->guardAdditionalInformation->trn }}</td>
        </tr>
        <tr>
            <th>Category</th>
            <td></td>
            <th>Payroll Period</th>
            <td>{{ $employeePayroll->start_date }} to {{ $employeePayroll->end_date }}</td>
        </tr>
        <tr>
            <th>Emp Start Date</th>
            <td>{{ $employeePayroll->user->guardAdditionalInformation->date_of_joining }}</td>
            <th>Period No.</th>
            <td>{{ $fortnightDayCount->id }}</td>
        </tr>
        <tr>
            <th>Payroll Processed Date</th>
            <td colspan="3">{{ $employeePayroll->created_at->format('d-M-Y') }}</td>
        </tr>
    </table>
    <br>
    <table>
        <tr>
            <th style="width: 18%; text-align: center;">Earnings</th>
            {{-- <th style="width: 10%; text-align: center;">Monthly Salary</th> --}}
            <th style="width: 8%; text-align: center;">Units</th>
            <th style="width: 10%; text-align: center;">Total Salary</th>
            <th style="width: 11%; text-align: center;">Deductions</th>
            <th style="width: 10%; text-align: center;">Amount</th>
            <th style="width: 10%; text-align: center;">Balance</th>
            {{-- <th style="width: 13%;">Employer contribution</th> --}}
        </tr>
        <tr>
            <td>Gross Earnings</td>
            {{-- <td style="text-align: right;">{{ formatAmount($employeePayroll->day_salary) }}</td> --}}
            <td style="text-align: right;">
                {{ $employeePayroll->leave_paid > 0 ? $employeePayroll->normal_days - $employeePayroll->leave_not_paid - $employeePayroll->leave_paid : $employeePayroll->normal_days - $employeePayroll->leave_not_paid }}
            </td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->normal_salary) }}</td>
            <td>PAYE</td>
            <td style="text-align: right;">
                {{ formatAmount($employeePayroll->paye) }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Leave Paid</td>
            {{-- <td></td> --}}
            <td style="text-align: right;">{{ $employeePayroll->leave_paid }}</td>
            <td>-</td>
            <td>Ed Tax</td>
            <td style="text-align: right;">
                {{ formatAmount($employeePayroll->education_tax) }}</td>
            <td></td>
            {{-- <td>{{ $employeePayroll->employer_eduction_tax }}</td> --}}
        </tr>
        <tr>
            <td>Employee Allowance</td>
            {{-- <td></td> --}}
            <td></td>
            <td style="text-align: right;">{{ formatAmount($employeeAllowance) }}</td>
            <td>NIS</td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->nis) }}
            </td>
            <td></td>
            {{-- <td>{{ $employeePayroll->employer_contribution_nis_tax }}</td> --}}
        </tr>
        <tr>
            @if ($employeePayroll->pending_leave_balance > 0)
                <td>Pending Balance</td>
                {{-- <td>{!! '&nbsp;' !!}</td> --}}
                <td style="text-align: right;">{{ $employeePayroll->pending_leave_balance }}</td>
                <td style="text-align: right;">{{ formatAmount($employeePayroll->pending_leave_amount) }}</td>
            @else
                <td colspan="3">{!! '&nbsp;' !!}</td>
                {{-- <td>{!! '&nbsp;' !!}</td> --}}
            @endif

            <td>NHT</td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->nht) }}</td>
            <td>{!! '&nbsp;' !!}</td>
        </tr>
        <tr>
            <td>Employee Overtime</td>
            <td></td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->overtime_income_total) }}</td>
            {{-- <td></td> --}}
            <td>Heart</td>
            <td></td>
            <td></td>
            {{-- <td>{{$employeePayroll->heart}}</td> --}}
        </tr>
        @if ($employeePayroll->staff_loan > 0 || $employeePayroll->pending_staff_loan > 0)
            <tr>
                <td colspan="3">{!! '&nbsp;' !!}</td>
                {{-- <td>{!! '&nbsp;' !!}</td> --}}
                <td>Staff Loan</td>
                <td style="text-align: right;">{{ formatAmount($employeePayroll->staff_loan) }}</td>
                <td style="text-align: right;">{{ number_format($employeePayroll->pending_staff_loan) }}</td>
                {{-- <td></td> --}}
            </tr>
        @endif

        @if ($employeePayroll->medical_insurance > 0 || $employeePayroll->pending_medical_insurance > 0)
            <tr>
                <td colspan="3">{!! '&nbsp;' !!}</td>
                {{-- <td>{!! '&nbsp;' !!}</td> --}}
                <td>Medical Ins</td>
                <td style="text-align: right;">{{ formatAmount($employeePayroll->medical_insurance) }}</td>
                <td style="text-align: right;">{{ number_format($employeePayroll->pending_medical_insurance) }}</td>
                {{-- <td></td> --}}
            </tr>
        @endif

        @php
            $total = ($employeePayroll->gross_salary ?? 0) + ($employeeAllowance ?? 0);
            $totalAmount =
                $employeePayroll->paye +
                $employeePayroll->education_tax +
                $employeePayroll->nis +
                $employeePayroll->nht +
                $employeePayroll->staff_loan +
                $employeePayroll->medical_insurance +
                $employeePayroll->salary_advance +
                $employeePayroll->approved_pension_scheme +
                $employeePayroll->psra +
                $employeePayroll->bank_loan +
                $employeePayroll->missing_goods +
                $employeePayroll->damaged_goods +
                $employeePayroll->garnishment;
        @endphp
        <tr>
            <th>Total (JMD)</th>
            {{-- <td></td> --}}
            <td></td>
            <td style="text-align: right;">{{ formatAmount($total) }}</td>
            <td></td>
            <td style="text-align: right;">{{ formatAmount($totalAmount) }}</td>
            <td></td>
            {{-- <td></td> --}}
        </tr>
        <tr>
            <td></td>
            {{-- <td></td> --}}
            <td></td>
            <td></td>
            <th>Net Salary (JMD)</th>
            <td style="text-align: right;">{{ formatAmount($total - $totalAmount) }}</td>
            <td></td>
            {{-- <td></td> --}}
        </tr>
    </table>
    {{-- <br>
    <table>
        <tr>
            <th>Bank Name</th>
            <th>Account No.</th>
            <th>Credit Amount</th>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td style="text-align: right;">{{ formatAmount($total - $totalAmount) }}</td>
        </tr>
    </table> --}}
    <br>
    <span style="text-size: 18px "><strong>Year to Date (JMD)</strong></span>
    <table>
        <tr>
            {{-- <th>Year to Date (JMD)</th> --}}
            <th style="text-align: center">Gross Earnings</th>
            <th style="text-align: center">NIS</th>
            <th style="text-align: center">Tax</th>
            <th style="text-align: center">Ed Tax</th>
            <th style="text-align: center">NHT</th>
            {{-- <th style="text-align: center">Annual Leave</th> --}}
            {{-- <th style="text-align: center">Sick Leave</th> --}}
        </tr>
        <tr>
            {{-- <td></td> --}}
            <td style="text-align: right;">{{ formatAmount($employeePayroll->gross_total) }}</td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->nis_total) }}</td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->paye_tax_total) }}</td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->education_tax_total) }}</td>
            <td style="text-align: right;">{{ formatAmount($employeePayroll->nht_total) }}</td>
            {{-- <td style="text-align: right;">{{ $employeePayroll->pendingLeaveBalance }}</td> --}}
            {{-- <td style="text-align: right;">{{ setting('yearly_leaves') ?: 10 }} </td> --}}
        </tr>
    </table>
</body>

</html>
