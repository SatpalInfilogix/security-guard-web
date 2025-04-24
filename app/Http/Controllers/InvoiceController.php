<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\InvoiceDetail;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\ClientRateMaster;
use Barryvdh\DomPDF\Facade\PDF;
use App\Models\PayrollDetail;
use App\Models\FortnightDates;
use App\Models\RateMaster;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use Illuminate\Support\Facades\Gate;

class InvoiceController extends Controller
{
    public function index()
    {
        if (!Gate::allows('view invoice')) {
            abort(403);
        }
        $clients = Client::latest()->get();

        return view('admin.invoices.index', compact('clients'));
    }

    public function getInvoice(Request $request)
    {
        $invoices = Invoice::with('clientSite');
        if ($request->has('client_ids') && !empty($request->client_ids)) {
            $clientIds = $request->client_ids;
            $invoices->whereHas('clientSite', function ($query) use ($clientIds) {
                $query->whereIn('client_id', $clientIds);
            });
        }

        if ($request->has('client_site_ids') && !empty($request->client_site_ids)) {
            $clientSiteIds = $request->client_site_ids;
            $invoices->whereHas('clientSite', function ($query) use ($clientSiteIds) {
                $query->whereIn('id', $clientSiteIds);
            });
        }

        if ($request->has('date') && !empty($request->date)) {
            $searchDate = Carbon::parse($request->date);
            $invoices->whereDate('start_date', '<=', $searchDate)->whereDate('end_date', '>=', $searchDate);
        }

        if ($request->has('paid_status') && !empty($request->paid_status)) {
            $invoices->where('status', $request->paid_status);
        }

        if ($request->has('search') && !empty($request->search['value'])) {
            $searchValue = $request->search['value'];
            $invoices->where(function ($query) use ($searchValue) {
                $query->where('invoice_code', 'like', '%' . $searchValue . '%')
                    ->orWhere('invoice_date', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('clientSite', function ($q) use ($searchValue) {
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
                'total_amount' => formatAmount($invoice->total_amount),
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

    public function updateStatus(Request $request)
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'status' => 'required|in:Paid,Unpaid',
        ]);

        $invoice = Invoice::find($request->invoice_id);
        $invoice->status = $request->status;
        $invoice->save();

        return response()->json(['success' => true]);
    }

    public function downloadPdf($invoiceId)
    {
        $invoice = Invoice::with('clientSite', 'clientSite.client')->where('id', $invoiceId)->first();
        if (!$invoice) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }
        $clientId = $invoice->clientSite->client->id;
        $clientSites = ClientSite::where('client_id', $clientId)->get();
        $previousInvoices = Invoice::with('clientSite', 'clientSite.client')->whereIn('client_site_id', $clientSites->pluck('id'))->where('id', '<', $invoiceId)->where('status', 'Unpaid')->take(4)->get();
        $list = collect([$invoice])->merge($previousInvoices);
        $invoicesList = $list->sortBy('id');

        $invoice['items'] = InvoiceDetail::where('invoice_id', $invoiceId)->with('guardType')->get();
        $invoice['total'] = InvoiceDetail::where('invoice_id', $invoiceId)->sum('invoice_amount');
        $invoice['due_date'] = Carbon::parse($invoice->invoice_date)->addDays(7)->format('Y-m-d');

        $pdfOptions = new Options();
        $pdfOptions->set('isHtml5ParserEnabled', true);
        $pdfOptions->set('isPhpEnabled', true);

        $dompdf = new Dompdf($pdfOptions);
        $html = view('admin.invoices.invoice-pdf.invoice-new', ['invoice' => $invoice, 'invoicesList' => $invoicesList])->render();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->stream('invoice-' . $invoiceId . '.pdf');

        // return view('admin.invoices.invoice-pdf.invoice', compact('invoice'));
    }

    public function getClientSites(Request $request)
    {
        $clientIds = $request->input('client_ids', []);
        if (empty($clientIds)) {
            return response()->json(['success' => false, 'message' => 'No client selected']);
        }

        $clientSites = ClientSite::whereIn('client_id', $clientIds)->get(['id', 'location_code']);

        return response()->json([
            'success' => true,
            'clientSites' => $clientSites
        ]);
    }

    public function exportCsv(Request $request)
    {
        $clientIds = $request->input('client_ids', []);
        if (is_string($clientIds)) {
            $clientIds = explode(',', $clientIds);
        }

        $clientSiteIds = $request->input('client_site_ids', []);
        if (is_string($clientSiteIds)) {
            $clientSiteIds = explode(',', $clientSiteIds);
        }
        $date = Carbon::parse($request->input('date'));

        if ($date) {
            $fortnightDays = FortnightDates::whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date)->first();

            if (!$fortnightDays) {
                return response()->json(["message" => "No fortnight found for the selected date {$date->toDateString()}."], 400);
            }

            $firstWeekStart = Carbon::parse($fortnightDays->start_date);
            $firstWeekEnd = $firstWeekStart->copy()->addDays(6);
            $secondWeekStart = $firstWeekEnd->copy()->addDay();
            $secondWeekEnd = Carbon::parse($fortnightDays->end_date);

            if ($date >= $firstWeekStart && $date <= $firstWeekEnd) {
                $start_date = $firstWeekStart;
                $end_date = $firstWeekEnd;
            } elseif ($date >= $secondWeekStart && $date <= $secondWeekEnd) {
                $start_date = $secondWeekStart;
                $end_date = $secondWeekEnd;
            } else {
                return response()->json(["message" => "No valid fortnight period found."], 400);
            }

            $payrollDetails = PayrollDetail::whereBetween('date', [
                Carbon::parse($start_date)->format('Y-m-d'),
                Carbon::parse($end_date)->format('Y-m-d'),
            ])->whereIn('client_id', $clientIds);

            if (!empty($clientSiteIds)) {
                $payrollDetails->whereIn('client_site_id', $clientSiteIds);
            }

            $payrollDetails = $payrollDetails->get();

            $startDate =  Carbon::parse($start_date)->format('Y-m-d');
            $endDate = Carbon::parse($end_date)->format('Y-m-d');
            $aggregatedData = $payrollDetails->groupBy('client_id')->map(function ($clientGroup, $clientId) use ($startDate, $endDate) {
                return $clientGroup->groupBy('client_site_id')->map(function ($siteGroup, $clientSiteId) use ($clientId, $startDate, $endDate) {
                    $clientSite = ClientSite::find($clientSiteId);
                    $clientSiteName = $clientSite ? $clientSite->location_code : 'Unknown Site';
                    return $siteGroup->groupBy('guard_type_id')->map(function ($guardGroup, $guardTypeId) use ($clientId, $clientSiteId, $clientSiteName, $startDate, $endDate) {
                        $guardsForNormalHours = $guardGroup->filter(function ($entry) {
                            return $entry->normal_hours > 0;
                        })->pluck('guard_id')->unique()->count();

                        $guardsForOvertimeHours = $guardGroup->filter(function ($entry) {
                            return $entry->overtime > 0;
                        })->pluck('guard_id')->unique()->count();

                        $guardsForPublicHolidayHours = $guardGroup->filter(function ($entry) {
                            return $entry->public_holiday > 0;
                        })->pluck('guard_id')->unique()->count();

                        $invoice = Invoice::whereDate('start_date', $startDate)->where('end_date', $endDate)->where('client_site_id', $clientSiteId)->first();
                        $rate = RateMaster::find($guardGroup->first()->guard_type_id);
                        $clientRateMaster = ClientRateMaster::where('client_id', $clientId)->where('guard_type', $rate->guard_type)->first();
                        return [
                            'invoice_number'                => $invoice->invoice_code,
                            'invoice_date'                  => $invoice->invoice_date,
                            'client_site_id'                => $clientSiteId,
                            'client_site_name'              => $clientSiteName,
                            'guard_type_id_name'            => $rate ? $rate->guard_type : 'Unknown',
                            'normal_hours_guard'            => $guardGroup->sum('normal_hours'),
                            'overtime_guard'                => $guardGroup->sum('overtime'),
                            'double_hours'                  => $guardGroup->sum('public_holiday'),
                            'no_of_guards_normal'           => $guardsForNormalHours,
                            'no_of_guards_overtime'         => $guardsForOvertimeHours,
                            'no_of_guards_publicHoliday'    => $guardsForPublicHolidayHours,
                            'rate_normal'                   => $rate ? $rate->gross_hourly_rate : 0,
                            'rate_overtime'                 => $rate ? $rate->overtime_rate : 0,
                            'rate_holiday'                  => $rate ? $rate->holiday_rate : 0,
                            'client_rate_normal'            => $clientRateMaster ? $clientRateMaster->gross_hourly_rate : 0,
                            'client_rate_overtime'          => $clientRateMaster ? $clientRateMaster->overtime_rate : 0,
                            'client_rate_holiday'           => $clientRateMaster ? $clientRateMaster->holiday_rate : 0,
                        ];
                    });
                });
            });

            $flattenedData = $aggregatedData->flatMap(function ($siteGroup) {
                return $siteGroup->flatMap(function ($guardGroup) {
                    return $guardGroup;
                });
            });

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            $sheet->mergeCells('A1:H1');
            $sheet->setCellValue('A1', 'Report Name: Analysis of Client Billing  Vs Guard Payment for the same period ');
            $sheet->getStyle('A1')->getFont()->setBold(true);
            $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A2:B2');
            $sheet->setCellValue('A2', 'Report Filters');
            $sheet->getStyle('A2')->getFont()->setBold(true);
            $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A5:D5');
            $sheet->setCellValue('A5', 'PERIOD: ' . $start_date->toFormattedDateString() . ' to ' . $end_date->toFormattedDateString());
            $sheet->getStyle('A5')->getFont()->setBold(true);
            $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A7:B7');
            $sheet->setCellValue('A7', 'SENIOR MANAGER:');
            $sheet->getStyle('A7')->getFont()->setBold(true);
            $sheet->getStyle('A7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('F7:I7');
            $sheet->setCellValue('F7', 'MANAGER:');
            $sheet->getStyle('F7')->getFont()->setBold(true);
            $sheet->getStyle('F7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('K7:N7');
            $sheet->setCellValue('K7', 'SUPERVISOR');
            $sheet->getStyle('K7')->getFont()->setBold(true);
            $sheet->getStyle('K7')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A9:C9');
            $sheet->setCellValue('A9', 'Expected output from report');
            $sheet->getStyle('A9')->getFont()->setBold(true);
            $sheet->getStyle('A9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('A10:J10');
            $sheet->setCellValue('A10', 'CLIENT INVOICED');
            $sheet->getStyle('A10')->getFont()->setBold(true);
            $sheet->getStyle('A10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $sheet->mergeCells('K10:P10');
            $sheet->setCellValue('K10', 'GUARD SALARY PAYMENTS');
            $sheet->getStyle('K10')->getFont()->setBold(true);
            $sheet->getStyle('K10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            $headers = [
                'Invoice Date',
                'Invoice Number',
                'Location Code',
                'Location Name',
                'Guard Type',
                'Hours Type',
                'No of Guards',
                'Hours Billed',
                'Rate',
                'Invoice Amount',
                'No of Guards',
                'Hours Paid',
                'Hourly Rate',
                'Salary Paid',
                'Gross Margin',
                'Cost Absorbed'
            ];

            $sheet->fromArray($headers, null, 'A11');
            $sheet->getStyle('A11:P11')->getFont()->setBold(true);

            $row = 12;
            foreach ($aggregatedData as $clientId => $clientSiteGroup) {
                foreach ($clientSiteGroup as $clientSiteId => $siteData) {
                    $siteTotalNormalHours = 0;
                    $siteTotalOvertime = 0;
                    $siteTotalPublicHoliday = 0;
                    $siteTotalGuardsNormal = 0;
                    $siteTotalGuardsOvertime = 0;
                    $siteTotalGuardsPublicHoliday = 0;
                    $siteTotalAmount = 0;

                    $guardTotalHours = 0;
                    $guardTotalOvertime = 0;
                    $guardTotalPublicHoliday = 0;
                    $guardTotalGuardsNormal = 0;
                    $guardTotalGuardsOvertime = 0;
                    $guardTotalGuardsPublicHoliday = 0;
                    $guardSalaryPaid = 0;
                    $guardGrossMargin = 0;

                    foreach ($siteData as $data) {
                        $normalInvoiceAmount = ($data['normal_hours_guard'] + $data['overtime_guard']) * $data['client_rate_normal'];
                        $normalSalaryPaid = $data['normal_hours_guard'] * $data['rate_normal'];
                        $normalGrossMargin = $normalInvoiceAmount - $normalSalaryPaid;

                        $sheet->setCellValue("A{$row}", $data['invoice_date']);
                        $sheet->setCellValue("B{$row}", $data['invoice_number']);
                        $sheet->setCellValue("C{$row}", '');
                        $sheet->setCellValue("D{$row}", $data['client_site_name']);
                        $sheet->setCellValue("E{$row}", $data['guard_type_id_name']);
                        $sheet->setCellValue("F{$row}", 'Normal');
                        $sheet->setCellValue("G{$row}", $data['no_of_guards_normal']);
                        $sheet->setCellValue("H{$row}", convertToHoursAndMinutes($data['normal_hours_guard'] + $data['overtime_guard']));
                        $sheet->setCellValue("I{$row}", '$ ' . $data['client_rate_normal']);
                        $sheet->setCellValue("J{$row}", '$ ' . $normalInvoiceAmount);
                        $sheet->setCellValue("K{$row}", $data['no_of_guards_normal']);
                        $sheet->setCellValue("L{$row}", convertToHoursAndMinutes($data['normal_hours_guard']));
                        $sheet->setCellValue("M{$row}", '$ ' . $data['rate_normal']);
                        $sheet->setCellValue("N{$row}", '$ ' . $normalSalaryPaid);
                        $sheet->setCellValue("O{$row}", '$ ' . $normalGrossMargin);
                        $row++;

                        $overtimeInvoiceAmount = $data['overtime_guard'] * $data['rate_overtime'];
                        $overtimeSalaryPaid = $data['overtime_guard'] * $data['rate_overtime'];
                        $overtimeGrossMargin = $overtimeInvoiceAmount - $overtimeSalaryPaid;

                        $sheet->setCellValue("F{$row}", 'Time & 1/2');
                        $sheet->setCellValue("G{$row}", 0);
                        $sheet->setCellValue("H{$row}", '0:0');
                        $sheet->setCellValue("I{$row}", '$ ' . $data['client_rate_overtime']);
                        $sheet->setCellValue("J{$row}", '$ ' . 0);
                        $sheet->setCellValue("K{$row}", $data['no_of_guards_overtime']);
                        $sheet->setCellValue("L{$row}", convertToHoursAndMinutes($data['overtime_guard']));
                        $sheet->setCellValue("M{$row}", '$ ' . $data['rate_overtime']);
                        $sheet->setCellValue("N{$row}", '$ ' . $overtimeSalaryPaid);
                        $sheet->setCellValue("O{$row}", '$ ' . $overtimeGrossMargin);
                        $row++;

                        $holidayInvoiceAmount = $data['double_hours'] * $data['client_rate_holiday'];
                        $holidaySalaryPaid = $data['double_hours'] * $data['rate_holiday'];
                        $holidayGrossMargin = $holidayInvoiceAmount - $holidaySalaryPaid;

                        $sheet->setCellValue("F{$row}", 'Double');
                        $sheet->setCellValue("G{$row}", $data['no_of_guards_publicHoliday']);
                        $sheet->setCellValue("H{$row}", convertToHoursAndMinutes($data['double_hours']));
                        $sheet->setCellValue("I{$row}", '$ ' . $data['client_rate_holiday']);
                        $sheet->setCellValue("J{$row}", '$ ' . $holidayInvoiceAmount);
                        $sheet->setCellValue("K{$row}", $data['no_of_guards_publicHoliday']);
                        $sheet->setCellValue("L{$row}", convertToHoursAndMinutes($data['double_hours']));
                        $sheet->setCellValue("M{$row}", '$ ' . $data['rate_holiday']);
                        $sheet->setCellValue("N{$row}", '$ ' . $holidaySalaryPaid);
                        $sheet->setCellValue("O{$row}", '$ ' . $holidayGrossMargin);
                        $row++;

                        $siteTotalNormalHours += $data['normal_hours_guard'];
                        $siteTotalOvertime += $data['overtime_guard'];
                        $siteTotalPublicHoliday += $data['double_hours'];
                        $siteTotalGuardsNormal += $data['no_of_guards_normal'];
                        $siteTotalGuardsOvertime += $data['no_of_guards_overtime'];
                        $siteTotalGuardsPublicHoliday += $data['no_of_guards_publicHoliday'];
                        $siteTotalAmount += $normalInvoiceAmount + $overtimeInvoiceAmount + $holidayInvoiceAmount;

                        $guardTotalHours +=  $data['normal_hours_guard'];
                        $guardTotalOvertime += $data['overtime_guard'];
                        $guardTotalPublicHoliday += $data['double_hours'];
                        $guardTotalGuardsNormal += $data['no_of_guards_normal'];
                        $guardTotalGuardsOvertime += $data['no_of_guards_overtime'];
                        $guardTotalGuardsPublicHoliday += $data['no_of_guards_publicHoliday'];
                        $guardSalaryPaid += $normalSalaryPaid + $overtimeSalaryPaid + $holidaySalaryPaid;
                        $guardGrossMargin += $normalGrossMargin + $overtimeGrossMargin + $holidayGrossMargin;
                    }

                    $sheet->setCellValue("A{$row}", 'Totals');
                    $sheet->setCellValue("H{$row}", convertToHoursAndMinutes($siteTotalNormalHours + $siteTotalOvertime + $siteTotalPublicHoliday));
                    $sheet->setCellValue("J{$row}", '$ ' . $siteTotalAmount);
                    $sheet->setCellValue("L{$row}", convertToHoursAndMinutes($guardTotalHours + $guardTotalOvertime + $guardTotalPublicHoliday));
                    $sheet->setCellValue("N{$row}", '$ ' . $guardSalaryPaid);
                    $sheet->setCellValue("O{$row}", '$ ' . $guardGrossMargin);

                    $sheet->getStyle("A{$row}:p{$row}")->getFont()->setBold(true);
                    $row++;

                    $row++;
                }
            }

            $writer = new Xlsx($spreadsheet);
            $filename = "payroll_export_" . now()->format('Y_m_d_H_i_s') . ".xlsx";
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            $writer->save('php://output');
            exit;
        }
    }
}
