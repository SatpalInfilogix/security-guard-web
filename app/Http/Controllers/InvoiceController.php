<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use Barryvdh\DomPDF\Facade\PDF;
use App\Models\PayrollDetail;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('admin.invoices.index');
    }

    public function getInvoice(Request $request)
    {
        $invoices = Invoice::with('clientSite');
    
        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $invoices->where(function($query) use ($searchValue) {
                $query->where('invoice_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('invoice_date', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('clientSite', function($q) use ($searchValue) {
                        $q->where('location_code', 'like', '%' . $searchValue . '%');
                    });
            });
        }
    
        $totalRecords = Invoice::count();
    
        $filteredRecords = $invoices->count();
    
        $length = $request->input('length', 10);
        $start = $request->input('start', 0);
    
        $invoices = $invoices->skip($start)->take($length)->get();
        $data = $invoices->map(function ($invoice) {
            return [
                'id'           => $invoice->id,
                'invoice_code' => $invoice->invoice_code,
                'invoice_date' => $invoice->invoice_date,
                'location_code' => $invoice->clientSite ? $invoice->clientSite->location_code : null,
                'total_amount' => $invoice->total_amount,
                'status'       => $invoice->status
            ];
        });
    
        $response = [
            'draw' => $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ];
    
        return response()->json($response);
    }
    
    public function downloadPdf($invoiceId)
    {
        $invoice = Invoice::with('clientSite')->where('id', $invoiceId)->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }

        $invoice['items'] = InvoiceDetail::where('invoice_id', $invoiceId)->with('guardType')->get();
        $invoice['total'] = InvoiceDetail::where('invoice_id', $invoiceId)->sum('invoice_amount');

        $pdf = PDF::loadView('admin.invoices.invoice-pdf.invoice', ['invoice' => $invoice]);
        return $pdf->download('invoice-' . $invoiceId . '.pdf');

        // return view('admin.invoices.invoice-pdf.invoice', compact('invoice'));
    }

// public function exportCsv(Request $request)
// {
//     $invoiceId = $request->input('invoice_id');
//     $invoice = Invoice::where('id', $invoiceId)->first();

//     if (!$invoice) {
//         return response()->json(['error' => 'Invoice not found'], 404);
//     }

//     // Get payroll details between the invoice start and end date
//     $payrollDetails = PayrollDetail::whereBetween('date', [
//         Carbon::parse($invoice->start_date)->format('Y-m-d'),
//         Carbon::parse($invoice->end_date)->format('Y-m-d')
//     ])->get();

//     // Group payroll details by client site and guard type, and also by hours type (Normal, Time & 1/2, Double)
//     $aggregatedData = $payrollDetails->groupBy('client_site_id')->map(function ($clientGroup, $clientSiteId) {
//         return $clientGroup->groupBy('guard_type_id')->map(function ($guardGroup, $guardTypeId) use ($clientSiteId) {
//             return $guardGroup->groupBy('hours_type')->map(function ($hoursGroup, $hoursType) use ($guardGroup) {
//                 return [
//                     'guard_type' => $guardGroup->first()->guard_type, // Assuming all guards in the group have the same type
//                     'hours_type' => $hoursType,
//                     'no_of_guards_billed' => $guardGroup->pluck('guard_id')->unique()->count(),
//                     'hours_billed' => $hoursGroup->sum('normal_hours') + $hoursGroup->sum('overtime') + $hoursGroup->sum('public_holiday'), // Aggregate all hours types
//                     'rate' => 749, // Assuming rate is a constant, adjust as needed
//                     'invoice_amount' => $hoursGroup->sum('normal_hours') * 749, // Assuming rate is multiplied by hours billed
//                     'no_of_guards_paid' => $guardGroup->pluck('guard_id')->unique()->count(), // Assuming same guards are paid
//                     'hours_paid' => $hoursGroup->sum('normal_hours') + $hoursGroup->sum('overtime') + $hoursGroup->sum('public_holiday'), // Same logic for paid hours
//                     'hourly_rate' => 479, // Adjust accordingly
//                     'salary_paid' => $hoursGroup->sum('normal_hours') * 479, // Same logic for salary paid
//                     'gross_margin' => ($hoursGroup->sum('normal_hours') * 749) - ($hoursGroup->sum('normal_hours') * 479), // Invoice Amount - Salary Paid
//                     'cost_absorbed' => $hoursGroup->sum('overtime') * 479, // Assuming cost absorbed is overtime * salary rate
//                 ];
//             });
//         });
//     });

//     // Set headers for CSV response
//     $headers = [
//         'Content-Type' => 'text/csv',
//         'Content-Disposition' => 'attachment; filename="payroll_details.csv"',
//         'Pragma' => 'no-cache',
//         'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
//         'Expires' => '0',
//     ];

//     // Create a streamed response to generate the CSV content
//     $response = new StreamedResponse(function () use ($aggregatedData, $invoice) {
//         $handle = fopen('php://output', 'w');

//         // Add CSV headers
//         fputcsv($handle, [
//             'Invoice Date',
//             'Invoice Number',
//             'Location Code',
//             'Location Name',
//             'Guard Type',
//             'Hours Type',
//             'No of Guards (Billed)',
//             'Hours Billed',
//             'Rate',
//             'Invoice Amount',
//             'No of Guards (Paid)',
//             'Hours Paid',
//             'Hourly Rate',
//             'Salary Paid',
//             'Gross Margin',
//             'Cost Absorbed'
//         ]);

//         // Iterate over aggregated data and write to CSV
//         foreach ($aggregatedData as $clientSiteId => $guardTypes) {
//             foreach ($guardTypes as $guardTypeId => $hoursTypes) {
//                 foreach ($hoursTypes as $hoursType => $data) {
//                     fputcsv($handle, [
//                         Carbon::parse($invoice->invoice_date)->format('d/m/Y'), // Invoice Date
//                         $invoice->invoice_number, // Invoice Number
//                         $invoice->location_code, // Location Code
//                         $invoice->location_name, // Location Name
//                         $data['guard_type'], // Guard Type
//                         $data['hours_type'], // Hours Type (Normal, Time & 1/2, Double)
//                         $data['no_of_guards_billed'], // No of Guards Billed
//                         $data['hours_billed'], // Hours Billed
//                         $data['rate'], // Rate
//                         '$ ' . number_format($data['invoice_amount'], 2), // Invoice Amount
//                         $data['no_of_guards_paid'], // No of Guards Paid
//                         $data['hours_paid'], // Hours Paid
//                         '$ ' . number_format($data['hourly_rate'], 2), // Hourly Rate
//                         '$ ' . number_format($data['salary_paid'], 2), // Salary Paid
//                         '$ ' . number_format($data['gross_margin'], 2), // Gross Margin
//                         '$ ' . number_format($data['cost_absorbed'], 2), // Cost Absorbed
//                     ]);
//                 }
//             }
//         }

//         fclose($handle);
//     }, 200, $headers);

//     return $response;
// }

}
