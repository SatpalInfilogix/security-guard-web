@extends('layouts.app')

@section('content')
    <div class="page-content">
        <div class="container-fluid">
            <!-- Start page title -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="page-title-box d-sm-flex align-items-center justify-content-between">
                        <h4 class="mb-0 font-size-18">Payslip</h4>

                        <div class="page-title-right">
                            <a href="{{ route('payrolls.index') }}" class="btn btn-primary"><i class="bx bx-arrow-back"></i> Back to Payroll</a>
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
                            <input type="hidden" value="{{$payroll->id}}" id="payroll_id"> 
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6><strong>Employee Name: </strong> {{ $payroll->user->first_name }} {{ $payroll->user->last_name }}</h6>
                                    <h6><strong>Department: </strong> N/A</h6>
                                    <h6><strong>Category: </strong> N/A</h6>
                                </div>
                                <div class="col-md-6 text-md-right">
                                    <h6><strong>NIS No: </strong>{{ $payroll->user->guardAdditionalInformation->nis }}</h6>
                                    <h6><strong>Employee TRN: </strong> {{ $payroll->user->guardAdditionalInformation->trn }}</h6>
                                    <h6><strong>Payroll Period: </strong> {{ $payroll->start_date }} to {{ $payroll->end_date }}</h6>
                                    <h6><strong>Payroll No: </strong>{{ $fortnightDayCount }}</h6>
                                    <h6><strong>Date of Processing: </strong> 2-Feb-25</h6>
                                </div>
                            </div>

                            <!-- Earnings and Deductions Table -->
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Gross Earnings</th>
                                            <th>Rate</th>
                                            <th>Units</th>
                                            <th>Rate per Unit</th>
                                            <th>Total</th>
                                            <th>Deductions</th>
                                            <th>Amount</th>
                                            <th rowspan="2" class="align-middle text-center">Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Megamart Waterloo (Normal)</td>
                                            <td>Normal</td>
                                            <td>{{ $payroll->normal_hours }}</td>
                                            <td>-</td>
                                            <td id="normal_hours_rate">{{ $payroll->normal_hours_rate }}</td>
                                            <td>PAYE</td>
                                            <td><input type="text" class="form-control editable" id="paye" value="{{ $payroll->paye }}"></td>
                                        </tr>
                                        <tr>
                                            <td>Megamart Waterloo (Overtime)</td>
                                            <td>Overtime</td>
                                            <td>{{ $payroll->overtime }}</td>
                                            <td>-</td>
                                            <td id="overtime_rate">{{ $payroll->overtime_rate }}</td>
                                            <td>Ed Tax</td>
                                            <td id="education_tax">{{ $payroll->education_tax }}</td>
                                        </tr>
                                        <tr>
                                            <td>Megamart Waterloo (Public Holiday)</td>
                                            <td>Public Holiday</td>
                                            <td>{{ $payroll->public_holidays }}</td>
                                            <td>-</td>
                                            <td id="public_holiday_rate">{{ $payroll->public_holiday_rate }}</td>
                                            <td>NIS</td>
                                            <td id="less_nis">{{ $payroll->less_nis }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"></td>
                                            <td></td>
                                            <td>NHT</td>
                                            <td id="nht">{{ $payroll->nht }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"></td>
                                            <td></td>
                                            <td>Staff Loan</td>
                                            <td> <input type="text" class="form-control editable" id="staff_loan" value="{{ $payroll->staff_loan }}"></td>
                                            <td id="balance">{{ number_format($payroll->staff_loan * 5 , 2)}}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="4"></td>
                                            <td></td>
                                            <td>Medical Ins</td>
                                            <td><input type="text" class="form-control editable" id="medical_insurance" value="{{ $payroll->medical_insurance }}">
                                            </td></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Total</strong></td>
                                            <td></td>
                                            <td></td>
                                            <td></td>
                                            @php
                                                $total = $payroll->normal_hours_rate +  $payroll->overtime_rate +  $payroll->public_holiday_rate ?? 0;
                                                $totalAmount = $payroll->paye +  $payroll->education_tax + $payroll->less_nis + $payroll->nht + $payroll->staff_loan +  $payroll->medical_insurance;
                                            @endphp
                                            <td><strong>{{ $total }}</strong></td>
                                            <td></td>
                                            <td><strong id="totalDeductions">{{ $totalAmount }}</strong></td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"></td>
                                            <td>Net Salary</td>
                                            <td id="netSalary">{{ $total - $totalAmount }}</td>
                                        </tr>
                                        <tr>
                                            <td colspan="5"></td>
                                            <td>BNS Account</td>
                                            <td id="bnsAccount">{{ $total - $totalAmount }}</td>
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
                                            <td>{{ $total }}</td>
                                            <td>{{ $payroll->less_nis }}</td>
                                            <td id="payeTax">{{ $payroll->paye }}</td>
                                            <td>{{ $payroll->education_tax }}</td>
                                            <td>{{ $payroll->nht }}</td>
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
                        if(response.success == true) {
                            // window.location.href = "{{ route('payrolls.index') }}";
                        }
                    },
                    error: function(error) {
                        console.log('Error updating salary details: ', error);
                    }
                });
            }
        });
    </script>
    
    
    
@endsection