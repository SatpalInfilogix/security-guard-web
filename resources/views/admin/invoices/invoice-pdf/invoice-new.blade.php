<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
        }
        .invoice {
            width: 100%;
            padding: 10px;
        }
        .invoice-header h1 {
            font-size: 24px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 4px;
            border: 1px solid #000;
            text-align: left;
        }
        .bank-info table {
            width: 100%;
        }
        .signature {
            /* margin-top: 20px; */
            text-align: right;
        }
        /* .unpaid-invoices {
            margin-top: 20px;
        } */
        .invoice-details {
            line-height: 10px;
        }
    </style>
</head>
<body>
    <div class="invoice">
        <div class="invoice-header">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 20%; border: none;">
                        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('assets/logo-admin.png'))) }}" alt="Logo" style="max-height: 50px;">
                    </td>
                    <td style="width: 60%; border: none; text-align: center;">
                        <h1>INVOICE</h1>
                    </td>
                    <td style="width: 20%; border: none;">
                    </td>
                </tr>
            </table>
        </div>
        <div class="invoice-details">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="border: none;">
                        <p><strong>Bill From:</strong></p>
                        <p><strong>Vanguard Security Ltd.</strong></p>
                        <p>6 Eastwood Avenue, Kingston 10.</p>
                        <p>Tel: 876-968-2413/4</p><br>
                        <p><strong>Service Period:</strong> {{ $invoice->start_date }} to {{ $invoice->end_date }}</p>
                        <p><strong>Customer ID:</strong> XXX-XXX-XXX</p>
                        <p><strong>GCT REG NO.:</strong> 000-973-777</p>
                        <p><strong>E-mail:</strong> accounts@vanguard-group.com</p>
                    </td>
                    <td style="border: none; text-align: right;">
                        <p><strong>Bill To:</strong></p>
                        <p><strong>{{ $invoice->clientSite->location_code }}</strong></p>
                        <p>{{ $invoice->clientSite->billing_address }}</p>
                        <p>Tel: **********</p><br>
                        <p><strong>Invoice No.:</strong> {{ $invoice->invoice_code }}</p>
                        <p><strong>Invoice Date:</strong>{{ $invoice->invoice_date }}</p>
                        <p><strong>Due Date:</strong> {{ $invoice->due_date }}</p>
                        <p><strong>Payment Terms:</strong> 7 Days</p>
                    </td>
                </tr>
            </table>
        </div>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>No. of Guard</th>
                    <th>Type of Guard</th>
                    <th>Service Description</th>
                    <th>No. of Hours</th>
                    <th>Rate per Hour</th>
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
                    <td colspan="6" style="text-align: right;"><strong>TOTAL</strong></td>
                    <td>${{ number_format($invoice->total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;"><strong>GCT @ {{ $invoice->clientSite->client->gct ?? '15' }}%</strong></td>
                    <td> ${{ number_format($invoice->total * ($invoice->clientSite->client->gct ?? 15) / 100, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="6" style="text-align: right;"><strong>TOTAL INVOICE JMD</strong></td>
                    <td>${{ number_format($invoice->total + $invoice->total * 0.15, 2) }}</td>
                </tr>
            </tbody>
        </table>
        
        <div class="bank-info">
            <p><strong>On payment, kindly indicate the invoice number that the payment applies to. Thank you.</strong></p>
            <p><strong>Banking Information:</strong></p>
            <table style="width: 80%;">
                <tr>
                    <th>Bank Name</th>
                    <th>Branch</th>
                    <th>Type</th>
                    <th>Routing No. – Account No.</th>
                </tr>
                <tbody>
                    <tr>
                        <td>{{ setting('branch_name1') ? setting('branch_name1') : 'Scotia Bank' }}</td>
                        <td>{{ setting('branch1') ? setting('branch1') : 'Scotia centre' }}</td>
                        <td>{{ setting('type1') ? setting('type1') : 'Current' }}</td>
                        <td>{{ setting('account_number1') ? setting('account_number1') : '00250765-000806384' }}</td>
                    </tr>
                    <tr>
                        <td>{{ setting('branch_name2') ? setting('branch_name2') : 'Scotia Bank' }}</td>
                        <td>{{ setting('branch2') ? setting('branch2') : 'Scotia centre' }}</td>
                        <td>{{ setting('type2') ? setting('type2') : 'Current' }}</td>
                        <td>{{ setting('account_number2') ? setting('account_number2') : '00250765-000806384' }}</td>
                    </tr>
                    <tr>
                        <td>{{ setting('branch_name3') ? setting('branch_name3') : 'Sagicor' }}</td>
                        <td>{{ setting('branch3') ? setting('branch1') : 'Scotia centre' }}</td>
                        <td>{{ setting('type3') ? setting('type3') : 'Current' }}</td>
                        <td>{{ setting('account_number3') ? setting('account_number3') : '00250765-000806384' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div class="unpaid-invoices">
            <div class="signature">
                <p><strong>Signature:</strong></p>
                <p>______________________</p>
                <p>Manager</p>
            </div>
            <p><strong>Status of unpaid invoices as per below, please ignore this if you have already paid these in the last 7 days.</strong></p>
            <table style="width: 70%;">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Invoice No.</th>
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
</body>
</html>