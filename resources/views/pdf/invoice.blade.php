<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #1a1a1a;
            background: #fff;
        }

        @if(empty($printMode))
        /*
         * PDF mode (dompdf): body margin is the reliable way to set page insets.
         * @page margin support in dompdf is inconsistent regardless of unit.
         * 2cm ≈ 57pt — standard A4 business invoice margin.
         */
        body { margin: 2cm; }
        .page { width: 100%; }
        @endif

        @if(!empty($printMode))
        /* Screen print-preview: A4 width, centered, with visual margins */
        .page {
            width: 100%;
            max-width: 794px;
            margin: 0 auto;
            padding: 40px;
        }
        @endif

        /* ── Header ── */
        .header {
            width: 100%;
            margin-bottom: 32px;
        }
        .header table { width: 100%; }
        .header td { vertical-align: top; }
        .header .logo-cell { width: 50%; }
        .header .meta-cell { width: 50%; text-align: right; }

        .logo-img { max-height: 64px; max-width: 180px; }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d3d93;
        }

        .doc-title {
            font-size: 22px;
            font-weight: bold;
            color: #0d3d93;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .doc-number { font-size: 14px; font-weight: bold; margin-top: 4px; }
        .doc-meta { font-size: 12px; color: #555; margin-top: 2px; }

        /* ── Divider ── */
        .divider {
            border: none;
            border-top: 2px solid #0d3d93;
            margin: 20px 0;
        }
        /* ── Party section ── */
        .parties { width: 100%; margin-bottom: 24px; }
        .parties td { vertical-align: top; width: 50%; }
        .party-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0d3d93;
            margin-bottom: 6px;
        }
        .party-name { font-size: 14px; font-weight: bold; margin-bottom: 4px; }
        .party-detail { font-size: 12px; color: #444; line-height: 1.6; }

        /* ── Line items table ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table thead tr {
            background-color: #0d3d93;
            color: #fff;
        }
        .items-table thead th {
            padding: 8px 10px;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: bold;
        }
        .items-table tbody tr { border-bottom: 1px solid #e8e8e8; }
        .items-table tbody tr:last-child { border-bottom: none; }
        .items-table tbody td { padding: 8px 10px; font-size: 12px; vertical-align: top; }
        .items-table tbody tr:nth-child(even) td { background-color: #f7f9fc; }

        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .col-desc { width: 45%; }
        .col-qty  { width: 10%; }
        .col-unit { width: 20%; }
        .col-total{ width: 25%; }
        /* Multi-vehicle invoices add a Véhicule column; narrow the description to fit. */
        .col-veh  { width: 18%; }
        .col-veh .veh-detail { color: #888; font-size: 10px; }
        .items-table.has-veh .col-desc { width: 27%; }

        /* ── Totals ── */
        .totals-wrap { width: 100%; }
        .totals-wrap td { vertical-align: top; }
        .totals-notes { width: 50%; padding-right: 20px; }
        .totals-table-wrap { width: 50%; }

        .totals-table { width: 100%; border-collapse: collapse; }
        .totals-table td { padding: 5px 8px; font-size: 12px; }
        .totals-table td:last-child { text-align: right; }
        .totals-table .row-total {
            background-color: #0d3d93;
            color: #fff;
            font-weight: bold;
            font-size: 13px;
        }
        .totals-table .row-subtotal td { border-top: 1px solid #e0e0e0; }

        /* ── Notes ── */
        .notes-label {
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0d3d93;
            margin-bottom: 6px;
        }
        .notes-body { font-size: 12px; color: #444; line-height: 1.6; }

        /* ── Footer ── */
        .footer {
            margin-top: 40px;
            border-top: 1px solid #e0e0e0;
            padding-top: 12px;
            font-size: 10px;
            color: #888;
            text-align: center;
        }

        /* ── Print mode ── */
        @media print {
            body { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        }

        @if(!empty($printMode))
        .print-bar {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #f3f4f6;
            padding: 10px 20px;
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            border-bottom: 1px solid #d1d5db;
            z-index: 100;
        }
        .print-bar button {
            background: #0d3d93;
            color: #fff;
            border: none;
            padding: 6px 16px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }
        .print-bar button:hover { background: #0b2f70; }
        @media print { .print-bar { display: none; } body { padding-top: 0; } }
        body { padding-top: 52px; }
        @endif
    </style>
</head>
<body>

@if(!empty($printMode))
<div class="print-bar">
    <button onclick="window.print()">Imprimer</button>
    <button onclick="window.close()">Fermer</button>
</div>
@endif

<div class="page">

    {{-- ── HEADER ── --}}
    <div class="header">
        <table>
            <tr>
                <td class="logo-cell">
                    @if(!empty($company['logo']) && file_exists(public_path($company['logo'])))
                        @if(!empty($printMode))
                            <img class="logo-img" src="{{ asset($company['logo']) }}" alt="Logo">
                        @else
                            <img class="logo-img" src="{{ public_path($company['logo']) }}" alt="Logo">
                        @endif
                    @else
                        <div class="company-name">{{ $company['name'] }}</div>
                    @endif
                    <div class="party-detail" style="margin-top:8px;">
                        {{ $company['address'] }}<br>
                        @if($company['phone']) Tél : {{ $company['phone'] }}<br>@endif
                        @if($company['email']) {{ $company['email'] }}<br>@endif
                        @if($company['ice']) ICE : {{ $company['ice'] }}<br>@endif
                        @if($company['if'])  IF  : {{ $company['if'] }}<br>@endif
                        @if($company['rc'])  RC  : {{ $company['rc'] }}<br>@endif
                    </div>
                </td>
                <td class="meta-cell">
                    <div class="doc-title">
                        {{ $invoice->type->value === 'quote' ? 'Devis' : 'Facture' }}
                    </div>
                    <div class="doc-number">N° {{ $invoice->number }}</div>
                    <div class="doc-meta">
                        Date : {{ $invoice->issue_date->format('d/m/Y') }}<br>
                        @if($invoice->due_date)
                            Échéance : {{ $invoice->due_date->format('d/m/Y') }}<br>
                        @endif
                        Statut : {{ $invoice->status->label() }}
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <hr class="divider">

    {{-- ── PARTIES ── --}}
    <table class="parties">
        <tr>
            <td>
                <div class="party-label">De</div>
                <div class="party-name">{{ $company['name'] }}</div>
                <div class="party-detail">{{ $company['address'] }}</div>
            </td>
            <td>
                <div class="party-label">Facturé à</div>
                @if($invoice->customer)
                    <div class="party-name">{{ $invoice->customer->display_name }}</div>
                    <div class="party-detail">
                        {{-- Entreprise: raison sociale + ICE only (per client request) —
                             no IF/patente, contact, phone or address on the facture. --}}
                        @if($invoice->customer->type === \App\Enums\CustomerType::Company)
                            @if($invoice->customer->ice) ICE : {{ $invoice->customer->ice }}<br>@endif
                        @else
                            @if($invoice->customer->phone) {{ $invoice->customer->phone }}<br>@endif
                            @if($invoice->customer->email) {{ $invoice->customer->email }}<br>@endif
                            @if($invoice->customer->address) {{ $invoice->customer->address }}<br>@endif
                            @if($invoice->customer->ville) {{ $invoice->customer->ville }}<br>@endif
                        @endif
                    </div>
                @else
                    <div class="party-detail">—</div>
                @endif
                @if($invoice->vehicle)
                    <div class="party-detail" style="margin-top:6px;">
                        Véhicule : {{ $invoice->vehicle->license_plate }}
                        ({{ $invoice->vehicle->make }} {{ $invoice->vehicle->model }})
                    </div>
                @endif
            </td>
        </tr>
    </table>

    {{-- ── LINE ITEMS ── --}}
    {{-- Show a per-line Véhicule column only when at least one line is attributed to a
         vehicle (a multi-vehicle invoice). Single-vehicle invoices keep the plain table. --}}
    @php($hasLineVehicles = $invoice->items->contains(fn ($i) => $i->vehicle_id))
    <table class="items-table {{ $hasLineVehicles ? 'has-veh' : '' }}">
        <thead>
            <tr>
                <th class="col-desc text-left">Description</th>
                @if($hasLineVehicles)
                <th class="col-veh text-left">Véhicule</th>
                @endif
                <th class="col-qty text-center">Qté</th>
                <th class="col-unit text-right">P.U. HT (DH)</th>
                <th class="col-total text-right">Total HT (DH)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr>
                <td class="col-desc">{{ $item->description }}</td>
                @if($hasLineVehicles)
                <td class="col-veh">
                    @if($item->vehicle)
                        {{ $item->vehicle->license_plate }}
                        <span class="veh-detail">{{ $item->vehicle->make }} {{ $item->vehicle->model }}</span>
                    @endif
                </td>
                @endif
                <td class="col-qty text-center">{{ number_format((float)$item->quantity, 2, ',', ' ') }}</td>
                <td class="col-unit text-right">{{ number_format((float)$item->unit_price_ht, 2, ',', ' ') }}</td>
                <td class="col-total text-right">{{ number_format((float)$item->line_total_ht, 2, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    {{-- ── TOTALS + NOTES ── --}}
    <table class="totals-wrap">
        <tr>
            <td class="totals-notes">
                @if($invoice->notes)
                    <div class="notes-label">Notes</div>
                    <div class="notes-body">{{ $invoice->notes }}</div>
                @endif
            </td>
            <td class="totals-table-wrap">
                <table class="totals-table">
                    <tr>
                        <td>Sous-total HT</td>
                        <td>{{ number_format((float)$invoice->subtotal_ht, 2, ',', ' ') }} DH</td>
                    </tr>
                    @if((float)$invoice->discount > 0)
                    <tr>
                        <td>Remise</td>
                        <td>− {{ number_format((float)$invoice->discount, 2, ',', ' ') }} DH</td>
                    </tr>
                    @endif
                    <tr>
                        <td>TVA ({{ number_format((float)$invoice->tva_rate, 0) }} %)</td>
                        <td>{{ number_format((float)$invoice->tva_amount, 2, ',', ' ') }} DH</td>
                    </tr>
                    <tr class="row-total">
                        <td><strong>Total TTC</strong></td>
                        <td><strong>{{ number_format((float)$invoice->total_ttc, 2, ',', ' ') }} DH</strong></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ── FOOTER ── --}}
    <div class="footer">
        {{ $company['name'] }}
        @if($company['ice']) — ICE : {{ $company['ice'] }}@endif
        @if($company['if'])  — IF : {{ $company['if'] }}@endif
        @if($company['rc'])  — RC : {{ $company['rc'] }}@endif
    </div>

</div>

</body>
</html>
