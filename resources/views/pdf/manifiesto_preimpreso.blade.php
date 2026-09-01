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
        .top-blank-reservation {
            height: 72mm;
            width: 100%;
        }
        .content-container {
            padding: 0 8mm 4mm 8mm;
            box-sizing: border-box;
        }

        /* HEADER INFO - 3 rows using table for DomPDF compatibility (Clean without underline) */
        table.header-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            border-bottom: 1.5px solid #1e40af;
            padding-bottom: 3px;
        }
        table.header-info td {
            padding: 2px 0;
            font-size: 9px;
            font-weight: bold;
            vertical-align: bottom;
            border: none;
        }
        table.header-info .lbl {
            color: #1e3a8a;
            font-weight: 800;
            text-transform: uppercase;
            white-space: nowrap;
            padding-right: 2px;
        }
        table.header-info .val {
            color: #0f172a;
            font-weight: 800;
            border-bottom: none !important;
            padding-left: 3px;
            padding-right: 8px;
        }

        /* PASSENGERS GRID TABLE */
        table.grid {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            table-layout: fixed;
            border: 1.5px solid #2563eb;
        }
        table.grid th {
            background-color: #bfdbfe;
            color: #1e3a8a;
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
        }
        table.grid td.asiento {
            text-align: center;
            font-weight: bold;
            color: #1e3a8a;
            width: 32px;
            background-color: #eff6ff;
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
            width: 65px;
            font-size: 8.5px;
        }
        table.grid td.empresa {
            text-transform: uppercase;
            width: 105px;
            font-size: 8px;
        }
        table.grid td.firma {
            width: 85px;
        }
        .footer-conductor {
            margin-top: 48px;
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
    </style>
</head>
<body>

    <div class="top-blank-reservation"></div>

    <div class="content-container">

        <!-- HEADER: 3 ROWS MATCHING PHYSICAL PRE-PRINTED FORM -->
        <table class="header-info">
            <!-- ROW 1: FECHA DE SALIDA | ORIGEN | DESTINO -->
            <tr>
                <td class="lbl">FECHA DE SALIDA:</td>
                <td class="val">{{ $fechaSalida ?? date('d/m/Y') }}</td>
                <td class="lbl">ORIGEN:</td>
                <td class="val">{{ strtoupper($manifiesto->ruta?->origen) }}</td>
                <td class="lbl">DESTINO:</td>
                <td class="val">{{ strtoupper($manifiesto->ruta?->destino) }}</td>
            </tr>
            <!-- ROW 2: CONDUCTOR | COPILOTO -->
            @php
                $cond = $manifiesto->conductor;
                $condTrab = $cond?->trabajador;
                $condApellidos = trim(($cond?->apellido_paterno ?? $condTrab?->apellido_paterno ?? '') . ' ' . ($cond?->apellido_materno ?? $condTrab?->apellido_materno ?? ''));
                if (!$condApellidos) $condApellidos = $condTrab?->apellidos ?? '';
                $condNombres = $cond?->nombres ?? $condTrab?->nombres ?? '';
                $condFull = trim("$condApellidos $condNombres");

                $cop = $manifiesto->copiloto;
                $copTrab = $cop?->trabajador;
                $copApellidos = trim(($cop?->apellido_paterno ?? $copTrab?->apellido_paterno ?? '') . ' ' . ($cop?->apellido_materno ?? $copTrab?->apellido_materno ?? ''));
                if (!$copApellidos) $copApellidos = $copTrab?->apellidos ?? '';
                $copNombres = $cop?->nombres ?? $copTrab?->nombres ?? '';
                $copFull = trim("$copApellidos $copNombres");
            @endphp
            <tr>
                <td class="lbl">CONDUCTOR:</td>
                <td class="val" colspan="2">{{ strtoupper($condFull) }}</td>
                <td class="lbl">COPILOTO:</td>
                <td class="val" colspan="2">{{ strtoupper($copFull) }}</td>
            </tr>
            <!-- ROW 3: N LICENCIA | CATEGORIA | PLACA | HORA -->
            <tr>
                <td class="lbl" style="white-space: nowrap;">N&ordm; LICENCIA:</td>
                <td class="val">{{ $manifiesto->conductor?->numero_licencia }}</td>
                <td class="lbl" style="white-space: nowrap;">CATEGOR&Iacute;A:</td>
                <td class="val">{{ $manifiesto->conductor?->categoria_licencia }}</td>
                <td class="lbl">PLACA:</td>
                <td class="val" style="width: 40%;">
                    <span>{{ $manifiesto->vehiculo?->placa }}</span>
                    <span style="float: right;"><strong style="color: #1e3a8a;">HORA:</strong> {{ $horaSalida ?? date('H:i') }}</span>
                </td>
            </tr>
        </table>

        <!-- 46 SEATS GRID TABLE -->
        <table class="grid">
            <thead>
                <tr>
                    <th style="width: 32px;">ASIENTO</th>
                    <th>APELLIDOS Y NOMBRES</th>
                    <th style="width: 65px;">DNI</th>
                    <th style="width: 105px;">EMPRESA</th>
                    <th style="width: 85px;">FIRMA</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 46; $i++)
                    @php
                        $detalle = $manifiesto->detalles->firstWhere('numero_asiento', $i) ?? ($manifiesto->detalles[$i - 1] ?? null);
                        $trab = $detalle?->trabajador;
                        $nombreCompleto = '';
                        if ($trab) {
                            $apellidos = trim(($trab->apellido_paterno ?? '') . ' ' . ($trab->apellido_materno ?? ''));
                            if (!$apellidos) {
                                $apellidos = $trab->apellidos ?? '';
                            }
                            $nombres = $trab->nombres ?? '';
                            $nombreCompleto = trim("$apellidos $nombres");
                        }
                    @endphp
                    <tr>
                        <td class="asiento">{{ $i }}</td>
                        <td class="pasajero-nombre">
                            {{ strtoupper($nombreCompleto) }}
                        </td>
                        <td class="dni">{{ $trab?->dni }}</td>
                        <td class="empresa">{{ $trab?->empresa?->razon_social }}</td>
                        <td class="firma"></td>
                    </tr>
                @endfor
            </tbody>
        </table>

        <div class="footer-conductor">
            <div class="signature-line">CONDUCTOR</div>
        </div>
    </div>

</body>
</html>