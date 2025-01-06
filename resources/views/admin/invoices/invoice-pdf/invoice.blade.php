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
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Section */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .header-left {
            flex: 1;
            text-align: left;
        }

        .header-left img {
            max-height: 80px; /* You can adjust the size of the logo */
        }

        .header-center {
            flex: 2;
            text-align: center;
        }

        .header-center h3 {
            margin: 0;
            font-size: 32px;
        }

        .company-info {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .company-info-left,
        .company-info-center {
            width: 50%;
        }

        .company-info-left p,
        .company-info-center p {
            margin: 2px 0;
        }

        .company-info-center {
            /* text-align: center; */
            margin-bottom: 29px;
        }

        .company-details, .invoice-details {
            width: 100%;
            margin-bottom: 30px; /* Adjusted margin for spacing after content */
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
            margin-top: 30px; /* Increased margin for better gap */
            border-collapse: collapse;
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
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <img src="{{ asset('images/logo.png') }}" alt="Vanguard Security Ltd. Logo">
            </div>
            <div class="header-center">
                <h3>INVOICE</h3>
            </div>
        </div>

        <div class="company-info">
            <div class="company-info-left">
                <h3>Vanguard Security Ltd.</h3>
                <p>6 Eastwood Avenue, Kingston 10. Tel: 876-968-2413/4</p>
            </div>
            <div class="company-info-center">
                <p>GCT REG. NO. 000-973-777</p>
                <p>Email: <a href="mailto:accounts@vanguard-group.com">accounts@vanguard-group.com</a></p>
            </div>
        </div>

        <div class="invoice-details">
            <table>
                <tr>
                    <td><strong>Invoice No:</strong></td>
                    <td>{{ $invoice->invoice_code }}</td>
                </tr>
                <tr>
                    <td><strong>Service Period:</strong></td>
                    <td>{{ $invoice->start_date }}</td>
                </tr>
                <tr>
                    <td><strong>Invoice Date:</strong></td>
                    <td>{{ $invoice->invoice_date }}</td>
                </tr>
                <tr>
                    <td><strong>Due Date:</strong></td>
                    <td></td>
                </tr>
            </table>
        </div>

        <div class="company-details">
            <table>
                <tr>
                    <td><strong>Billed To:</strong></td>
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
            <div class="company-info-left">
                <p><strong>Customer Id:</strong></p>
                
            </div>
            <div class="Payment">
                <p><strong>Payment Terms:</strong> Net 7 Days</p>
            </div>
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
                        <td>{{ $item->total_hours }}</td>
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
                    <td colspan="2" class="total"><strong>GCT@15%</strong></td>
                    <td class="total" style="text-align: left;">${{ number_format($invoice->total * 0.15, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="4"></td>
                    <td colspan="2" class="total"><strong>Invoice Total JMD</strong></td>
                    <td class="total" style="text-align: left;">${{ number_format($invoice->total + $invoice->total * 0.15, 2) }}</td>
                </tr>
                
            </tbody>
        </table>

        <div class="payment-instructions" style="margin-top: 30px;">
            <p><strong>On payment, kindly indicate the invoice number that the payment applies to. Thank you.</strong></p>
            
            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <div class="banking-info" style="flex: 3;">
                    <strong>Banking Information:</strong>
                    <table style="width: 100%; margin-top: 10px;">
                        <tr>
                            <th>Bank Name</th>
                            <th>Branch</th>
                            <th>Type</th>
                            <th>Routing No. - Account No.</th>
                        </tr>
                        <tr>
                            <td>Scotia Bank</td>
                            <td>Scotiacentre</td>
                            <td>Current</td>
                            <td>00250765-000806384</td>
                        </tr>
                        <tr>
                            <td>Scotia Bank</td>
                            <td>Scotiacentre</td>
                            <td>Current</td>
                            <td>00250765-000077615</td>
                        </tr>
                        <tr>
                            <td>Sagicor</td>
                            <td>Tropical Plaza</td>
                            <td>Current</td>
                            <td>08101063-5501180779</td>
                        </tr>
                    </table>
                </div>
                <div style="flex: 1; margin-right: 20px;">
                    <label for="sign"><strong>Sign:</strong></label>
                    <div style="height: 50px; border-bottom: 1px solid #000; width: 200px;"></div>
                    <div for="sign"><strong>Manager</strong></div>
                </div>
            </div>
        </div>

        <div class="payment-instructions" style="margin-top: 30px;">
            <p><strong>Status of unpaid invoices as per below, please ignore thse if you have already paid these in last 7 days.</strong></p>
            
            <div style="display: flex; justify-content: space-between; margin-top: 30px;">
                <div class="banking-info" style="flex: 3;">
                    <table style="width: 100%; margin-top: 10px;">
                        <tr>
                            <th>Date</th>
                            <th>Invoice No</th>
                            <th>Invoice Amount</th>
                            <th>Status</th>
                        </tr>

                        <tr>
                            <td>30-Nov-24</td>
                            <td>12345</td>
                            <td></td>
                            <td>Overdue</td>
                        </tr>
                        <tr>
                            <td>30-Nov-24</td>
                            <td>12345</td>
                            <td></td>
                            <td>Overdue</td>
                        </tr>
                        <tr>
                            <td>30-Nov-24</td>
                            <td>12345</td>
                            <td></td>
                            <td>Overdue</td>
                        </tr>
                    </table>
                </div>
            </div>
            <div style="flex: 1; margin-right: 20px;">
            </div>
        </div>
    </div>
</body>
</html>
