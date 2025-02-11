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
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Section */
        .header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
            background-color: #66a2eb;
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
            background-color: #ddebfb;
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
            /* padding-top: 100px; */
            background-color: #66a2eb38;
        }
        td.security {
            color: #66a2eb;
        }
        .table tbody tr:nth-child(odd) {
        background-color: #f9f9f9; /* Light gray for odd rows */
    }

    .table tbody tr:nth-child(even) {
        background-color: #ddebfb; /* White for even rows */
    }

    .table th {
        background-color: #ddebfb; 
    }
    .table th, .table td {
        border: none; /* Remove table borders */
    }
    p.instruction {
        color: #66a2ed;
    }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="header-left">
                <img src="https://guard.vsljamaica.com/uploads/logo/677b7fb67adc6.png" alt="Logo">
            </div>
            <div class="header-center">
                <h3>INVOICE</h3>
            </div>
        </div>

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
    </div>
</body>
</html>
    