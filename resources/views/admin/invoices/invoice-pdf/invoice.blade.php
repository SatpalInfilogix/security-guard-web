<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $invoice->invoice_code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 100%;
        }

        .header {
            background-color: #66a2eb;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            background-color: #ddebfb;
        }

        .company-details, .invoice-details {
            width: 100%;
            margin-bottom: 30px;
            overflow: hidden;
        }

        .company-details td, .invoice-details td {
            padding: 5px 10px;
        }

        .invoice-details {
            width: 50%;
            float: right;
        }

        .invoice-details td {
            text-align: right;
        }

        .clear {
            clear: both;
        }

        .table {
            width: 100%;
            margin-top: 30px;
            border-collapse: collapse;
        }

        .gct-total {
            color: #66a2eb;
        }

        .billed-to {
            color: #66a2eb;
        }

        .table, .table th, .table td {
            border: 1px solid #000;
        }

        .table th, .table td {
            padding: 10px;
            text-align: left;
        }

        .total {
            text-align: right;
        }
        th {
            text-align: left;
        }

        .company-details {
            background-color: #66a2eb38;
        }
        td.security {
            color: #66a2eb;
        }
        .table tbody tr:nth-child(odd) {
        background-color: #f9f9f9;
    }

    .table tbody tr:nth-child(even) {
        background-color: #ddebfb;
    }

    .table th {
        background-color: #ddebfb; 
    }
    .table th, .table td {
        border: none;
    }
    p.instruction {
        color: #66a2ed;
    }
    </style>
</head>
<body>
    <div class="container">
        <table class="header" style="border-collapse: collapse; width: 100%;">
            <tr>
                <td style="border: none;">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/logo-admin.png'))) }}" alt="Logo" style="max-height: 50px;">
                </td>
                <td style="border: none;">
                    <h2 style="margin: 0; font-size: 25px;">INVOICE</h2>
                </td>
            </tr>
        </table>

        <div class="company-info">
            <table width="100%">
                <tr>
                    <td class="security" style="width: 50%; vertical-align: top;">
                        <h3>Vanguard Security Ltd.</h3>
                        <p>6 Eastwood Avenue, Kingston 10. Tel: 876-968-2413/4</p>
                    </td>
                    <td style="width: 50%; vertical-align: top; text-align: right;">
                        <p>GCT REG. NO. 000-973-777</p>
                        <p>Email: <a href="mailto:accounts@vanguard-group.com">accounts@vanguard-group.com</a></p>
                    </td>
                </tr>
            </table>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td><strong>Invoice No:</strong></td>
                    <td>{{ $invoice->invoice_code }}</td>
                </tr>
                <tr>
                    <td><strong>Service Period:</strong></td>
                    <td>{{ $invoice->start_date }} to {{ $invoice->end_date }}</td>
                </tr>
                <tr>
                    <td><strong>Invoice Date:</strong></td>
                    <td>{{ $invoice->invoice_date }}</td>
                </tr>
                <tr>
                    <td><strong>Due Date:</strong></td>
                    <td>{{ $invoice->due_date }}</td>
                </tr>
            </table>
        </div>
        <div style="height: 130px;"></div> 
        
        <div class="company-details">
            <table>
                <tr>
                    <td class="billed-to"><strong>Billed To:</strong></td>
                </tr>
                <tr>
                    <td>{{ $invoice->clientSite->location_code }}</td>
                </tr>
                <tr>
                    <td>{{ $invoice->clientSite->billing_address }}</td>
                </tr>
            </table>
        </div>

        <div class="company-info">
            <table style="width: 100%; margin-top: 20px;" >
                <tr>
                    <td style="width: 50%;"><strong>Customer Id:</strong></td>
                    <td style="width: 50%;"><strong>Payment Terms:</strong>Net 7 Days</td>
                </tr>
            </table>
        </div>

        <div class="clear"></div>

        <!-- Items Section -->
        <table class="table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th></th>
                    <th></th>
                    <th>Service Description</th>
                    <th>No Of Hours</th>
                    <th>Rate Per Hour</th>
                    <th>Total Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->date }}</td>
                        <td>{{ $item->no_of_guards }}</td>
                        <td>{{ $item->guardType->guard_type }}</td>
                        <td>{{ $item->hours_type }}</td>
                        <td>{{ convertToHoursAndMinutes($item->total_hours) }}</td>
                        <td>${{ number_format($item->rate, 2) }}</td>
                        <td>${{ number_format($item->invoice_amount, 2) }}</td>
                    </tr>
                @endforeach
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2" class="total"><strong>Total</strong></td>
                    <td class="total" style="text-align: left;">${{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2" class="total gct-total"><strong>GCT @ {{ $invoice->clientSite->client->gct ?? '15' }}%</strong></td>
                    <td class="total" style="text-align: left;"> ${{ number_format($invoice->total * ($invoice->clientSite->client->gct ?? 15) / 100, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2" class="total"><strong>Invoice Total JMD</strong></td>
                    <td class="total" style="text-align: left;">${{ number_format($invoice->total + $invoice->total * 0.15, 2) }}</td>
                </tr>
                
            </tbody>
        </table>

        <div class="payment-instructions" style="margin-top: 30px;">
            <p class="instruction"><strong>On payment, kindly indicate the invoice number that the payment applies to. Thank you.</strong></p>
            
            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                    <tr>
                        <td style="width: 75%; vertical-align: top; padding-right: 20px;">
                            <strong>Banking Information:</strong>
                            <table style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Bank Name</th>
                                        <th>Branch</th>
                                        <th>Type</th>
                                        <th>Routing No. - Account No.</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ setting('branch_name1') ? setting('branch_name1') : '' }}</td>
                                        <td>{{ setting('branch1') ? setting('branch1') : '' }}</td>
                                        <td>{{ setting('type1') ? setting('type1') : '' }}</td>
                                        <td>{{ setting('account_number1') ? setting('account_number1') : '' }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ setting('branch_name2') ? setting('branch_name2') : '' }}</td>
                                        <td>{{ setting('branch2') ? setting('branch2') : '' }}</td>
                                        <td>{{ setting('type2') ? setting('type2') : '' }}</td>
                                        <td>{{ setting('account_number2') ? setting('account_number2') : '' }}</td>
                                    </tr>
                                    <tr>
                                        <td>{{ setting('branch_name3') ? setting('branch_name3') : '' }}</td>
                                        <td>{{ setting('branch3') ? setting('branch1') : '' }}</td>
                                        <td>{{ setting('type3') ? setting('type3') : '' }}</td>
                                        <td>{{ setting('account_number3') ? setting('account_number3') : '' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
            
                        <!-- Right Column: Signature -->
                        <td style="width: 25%; vertical-align: top; padding-left: 20px;">
                            <strong>Sign:</strong>
                            <div style="height: 50px; border-bottom: 1px solid #000; width: 200px; margin-top: 10px;"></div>
                            <div style="margin-top: 10px;"><strong>Manager</strong></div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="payment-instructions" style="margin-top: 30px;">
            <p><strong>Status of unpaid invoices as per below, please ignore thse if you have already paid these in last 7 days.</strong></p>
            
            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <div class="banking-info" style="flex: 3;">
                    <table style="width: 100%; margin-top: 10px;">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Invoice No</th>
                                <th>Invoice Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($invoicesList as $list)
                                <tr>
                                    <td>{{ $list->invoice_date }}</td>
                                    <td>{{ $list->invoice_code }}</td>
                                    <td>${{ number_format($list->total_amount, 2) }}</td>
                                    @if($invoice->invoice_code == $list->invoice_code)
                                        <td> Due </td>
                                    @else
                                        <td>Overdue </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div style="flex: 1; margin-right: 20px;">
            </div>
        </div>
    </div>
</body>
</html>
