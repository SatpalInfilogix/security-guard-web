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
            <td>{{ $employeePayroll->user->first_name }} {{ $employeePayroll->user->last_name }}</td>
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
            <td>DD-MM-YY</td>
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
            <th style="width: 18%;">Earnings</th>
            <th style="width: 10%">Monthly Salary</th>
            <th style="width: 8%">Units</th>
            <th style="width: 10%;">Total Salary</th>
            <th style="width: 11%;">Deductions</th>
            <th style="width: 10%;">Amount</th>
            <th style="width: 10%;">Balance</th>
            <th style="width: 13%;">Employer contribution</th>
        </tr>
        <tr>
            <td>Gross Earnings</td>
            <td>{{ $employeePayroll->day_salary }}</td>
            <td>{{ $employeePayroll->normal_days - $employeePayroll->leave_not_paid }}</td>
            <td>{{ $employeePayroll->normal_salary }}</td>
            <td>PAYE</td>
            <td>{{ $employeePayroll->paye }}</td>
            <td></td>
            <td></td>
        </tr>
        <tr>
            <td>Leave Paid</td>
            <td></td>
            <td>{{ $employeePayroll->leave_paid }}</td>
            <td>-</td>
            <td>Ed Tax</td>
            <td>{{ $employeePayroll->education_tax }}</td>
            <td></td>
            <td>{{ $employeePayroll->employer_eduction_tax }}</td>
        </tr>
        <tr>
            <td>Leave Not Paid</td>
            <td></td>
            <td>{{ $employeePayroll->leave_not_paid }}</td>
            <td>-</td>
            <td>NIS</td>
            <td>{{ $employeePayroll->nis }}</td>
            <td></td>
            <td>{{ $employeePayroll->employer_contribution_nis_tax }}</td>
        </tr>
        <tr>
            @if($employeePayroll->pending_leave_balance > 0)
                <td>Pending Balance</td>
                <td></td>
                <td>{{ $employeePayroll->pending_leave_balance }}</td>
                <td>{{ $employeePayroll->pending_leave_amount }}</td>
            @else
                <td colspan="3"></td>
                <td></td>
            @endif
            <td>NHT</td>
            <td>{{ $employeePayroll->nht }}</td>
            <td></td>
            <td>{{$employeePayroll->employer_contribution_nht_tax}}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td></td>
            <td>Heart</td>
            <td></td>
            <td></td>
            <td>{{$employeePayroll->heart}}</td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td></td>
            <td>Staff Loan</td>
            <td>{{ $employeePayroll->staff_loan }}</td>
            <td>{{ number_format($employeePayroll->pending_staff_loan)}}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3"></td>
            <td></td>
            <td>Medical Ins</td>
            <td>{{ $employeePayroll->medical_insurance }}</td>
            <td>{{ number_format($employeePayroll->pending_medical_insurance)}}</td>
            <td></td>
        </tr>
        <tr>
            <th>Total (JMD)</th>
            <td></td>
            <td></td>
            <td></td>
            @php
                $total = $employeePayroll->gross_salary ?? 0;
                $totalAmount = $employeePayroll->paye +  $employeePayroll->education_tax + $employeePayroll->less_nis + $employeePayroll->nht + $employeePayroll->staff_loan +  $employeePayroll->medical_insurance + $employeePayroll->salary_advance + $employeePayroll->approved_pension_scheme + $employeePayroll->psra + $employeePayroll->bank_loan + $employeePayroll->missing_goods + $employeePayroll->damaged_goods + $employeePayroll->garnishment;
            @endphp
            <td>{{ $total }}</td>
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
            <td>{{ $total - $totalAmount }}</td>
            <td></td>
        </tr>
    </table>
    <br>
    <table>
        <tr>
            <th>Bank Name</th>
            <th>Account No.</th>
            <th>Credit Amount</th>
        </tr>
        <tr>
            <td></td>
            <td></td>
            <td>{{ $total - $totalAmount }}</td>
        </tr>
    </table>
    <br>
    <table>
        <tr>
            <th>Year to Date (JMD)</th>
            <th>Gross Earnings</th>
            <th>NIS</th>
            <th>Tax</th>
            <th>Ed Tax</th>
            <th>NHT</th>
            <th>Annual Leave</th>
            <th>Sick Leave</th>
        </tr>
        <tr>
            <td></td>
            <td>{{ $employeePayroll->gross_total }}</td>
            <td>{{ $employeePayroll->nis_total }}</td>
            <td>{{ $employeePayroll->paye_tax_total }}</td>
            <td>{{ $employeePayroll->education_tax_total }}</td>
            <td>{{ $employeePayroll->nht_total }}</td>
            <td>{{ $employeePayroll->pendingLeaveBalance }}</td>
            <td>{{ setting('yearly_leaves') ?: 10 }} </td>
        </tr>
    </table>
</body>
</html>