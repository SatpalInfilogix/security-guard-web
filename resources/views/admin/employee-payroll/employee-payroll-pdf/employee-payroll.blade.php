<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payroll</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
            word-wrap: break-word;
            font-size: 10px;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <div class="container">
        <table style="border-collapse: collapse; width: 100%;">
            <tr>
                <td style="border: none;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/logo-admin.png'))) }}" alt="Logo" style="max-height: 50px;">
                </td>
                <td style="border: none;">
                    <h2 style="margin: 0; font-size: 25px;">Payslip</h2>
                </td>
            </tr>
        </table>

        <div class="company-info">
            <table width="100%">
                <tr>
                    <td class="security" style="width: 50%; vertical-align: top;  border: 1px solid white;">
                        <p><strong>Employee Name:</strong> {{ $employeePayroll->user->first_name }} {{ $employeePayroll->user->last_name }}</p>
                        <p><strong>Department:</strong> N/A</p>
                        <p><strong>Category:</strong> N/A</p>
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: left; border: 1px solid white;">
                        <p><strong>NIS No: </strong>{{ $employeePayroll->user->guardAdditionalInformation->nis }}</p>
                        <p><strong>Employee TRN: </strong> {{ $employeePayroll->user->guardAdditionalInformation->trn }}</p>
                        <p><strong>Payroll Period: </strong> {{ $employeePayroll->start_date }} to {{ $employeePayroll->end_date }}</p>
                        <p><strong>Payroll No: </strong>{{ $fortnightDayCount->id }}</p>
                        <p><strong>Date of Processing: </strong>{{ $employeePayroll->created_at->format('d-M-Y') }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table width="100%">
            <thead>
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
            </thead>
            <tbody>
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
                    <td colspan="3"></td>
                    <td></td>
                    <td>Salary Advance</td>
                    <td>{{ $employeePayroll->salary_advance }}</td>
                    <td>{{ number_format($employeePayroll->pending_salary_advance)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td></td>
                    <td>Approved Pension</td>
                    <td>{{ $employeePayroll->approved_pension_scheme }}</td>
                    <td>{{ number_format($employeePayroll->pending_approved_pension)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td></td>
                    <td>PSRA</td>
                    <td>{{ $employeePayroll->psra }}</td>
                    <td>{{ number_format($employeePayroll->pending_psra)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td></td>
                    <td>Bank Loan</td>
                    <td>{{ $employeePayroll->bank_loan }}</td>
                    <td>{{ number_format($employeePayroll->pending_bank_loan)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td></td>
                    <td>Garnishment</td>
                    <td>{{ $employeePayroll->garnishment }}</td>
                    <td>{{ number_format($employeePayroll->pending_garnishment)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td></td>
                    <td>Damaged Goods</td>
                    <td>{{ $employeePayroll->damaged_goods }}</td>
                    <td>{{ number_format($employeePayroll->pending_damaged_goods)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="3"></td>
                    <td></td>
                    <td>Missing Goods</td>
                    <td>{{ $employeePayroll->missing_goods }}</td>
                    <td>{{ number_format($employeePayroll->pending_missing_goods)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td></td>
                    @php
                        $total = $employeePayroll->gross_salary ?? 0;
                        $totalAmount = $employeePayroll->paye +  $employeePayroll->education_tax + $employeePayroll->less_nis + $employeePayroll->nht + $employeePayroll->staff_loan +  $employeePayroll->medical_insurance + $employeePayroll->salary_advance + $employeePayroll->approved_pension_scheme + $employeePayroll->psra + $employeePayroll->bank_loan + $employeePayroll->missing_goods + $employeePayroll->damaged_goods + $employeePayroll->garnishment;
                    @endphp
                    <td><strong>{{ $total }}</strong></td>
                    <td></td>
                    <td><strong>{{ $totalAmount }}</strong></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td>Net Salary</td>
                    <td>{{ $total - $totalAmount }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td>BNS Account</td>
                    <td>{{ $total - $totalAmount }}</td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
        <p><strong>Year to Date</strong></p>
        <table width="100%">
            <thead>
                <tr>
                    <th>Gross Earnings</th>
                    <th>NIS</th>
                    <th>Tax</th>
                    <th>Education Tax</th>
                    <th>NHT</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $employeePayroll->gross_total }}</td>
                    <td>{{ $employeePayroll->nis_total }}</td>
                    <td>{{ $employeePayroll->paye_tax_total }}</td>
                    <td>{{ $employeePayroll->education_tax_total }}</td>
                    <td>{{ $employeePayroll->nht_total }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
    