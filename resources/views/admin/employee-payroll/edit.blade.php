@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-0 font-size-18">Update Employee Payroll </h4>

                        <div class="page-title-right">
                            <button class="btn btn-primary" onclick="downloadInvoicePdf({{ $employeePayroll->id }})">
                                Payslip <i class="fas fa-file-pdf"></i>
                            </button>
                            <a href="{{ route('employee-payroll.index', [
                                'year' => request('year'),
                                'month' => request('month'),
                                'page' => request('page', 1),
                            ]) }}"
                                class="btn btn-primary">
                                <i class="bx bx-arrow-back"></i> Back to Employee Payroll
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <!-- End page title -->

            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <!-- Payslip Header -->
                            <input type="hidden" value="{{ $employeePayroll->id }}" id="payroll_id">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6><strong>Employee Name: </strong> {{ $employeePayroll->user->first_name }}
                                        {{ $employeePayroll->user->surname }}</h6>
                                    <h6><strong>Department: </strong> operations</h6>
                                    <h6><strong>Category: </strong> N/A</h6>
                                </div>
                                <div class="col-md-6 text-md-right">
                                    <h6><strong>NIS No:
                                        </strong>{{ $employeePayroll->user->guardAdditionalInformation->nis }}</h6>
                                    <h6><strong>Employee TRN: </strong>
                                        {{ $employeePayroll->user->guardAdditionalInformation->trn }}</h6>
                                    <h6><strong>Payroll Period: </strong> {{ $employeePayroll->start_date }} to
                                        {{ $employeePayroll->end_date }}</h6>
                                    <h6><strong>Payroll No: </strong>{{ $twentyTwoDayCount->id }}</h6>
                                    <h6><strong>Date of Processing:
                                        </strong>{{ $employeePayroll->created_at->format('d-M-Y') }}</h6>
                                </div>
                            </div>

                            <!-- Earnings and Deductions Table -->
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th style="width: 14%;">Earnings</th>
                                            <th style="width: 13%;">Monthly Salary</th>
                                            <th style="width: 3%;">Units</th>
                                            <th style="width: 11%;">Total Salary</th>
                                            <th style="width: 15%;">Deductions</th>
                                            <th style="width: 15%;">Amount</th>
                                            <th style="width: 10%;">Balance</th>
                                            <th style="width: 18%;">Employer contribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Gross Earnings</td>
                                            <td>{{ formatAmount($employeePayroll->day_salary) }}</td>
                                            <td>{{ $employeePayroll->normal_days - $employeePayroll->leave_not_paid }}</td>
                                            <td>{{ formatAmount($employeePayroll->normal_salary) }}</td>
                                            <td>PAYE</td>
                                            <td><input type="text" class="form-control editable" id="paye"
                                                    value="{{ formatAmount($employeePayroll->paye) }}"></td>
                                            <td></td>
                                            {{-- <td id="normal_hours_rate">{{ $employeePayroll->normal_hours_rate }}</td> --}}
                                        </tr>
                                        <tr>
                                            <td>Leave Paid</td>
                                            <td></td>
                                            <td>{{ $employeePayroll->leave_paid }}</td>
                                            <td>-</td>
                                            <td>Ed Tax</td>
                                            <td id="education_tax">{{ formatAmount($employeePayroll->education_tax) }}</td>
                                            <td></td>
                                            <td>{{ formatAmount($employeePayroll->employer_eduction_tax) }}</td>
                                            {{-- <td id="overtime_rate">{{ $employeePayroll->overtime_rate }}</td> --}}
                                        </tr>
                                        <tr>
                                            <td>Employee Allowance</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ formatAmount($employeeAllowance) }}</td>
                                            <td>NIS</td>
                                            <td id="less_nis">{{ formatAmount($employeePayroll->nis) }}</td>
                                            <td></td>
                                            <td>{{ formatAmount($employeePayroll->employer_contribution_nis_tax) }}</td>
                                            {{-- <td id="public_holiday_rate">{{ $employeePayroll->public_holiday_rate }}</td> --}}
                                        </tr>
                                        <tr>
                                            @if ($employeePayroll->pending_leave_balance > 0)
                                                <td>Pending Balance</td>
                                                <td></td>
                                                <td>{{ formatAmount($employeePayroll->pending_leave_balance) }}</td>
                                                <td>{{ formatAmount($employeePayroll->pending_leave_amount) }}</td>
                                            @else
                                                <td>Leave not Paid</td>
                                                <td></td>
                                                <td>{{ $employeePayroll->leave_not_paid }}</td>
                                                <td></td>
                                            @endif
                                            <td>NHT</td>
                                            <td id="nht">{{ formatAmount($employeePayroll->nht) }}</td>
                                            <td></td>
                                            <td>{{ formatAmount($employeePayroll->employer_contribution_nht_tax) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Employee Overtime</td>
                                            <td></td>
                                            <td>{{ $overtimeHours }}</td>
                                            <td>{{ formatAmount($employeePayroll->overtime_income_total) }}</td>
                                            <td>Heart</td>
                                            <td></td>
                                            <td></td>
                                            <td>{{ formatAmount($employeePayroll->heart) }}</td>
                                        </tr>
                                        <tr>
                                            <td>Pay in Lieu of Employee</td>
                                            <td></td>
                                            <td>{{ $encashLeaveDays }}</td>
                                            <td>{{ formatAmount($encashLeaveAmount) }}</td>
                                            <td>Staff Loan</td>
                                            <td> <input type="text" class="form-control editable" id="staff_loan"
                                                    value="{{ formatAmount($employeePayroll->staff_loan) }}" readonly></td>
                                            <td id="balance">{{ $employeePayroll->pending_staff_loan == 0 ? '' : number_format($employeePayroll->pending_staff_loan) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Medical Ins</td>
                                            <td><input type="text" class="form-control editable" id="medical_insurance"
                                                    value="{{ formatAmount($employeePayroll->medical_insurance) }}"
                                                    readonly>
                                            <td>{{ $employeePayroll->pending_medical_insurance == 0 ? '' : number_format($employeePayroll->pending_medical_insurance) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Salary Advance</td>
                                            <td>{{ formatAmount($employeePayroll->salary_advance) }}</td>
                                            <td>{{ $employeePayroll->pending_salary_advance == 0 ? '' : number_format($employeePayroll->pending_salary_advance) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Approved Pension</td>
                                            <td>{{ formatAmount($employeePayroll->approved_pension_scheme) }}</td>
                                            <td>{{ $employeePayroll->pending_approved_pension == 0 ? '' : number_format($employeePayroll->pending_approved_pension) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>PSRA</td>
                                            <td>{{ formatAmount($employeePayroll->psra) }}</td>
                                            <td>{{ $employeePayroll->pending_psra == 0 ? '' : number_format($employeePayroll->pending_psra) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Bank Loan</td>
                                            <td>{{ formatAmount($employeePayroll->bank_loan) }}</td>
                                            <td>{{ $employeePayroll->pending_bank_loan == 0 ? '' : number_format($employeePayroll->pending_bank_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Garnishment</td>
                                            <td>{{ formatAmount($employeePayroll->garnishment) }}</td>
                                            <td>{{ $employeePayroll->pending_garnishment == 0 ? '' : number_format($employeePayroll->pending_garnishment) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Damaged Goods</td>
                                            <td>{{ formatAmount($employeePayroll->damaged_goods) }}</td>
                                            <td>{{ $employeePayroll->pending_damaged_goods == 0 ? '' : number_format($employeePayroll->pending_damaged_goods) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Missing Goods</td>
                                            <td>{{ formatAmount($employeePayroll->missing_goods) }}</td>
                                            <td>{{ $employeePayroll->pending_missing_goods == 0 ? '' : number_format($employeePayroll->pending_missing_goods) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>NCB Loan</td>
                                            <td>{{ formatAmount($employeePayroll->ncb_loan) }}</td>
                                            <td>{{ $employeePayroll->pending_ncb_loan == 0 ? '' : number_format($employeePayroll->pending_ncb_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>C&amp;WJ Credit Union Loan</td>
                                            <td>{{ formatAmount($employeePayroll->cwj_credit_union_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Edu Com Co-op Loan</td>
                                            <td>{{ formatAmount($employeePayroll->edu_com_coop_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>National Housing Trust Mortgage Loan</td>
                                            <td>{{ formatAmount($employeePayroll->nht_mortgage_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Jamaica National Bank Loan</td>
                                            <td>{{ formatAmount($employeePayroll->jn_bank_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Sagicor Bank Loan</td>
                                            <td>{{ formatAmount($employeePayroll->sagicor_bank_loan) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Health Insurance</td>
                                            <td>{{ formatAmount($employeePayroll->health_insurance) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Life Insurance</td>
                                            <td>{{ formatAmount($employeePayroll->life_insurance) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Overpayment</td>
                                            <td>{{ formatAmount($employeePayroll->overpayment) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="3"></td>
                                            <td></td>
                                            <td>Training</td>
                                            <td>{{ formatAmount($employeePayroll->training) }}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total</strong></td>
                                            <td></td>
                                            <td></td>
                                            @php
                                                $total =
                                                    ($employeePayroll->gross_salary ?? 0) + ($employeeAllowance ?? 0);
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
                                                    $employeePayroll->garnishment +
                                                    $employeePayroll->ncb_loan +
                                                    $employeePayroll->cwj_credit_union_loan +
                                                    $employeePayroll->edu_com_coop_loan +
                                                    $employeePayroll->nht_mortgage_loan +
                                                    $employeePayroll->jn_bank_loan +
                                                    $employeePayroll->sagicor_bank_loan +
                                                    $employeePayroll->health_insurance +
                                                    $employeePayroll->life_insurance +
                                                    $employeePayroll->overpayment +
                                                    $employeePayroll->training;
                                            @endphp
                                            <td><strong>{{ formatAmount($total) }}</strong></td>
                                            <td></td>
                                            <td><strong id="totalDeductions">{{ formatAmount($totalAmount) }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"></td>
                                            <td>Net Salary</td>
                                            <td id="netSalary">{{ formatAmount($total - $totalAmount) }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"></td>
                                            <td>BNS Account</td>
                                            <td id="bnsAccount">{{ formatAmount($total - $totalAmount) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Year to Date Summary -->
                            <div class="table-responsive mt-4">
                                <h5><strong>Year to Date</strong></h5>
                                <table class="table table-bordered">
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
                                            <td>{{ formatAmount($employeePayroll->gross_total + $employeeAllowance ?? 0) }}
                                            </td>
                                            <td>{{ formatAmount($employeePayroll->nis_total) }}</td>
                                            <td id="payeTax">{{ formatAmount($employeePayroll->paye_tax_total) }}</td>
                                            <td>{{ formatAmount($employeePayroll->education_tax_total) }}</td>
                                            <td>{{ formatAmount($employeePayroll->nht_total) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('.editable').on('input', function() {
                calculateTotals();
            });

            function calculateTotals() {
                let normal_hours_rate = parseFloat($('#normal_hours_rate').text()) || 0;
                let overtime_rate = parseFloat($('#overtime_rate').text()) || 0;
                let public_holiday_rate = parseFloat($('#public_holiday_rate').text()) || 0;
                let paye = parseFloat($('#paye').val()) || 0;
                let education_tax = parseFloat($('#education_tax').text()) || 0;
                let less_nis = parseFloat($('#less_nis').text()) || 0;
                let nht = parseFloat($('#nht').text()) || 0;
                let staff_loan = parseFloat($('#staff_loan').val()) || 0;
                let medical_insurance = parseFloat($('#medical_insurance').val()) || 0;
                let payrollId = $('#payroll_id').val();

                let totalEarnings = normal_hours_rate + overtime_rate + public_holiday_rate;
                let totalDeductions = paye + education_tax + less_nis + nht + staff_loan + medical_insurance;
                let netSalary = totalEarnings - totalDeductions;
                let balance = staff_loan * 5;

                $('#totalDeductions').text(totalDeductions.toFixed(2));
                $('#netSalary').text(netSalary.toFixed(2));
                $('#bnsAccount').text(netSalary.toFixed(2));
                $('#payeTax').text(paye.toFixed(2));
                $('#balance').text(balance.toFixed(2));

                updateDatabase(payrollId, paye, staff_loan, medical_insurance);
            }

            function updateDatabase(payrollId, paye, staff_loan, medical_insurance) {
                $.ajax({
                    url: `{{ route('payrolls.update', ':id') }}`.replace(':id', payrollId),
                    type: 'PUT',
                    data: {
                        '_token': '{{ csrf_token() }}',
                        paye: paye,
                        staff_loan: staff_loan,
                        medical_insurance: medical_insurance
                    },
                    success: function(response) {
                        if (response.success == true) {
                            // window.location.href = "{{ route('payrolls.index') }}";
                        }
                    },
                    error: function(error) {
                        console.log('Error updating salary details: ', error);
                    }
                });
            }

            window.downloadInvoicePdf = function(invoiceId) {
                window.location.href = "{{ route('employee-payroll.download-pdf', ':invoiceId') }}".replace(
                    ':invoiceId', invoiceId);
            };
        });
    </script>
@endsection
