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
                        <p><strong>Employee Name:</strong> {{ $payroll->user->first_name }} {{ $payroll->user->last_name }}</p>
                        <p><strong>Department:</strong> N/A</p>
                        <p><strong>Category:</strong> N/A</p>
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: left; border: 1px solid white;">
                        <p><strong>NIS No: </strong>{{ $payroll->user->guardAdditionalInformation->nis }}</p>
                        <p><strong>Employee TRN: </strong> {{ $payroll->user->guardAdditionalInformation->trn }}</p>
                        <p><strong>Payroll Period: </strong> {{ $payroll->start_date }} to {{ $payroll->end_date }}</p>
                        <p><strong>Payroll No: </strong>{{ $fortnightDayCount->id }}</p>
                        <p><strong>Date of Processing: </strong>{{ $payroll->created_at->format('d-M-Y') }}</p>
                    </td>
                </tr>
            </table>
        </div>

        <table width="100%">
            <thead>
                <tr>
                    <th style="width: 18%;">Gross Earnings</th>
                    <th style="width: 10%">Rate</th>
                    <th style="width: 8%">Units</th>
                    <th style="width: 10%;">Rate per Unit</th>
                    <th style="width: 12%;">Total</th>
                    <th style="width: 11%;">Deductions</th>
                    <th style="width: 10%;">Amount</th>
                    <th style="width: 10%;">Balance</th>
                    <th style="width: 13%;">Employer contribution</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Megamart Waterloo (Normal)</td>
                    <td>Normal</td>
                    <td>{{ convertToHoursAndMinutes($payroll->normal_hours) }}</td>
                    <td>-</td>
                    <td>{{ $payroll->normal_hours_rate }}</td>
                    <td>PAYE</td>
                    <td>{{ $payroll->paye }}</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td>Megamart Waterloo (Overtime)</td>
                    <td>Overtime</td>
                    <td>{{ convertToHoursAndMinutes($payroll->overtime) }}</td>
                    <td>-</td>
                    <td>{{ $payroll->overtime_rate }}</td>
                    <td>Ed Tax</td>
                    <td>{{ $payroll->education_tax }}</td>
                    <td></td>
                    <td>{{ $payroll->employer_eduction_tax }}</td>
                </tr>
                <tr>
                    <td>Megamart Waterloo (Public Holiday)</td>
                    <td>Public Holiday</td>
                    <td>{{ convertToHoursAndMinutes($payroll->public_holidays) }}</td>
                    <td>-</td>
                    <td>{{ $payroll->public_holiday_rate }}</td>
                    <td>NIS</td>
                    <td>{{ $payroll->less_nis }}</td>
                    <td></td>
                    <td>{{ $payroll->employer_contribution_nis_tax }}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>NHT</td>
                    <td>{{ $payroll->nht }}</td>
                    <td></td>
                    <td>{{$payroll->employer_contribution_nht_tax}}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Heart</td>
                    <td></td>
                    <td></td>
                    <td>{{$payroll->heart}}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Staff Loan</td>
                    <td>{{ $payroll->staff_loan }}</td>
                    <td>{{ number_format($payroll->pending_staff_loan)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Medical Ins</td>
                    <td>{{ $payroll->medical_insurance }}</td>
                    <td>{{ number_format($payroll->pending_medical_insurance)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Salary Advance</td>
                    <td>{{ $payroll->salary_advance }}</td>
                    <td>{{ number_format($payroll->pending_salary_advance)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Approved Pension</td>
                    <td>{{ $payroll->approved_pension_scheme }}</td>
                    <td>{{ number_format($payroll->pending_approved_pension)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>PSRA</td>
                    <td>{{ $payroll->psra }}</td>
                    <td>{{ number_format($payroll->pending_psra)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Bank Loan</td>
                    <td>{{ $payroll->bank_loan }}</td>
                    <td>{{ number_format($payroll->pending_bank_loan)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Garnishment</td>
                    <td>{{ $payroll->garnishment }}</td>
                    <td>{{ number_format($payroll->pending_garnishment)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Damaged Goods</td>
                    <td>{{ $payroll->damaged_goods }}</td>
                    <td>{{ number_format($payroll->pending_damaged_goods)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td></td>
                    <td>Missing Goods</td>
                    <td>{{ $payroll->missing_goods }}</td>
                    <td>{{ number_format($payroll->pending_missing_goods)}}</td>
                    <td></td>
                </tr>
                <tr>
                    <td><strong>Total</strong></td>
                    <td></td>
                    <td></td>
                    <td></td>
                    @php
                        $total = $payroll->normal_hours_rate +  $payroll->overtime_rate +  $payroll->public_holiday_rate ?? 0;
                        $totalAmount = $payroll->paye +  $payroll->education_tax + $payroll->less_nis + $payroll->nht + $payroll->staff_loan +  $payroll->medical_insurance + $payroll->salary_advance + $payroll->approved_pension_scheme + $payroll->psra + $payroll->bank_loan + $payroll->missing_goods + $payroll->damaged_goods + $payroll->garnishment;
                    @endphp
                    <td><strong>{{ $total }}</strong></td>
                    <td></td>
                    <td><strong>{{ $totalAmount }}</strong></td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="5"></td>
                    <td>Net Salary</td>
                    <td>{{ $total - $totalAmount }}</td>
                    <td colspan="2"></td>
                </tr>
                <tr>
                    <td colspan="5"></td>
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
                    <td>{{ $payroll->gross_total }}</td>
                    <td>{{ $payroll->nis_total }}</td>
                    <td>{{ $payroll->paye_tax_total }}</td>
                    <td>{{ $payroll->education_tax_total }}</td>
                    <td>{{ $payroll->nht_total }}</td>
                </tr>
            </tbody>
        </table>
    </div>
</body>
</html>
    