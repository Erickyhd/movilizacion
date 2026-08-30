<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MANIFIESTO OFICIAL DE PASAJEROS - {{ $manifiesto->codigo_manifiesto }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 10mm;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1e293b;
            margin: 0;
            padding: 10px;
            background: #fff;
        }
        .header-box {
            border: 2px solid #1e3a8a;
            border-radius: 6px;
            padding: 8px 12px;
            margin-bottom: 10px;
            background-color: #f8fafc;
        }
        .row-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 11px;
            font-weight: bold;
        }
        .label {
            color: #1e3a8a;
            font-weight: 800;
            text-transform: uppercase;
        }
        .value {
            color: #0f172a;
            border-bottom: 1px dotted #94a3b8;
            padding-bottom: 1px;
        }
        table.grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table.grid th {
            background-color: #3b82f6;
            color: #ffffff;
            font-weight: 800;
            text-transform: uppercase;
            padding: 5px;
            border: 1px solid #1d4ed8;
            text-align: center;
        }
        table.grid td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            height: 16px;
        }
        table.grid td.asiento {
            text-align: center;
            font-weight: bold;
            color: #1e3a8a;
            width: 50px;
        }
        table.grid td.dni {
            text-align: center;
            font-family: monospace;
            font-weight: bold;
            width: 90px;
        }
        table.grid td.empresa {
            text-transform: uppercase;
            width: 140px;
        }
        table.grid td.firma {
            width: 120px;
        }
        .no-print {
            margin-bottom: 15px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="text-align: right;">
        <button onclick="window.print()" style="background: #2563eb; color: white; border: none; padding: 8px 16px; font-weight: bold; border-radius: 8px; cursor: pointer;">
            🖨 IMPRIMIR MANIFIESTO OFICIAL
        </button>
    </div>

    <!-- Official Header Form Box -->
    <div class="header-box">
        <div class="row-info">
            <div><span class="label">FECHA DE SALIDA:</span> <span class="value">{{ date('d/m/Y', strtotime($manifiesto->fecha_salida_programada)) }}</span></div>
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
            <div><span class="label">HORA:</span> <span class="value">{{ date('H:i', strtotime($manifiesto->fecha_salida_programada)) }}</span></div>
        </div>
    </div>

    <!-- Official Passenger Table Grid -->
    <table class="grid">
        <thead>
            <tr>
                <th style="width: 50px;">ASIENTO</th>
                <th>APELLIDOS Y NOMBRES</th>
                <th style="width: 90px;">DNI</th>
                <th style="width: 140px;">EMPRESA</th>
                <th style="width: 120px;">FIRMA</th>
            </tr>
        </thead>
        <tbody>
            @for ($i = 1; $i <= $totalFilas; $i++)
                @php
                    $detalle = $manifiesto->detalles->firstWhere('numero_asiento', $i) ?? ($manifiesto->detalles[$i - 1] ?? null);
                @endphp
                <tr>
                    <td class="asiento">{{ $i }}</td>
                    <td style="font-weight: bold; text-transform: uppercase;">
                        {{ $detalle ? ($detalle->trabajador?->nombres . ' ' . $detalle->trabajador?->apellidos) : '' }}
                    </td>
                    <td class="dni">{{ $detalle?->trabajador?->dni }}</td>
                    <td class="empresa">{{ $detalle?->trabajador?->empresa?->razon_social }}</td>
                    <td class="firma"></td>
                </tr>
            @endfor
        </tbody>
    </table>

</body>
</html>