<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with('items', 'creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $invoices = $query->get();
        $totalRevenue = $invoices->where('status', 'paid')->sum('total_amount');

        return view('admin.reports.index', compact('invoices', 'totalRevenue'));
    }

    public function exportPdf(Request $request)
    {
        $query = Invoice::with('items', 'creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $invoices = $query->get();
        $totalRevenue = $invoices->where('status', 'paid')->sum('total_amount');

        $pdf = Pdf::loadView('admin.reports.pdf', compact('invoices', 'totalRevenue'))
            ->setPaper('a4', 'landscape');

        return $pdf->download('laporan-invoice-' . now()->format('Ymd') . '.pdf');
    }

    public function exportExcel(Request $request)
    {
        $query = Invoice::with('items', 'creator')->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $invoices = $query->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Invoice');

        $headers = ['No', 'No. Invoice', 'Customer', 'Telepon', 'Total', 'Metode', 'Status', 'Tanggal', 'Lunas'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValueByColumnAndRow($col + 1, 1, $header);
        }

        foreach ($invoices as $i => $invoice) {
            $row = $i + 2;
            $sheet->setCellValueByColumnAndRow(1, $row, $i + 1);
            $sheet->setCellValueByColumnAndRow(2, $row, $invoice->invoice_number);
            $sheet->setCellValueByColumnAndRow(3, $row, $invoice->customer_name);
            $sheet->setCellValueByColumnAndRow(4, $row, $invoice->customer_phone ?? '-');
            $sheet->setCellValueByColumnAndRow(5, $row, (float) $invoice->total_amount);
            $sheet->setCellValueByColumnAndRow(6, $row, $invoice->payment_method ?? '-');
            $sheet->setCellValueByColumnAndRow(7, $row, $invoice->status_label);
            $sheet->setCellValueByColumnAndRow(8, $row, $invoice->created_at->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(9, $row, $invoice->paid_at?->format('d/m/Y') ?? '-');
        }

        $writer = new Xlsx($spreadsheet);
        $filename = 'laporan-invoice-' . now()->format('Ymd') . '.xlsx';
        $path = storage_path('app/temp/' . $filename);

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $writer->save($path);

        return response()->download($path, $filename)->deleteFileAfterSend();
    }

    public function invoicePdf(Invoice $invoice)
    {
        $invoice->load('items', 'extras', 'creator');
        $pdf = Pdf::loadView('admin.invoices.pdf', compact('invoice'))->setPaper('a4');
        return $pdf->download('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
