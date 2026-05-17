<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class InvoicePdfController extends Controller
{
    public function pdf(Request $request, Invoice $invoice): Response
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'vehicle', 'items.product']);

        $pdf = Pdf::loadView('pdf.invoice', [
            'invoice' => $invoice,
            'company' => config('company'),
        ])->setPaper('a4', 'portrait');

        return $pdf->stream($invoice->number . '.pdf');
    }

    public function print(Request $request, Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'vehicle', 'items.product']);

        return view('pdf.invoice', [
            'invoice'   => $invoice,
            'company'   => config('company'),
            'printMode' => true,
        ]);
    }
}
