<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MANIFIESTO PAPEL PREIMPRESO MAGORI - {{ $manifiesto->codigo_manifiesto }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 0;
        }
        html, body {
            width: 210mm;
            height: 297mm;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8.5px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact;
            overflow: hidden;
        }
        /* CALIBRATED TOP MARGIN FOR PHYSICAL PRE-PRINTED MAGORI HEADER (36mm) */
        .top-blank-reservation {
            height: 36mm;
            width: 100%;
        }
        .content-container {
            padding: 0 8mm 4mm 8mm;
            box-sizing: border-box;
        }
        .header-box {
            border-bottom: 1.5px solid #1e40af;
            padding-bottom: 3px;
            margin-bottom: 4px;
        }
        .row-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .label {
            color: #1e3a8a;
            font-weight: 800;
            text-transform: uppercase;
        }
        .value {
            color: #0f172a;
            border-bottom: 1px dotted #3b82f6;
            padding-bottom: 0px;
        }
        table.grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            table-layout: fixed;
            border: 1.5px solid #2563eb;
        }
        table.grid th {
            background-color: #bfdbfe !important;
            color: #1e3a8a !important;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2.5px 2px;
            border: 1px solid #60a5fa;
            text-align: center;
            font-size: 8.5px;
            white-space: nowrap;
        }
        table.grid td {
            border: 1px solid #93c5fd;
            padding: 1px 3px;
            height: 11.2px;
            vertical-align: middle;
            overflow: hidden;
            white-space: nowrap;
            text-overflow: ellipsis;
        }
        table.grid td.asiento {
            text-align: center;
            font-weight: bold;
            color: #1e3a8a;
            width: 42px;
            background-color: #eff6ff !important;
        }
        table.grid td.pasajero-nombre {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 8.5px;
        }
        table.grid td.dni {
            text-align: center;
            font-family: monospace;
            font-weight: bold;
            width: 75px;
            font-size: 8.5px;
        }
        table.grid td.empresa {
            text-transform: uppercase;
            width: 120px;
            font-size: 8px;
        }
        table.grid td.firma {
            width: 95px;
        }
        /* CONDUCTOR SIGNATURE LINE SEPARATED BY ~3 LINES BELOW TABLE */
        .footer-conductor {
            margin-top: 24px;
            text-align: center;
        }
        .signature-line {
            display: inline-block;
            width: 220px;
            border-top: 1.5px solid #1e40af;
            padding-top: 3px;
            font-weight: bold;
            font-size: 9px;
            color: #1e3a8a;
            text-transform: uppercase;
        }
        .no-print {
            position: fixed;
            top: 10px;
            right: 10px;
            z-index: 9999;
        }
        @media print {
            .no-print { display: none; }
            html, body {
                width: 210mm;
                height: 297mm;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <button onclick="window.print()" style="background: #2563eb; color: white; border: none; padding: 10px 20px; font-weight: bold; border-radius: 8px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2);">
            🖨 IMPRIMIR MANIFIESTO (1 HOJA A4)
        </button>
    </div>

    <!-- CALIBRATED TOP MARGIN FOR PHYSICAL PRE-PRINTED MAGORI HEADER (36mm) -->
    <div class="top-blank-reservation"></div>

    <div class="content-container">
        <!-- Dynamic Header Info Matching Physical Form Lines -->
        <div class="header-box">
            <div class="row-info">
                @php
                    $fechaSalidaFormatted = \Carbon\Carbon::parse($manifiesto->fecha_salida_programada)->format('d/m/Y');
                    $horaSalidaFormatted = \Carbon\Carbon::parse($manifiesto->fecha_salida_programada)->format('H:i');
                @endphp
                <div><span class="label">FECHA DE SALIDA:</span> <span class="value">{{ $fechaSalidaFormatted }}</span></div>
                <div><span class="label">ORIGEN:</span> <span class="value">{{ strtoupper($manifiesto->ruta?->origen) }}</span></div>
                <div><span class="label">DESTINO:</span> <span class="value">{{ strtoupper($manifiesto->ruta?->destino) }}</span></div>
            </div>
            <div class="row-info">
                <div style="flex: 2;"><span class="label">CONDUCTOR:</span> <span class="value">{{ strtoupper($manifiesto->conductor?->nombres ?? $manifiesto->conductor?->trabajador?->nombres) }} {{ strtoupper($manifiesto->conductor?->apellido_paterno ?? $manifiesto->conductor?->trabajador?->apellidos) }}</span></div>
                <div style="flex: 2;"><span class="label">COPILOTO:</span> <span class="value">{{ strtoupper($manifiesto->copiloto?->nombres ?? $manifiesto->copiloto?->trabajador?->nombres) }} {{ strtoupper($manifiesto->copiloto?->apellido_paterno ?? $manifiesto->copiloto?->trabajador?->apellidos) ?: '------------------' }}</span></div>
            </div>
            <div class="row-info" style="margin-bottom: 0;">
                <div><span class="label">Nº LICENCIA:</span> <span class="value">{{ $manifiesto->conductor?->numero_licencia }}</span></div>
                <div><span class="label">CATEGORÍA:</span> <span class="value">{{ $manifiesto->conductor?->categoria_licencia }}</span></div>
                <div><span class="label">PLACA:</span> <span class="value">{{ $manifiesto->vehiculo?->placa }}</span></div>
                <div><span class="label">HORA:</span> <span class="value">{{ $horaSalidaFormatted }}</span></div>
            </div>
        </div>

        <!-- 46 Seats Grid Table (Compacted to Fit 1 A4 Page Perfectly) -->
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 42px;">ASIENTO</th>
                    <th>APELLIDOS Y NOMBRES</th>
                    <th style="width: 75px;">DNI</th>
                    <th style="width: 120px;">EMPRESA</th>
                    <th style="width: 95px;">FIRMA</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 46; $i++)
                    @php
                        $detalle = $manifiesto->detalles->firstWhere('numero_asiento', $i) ?? ($manifiesto->detalles[$i - 1] ?? null);
                    @endphp
                    <tr>
                        <td class="asiento">{{ $i }}</td>
                        <td class="pasajero-nombre">
                            {{ $detalle ? ($detalle->trabajador?->nombres . ' ' . $detalle->trabajador?->apellidos) : '' }}
                        </td>
                        <td class="dni">{{ $detalle?->trabajador?->dni }}</td>
                        <td class="empresa">{{ $detalle?->trabajador?->empresa?->razon_social }}</td>
                        <td class="firma"></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="footer-conductor">
            <div class="signature-line">
                CONDUCTOR
            </div>
        </div>
    </div>

</body>
</html>