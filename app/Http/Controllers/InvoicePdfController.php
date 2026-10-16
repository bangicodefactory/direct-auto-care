<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoicePdfController extends Controller
{
    public function pdf(Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'vehicle', 'items.product', 'items.vehicle']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice'   => $invoice,
            'company'   => config('company'),
            'showUnits' => (bool) config('company.show_product_units'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($invoice->number . '.pdf');
    }

    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'vehicle', 'items.product', 'items.vehicle']);

        return view('pdf.invoice', [
            'invoice'   => $invoice,
            'company'   => config('company'),
            'showUnits' => (bool) config('company.show_product_units'),
            'printMode' => true,
        ]);
    }
}
