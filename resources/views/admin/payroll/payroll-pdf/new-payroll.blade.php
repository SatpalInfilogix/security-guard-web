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
        table, th, td {
            border: 1px solid black;
        }
        th, td {
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
    <h4 style="text-align: center">Vanguard Security Limited, 6 East Wood Avenue, Kingston 10, Jamaica</h4>
    <h4 style="text-align: center">Payslip</h4>
    <table>
        <tr>
            <th>Employee Name</th>
            <td>{{ $payroll->user->first_name }} {{ $payroll->user->last_name }}</td>
            <th>Employee NIS</th>
            <td>{{ $payroll->user->guardAdditionalInformation->nis }}</td>
        </tr>
        <tr>
            <th>Department</th>
            <td>Operations</td>
            <th>Employee TRN</th>
            <td>{{ $payroll->user->guardAdditionalInformation->trn }}</td>
        </tr>
        <tr>
            <th>Category</th>
            <td>{{ $payroll->user->guardAdditionalInformation->rateMaster->guard_type }}</td>
            <th>Payroll Period</th>
            <td>{{ $payroll->start_date }} to {{ $payroll->end_date }}</td>
        </tr>
        <tr>
            <th>Emp Start Date</th>
            <td>DD-MM-YY</td>
            <th>Period No.</th>
            <td>{{ $fortnightDayCount->id }}</td>
        </tr>
        <tr>
            <th>Payroll Processed Date</th>
            <td colspan="3">{{ $payroll->created_at->format('d-M-Y') }}</td>
        </tr>
    </table>
    <br>
    <table>
        <tr>
            <th>Gross Earnings</th>
            <th>Rate</th>
            <th>Units</th>
            <th>Rate per unit</th>
            <th>Total (JMD)</th>
            <th>Deductions</th>
            <th>Amount (JMD)</th>
            <th>Balance</th>
        </tr>
        <tr>
            <td>Megamart Waterloo</td>
            <td>Normal</td>
            <td>{{ convertToHoursAndMinutes($payroll->normal_hours) }}</td>
            <td>-</td>
            <td>{{ $payroll->normal_hours_rate }}</td>
            <td>PAYE</td>
            <td>{{ $payroll->paye }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Megamart Waterloo</td>
            <td>Overtime</td>
            <td>{{ convertToHoursAndMinutes($payroll->overtime) }}</td>
            <td>-</td>
            <td>{{ $payroll->overtime_rate }}</td>
            <td>Ed Tax</td>
            <td>{{ $payroll->education_tax }}</td>
            <td></td>
        </tr>
        <tr>
            <td>Megamart Waterloo</td>
            <td>Double time</td>
            <td>{{ convertToHoursAndMinutes($payroll->public_holidays) }}</td>
            <td>-</td>
            <td>{{ $payroll->public_holiday_rate }}</td>
            <td>NIS</td>
            <td>{{ $payroll->less_nis }}</td>
            <td></td>
        </tr>
        <tr>
            @if($payroll->pending_leave_balance > 0)
            <td></td>
            <td>Leave Bal.</td>
            <td>{{ $payroll->pending_leave_balance }} D</td>
            <td>-</td>
            <td>{{ $payroll->pending_leaves_amount }}</td>
            @else
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            @endif
            <td>NHT</td>
            <td>{{ $payroll->nht }}</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Staff Loan</td>
            <td>{{ $payroll->staff_loan }}</td>
            <td>{{ number_format($payroll->pending_staff_loan)}}</td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td>Medical Ins</td>
            <td>{{ $payroll->medical_insurance }}</td>
            <td>{{ number_format($payroll->pending_medical_insurance)}}</td>
        </tr>
        <tr>
            <th>Total (JMD)</th>
            <td></td>
            <td></td>
            <td></td>
            @php
                $totalAmount = $payroll->paye +  $payroll->education_tax + $payroll->less_nis + $payroll->nht + $payroll->staff_loan +  $payroll->medical_insurance + $payroll->salary_advance + $payroll->approved_pension_scheme + $payroll->psra + $payroll->bank_loan + $payroll->missing_goods + $payroll->damaged_goods + $payroll->garnishment;
            @endphp
            <td>{{ $payroll->gross_salary_earned }}</td>
            <td></td>
            <td>{{ $totalAmount }}</td>
            <td></td>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <td></td>
            <th>Net Salary (JMD)</th>
            <td>{{ $payroll->gross_salary_earned - $totalAmount }}</td>
            <td></td>
        </tr>
    </table>
    <br>
    {{-- <table>
        <tr>
            <th>Bank Name</th>
            <th>Account No.</th>
            <th>Credit Amount</th>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>{{ $payroll->gross_salary_earned - $totalAmount }}</td>
        </tr>
    </table> --}}
    <br>
    <table>
        <tr>
            <th>Year to Date (JMD)</th>
            <th>Gross Earnings</th>
            <th>NIS</th>
            <th>Tax</th>
            <th>Ed Tax</th>
            <th>NHT</th>
            <th>Vacation Leave</th>
            <th>Sick Leave</th>
        </tr>
        <tr>
            <td></td>
            <td>{{ $payroll->gross_total }}</td>
            <td>{{ $payroll->nis_total }}</td>
            <td>{{ $payroll->paye_tax_total }}</td>
            <td>{{ $payroll->education_tax_total }}</td>
            <td>{{ $payroll->nht_total }}</td>
            <td>{{ $payroll->pendingVacationLeaveBalance }}</td>
            <td>{{ $payroll->pendingLeaveBalance }} </td>
        </tr>
    </table>
</body>
</html>