<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>MANIFIESTO PREIMPRESO - {{ $manifiesto->codigo_manifiesto }}</title>
    @php
        /*
         * CÃLCULO DE DIMENSIONES PARA AJUSTE EXACTO EN 1 HOJA OFICIO / LEGAL
         * ------------------------------------------------------------------
         * DomPDF: 96 DPI por defecto.
         * Papel Legal: 14 pulgadas de alto (1344 px / 1008 pt).
         * 46 filas calibradas con padding de 6.4px para llenar elegantemente
         * toda la hoja de arriba a abajo, dejando el espacio justo para la firma.
         */
        $padVert    = 6.4;  // Padding vertical ampliado para ocupar toda la pÃ¡gina
        $fontRow    = 8.0;  // TamaÃ±o de fuente nÃ­tido y legible
        $topBlankMm = 44;   // Espacio superior reservado
    @endphp
    <style>
        /* ===========================================================
           PAPEL OFICIO / LEGAL â€“ EXACTAMENTE 1 SOLA HOJA
        =========================================================== */
        @page {
            size: legal portrait;
            margin: 0;
        }
        html, body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 8px;
            color: #1e293b;
            margin: 0;
            padding: 0;
            background: #fff;
            -webkit-print-color-adjust: exact;
        }

        /* Espacio superior para el formato pre-impreso */
        .top-blank-reservation {
            height: {{ $topBlankMm }}mm;
            width: 100%;
        }

        .content-container {
            padding: 0 12mm 3mm 12mm;
            box-sizing: border-box;
        }

        /* ===========================================================
           CABECERA: 3 FILAS x 3 DATOS
        =========================================================== */
        table.header-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
            border-bottom: 2px solid #1e40af;
        }
        table.header-info tr {
            height: 14px;
        }
        table.header-info td {
            padding: 1px 0;
            font-size: 8px;
            vertical-align: middle;
            border: none;
            white-space: nowrap;
        }
        table.header-info .lbl {
            color: #1e3a8a;
            font-weight: 800;
            text-transform: uppercase;
            padding-right: 3px;
            width: 1%;
        }
        table.header-info .val {
            color: #0f172a;
            font-weight: 700;
            padding-left: 2px;
            padding-right: 10px;
            overflow: hidden;
        }
        table.header-info .val-last {
            color: #0f172a;
            font-weight: 700;
            padding-left: 2px;
        }

        /* ===========================================================
           TABLA DE 46 ASIENTOS â€“ ANCHOS DIRECTOS EN TH/TD PARA DOMPDF
        =========================================================== */
        table.grid {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            border: 1.5px solid #2563eb;
        }

        /* Anchos obligatorios en TH para que DomPDF los respete al 100% */
        table.grid th.col-asiento,
        table.grid td.asiento {
            width: 5.5%;
        }
        table.grid th.col-nombre,
        table.grid td.pasajero-nombre {
            width: 44.5%;
        }
        table.grid th.col-dni,
        table.grid td.dni {
            width: 9%;
        }
        table.grid th.col-empresa,
        table.grid td.empresa {
            width: 25%;
        }
        table.grid th.col-firma,
        table.grid td.firma {
            width: 16%;
        }

        table.grid th {
            background-color: #bfdbfe;
            color: #1e3a8a;
            font-weight: 800;
            text-transform: uppercase;
            padding: 2.5px 2px;
            border: 1px solid #60a5fa;
            text-align: center;
            font-size: 7.5px;
            overflow: hidden;
            line-height: 1.1;
        }
        table.grid th.left { text-align: left; padding-left: 5px; }

        table.grid td {
            border: 1px solid #93c5fd;
            padding-top: {{ $padVert }}px;
            padding-bottom: {{ $padVert }}px;
            padding-left: 3px;
            padding-right: 3px;
            vertical-align: middle;
            overflow: hidden;
            white-space: nowrap;
            font-size: {{ $fontRow }}px;
            line-height: 1.1;
        }

        /* Columna ASIENTO â€“ fondo celeste suave igual que header */
        table.grid td.asiento {
            text-align: center;
            font-weight: 800;
            color: #1e3a8a;
            font-size: 7.5px;
            background-color: #dbeafe;
            padding-left: 0;
            padding-right: 0;
        }
        table.grid td.pasajero-nombre {
            font-weight: 700;
            text-transform: uppercase;
            padding-left: 5px;
        }
        table.grid td.dni {
            text-align: center;
            font-family: monospace;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        table.grid td.empresa {
            text-transform: uppercase;
            padding-left: 3px;
        }
        table.grid td.firma {
            /* VacÃ­o para firma manuscrita del pasajero */
        }

        /* Firma del conductor al pie */
        .footer-conductor {
            margin-top: 8px;
            text-align: center;
        }
        .signature-line {
            display: inline-block;
            width: 220px;
            border-top: 1.5px solid #1e40af;
            padding-top: 3px;
            font-weight: 800;
            font-size: 8.5px;
            color: #1e3a8a;
            text-transform: uppercase;
        }
    </style>
</head>
<body>

    <div class="top-blank-reservation"></div>

    <div class="content-container">

        @php
            /* ---- Datos conductor ---- */
            $cond     = $manifiesto->conductor;
            $condTrab = $cond?->trabajador;
            $condAp   = trim(($cond?->apellido_paterno ?? $condTrab?->apellido_paterno ?? '') . ' ' . ($cond?->apellido_materno ?? $condTrab?->apellido_materno ?? ''));
            if (!$condAp) $condAp = $condTrab?->apellidos ?? '';
            $condNom  = $cond?->nombres ?? $condTrab?->nombres ?? '';
            $condFull = strtoupper(trim("$condAp $condNom"));

            /* ---- Datos copiloto ---- */
            $cop      = $manifiesto->copiloto;
            $copTrab  = $cop?->trabajador;
            $copAp    = trim(($cop?->apellido_paterno ?? $copTrab?->apellido_paterno ?? '') . ' ' . ($cop?->apellido_materno ?? $copTrab?->apellido_materno ?? ''));
            if (!$copAp) $copAp = $copTrab?->apellidos ?? '';
            $copNom   = $cop?->nombres ?? $copTrab?->nombres ?? '';
            $copFull  = strtoupper(trim("$copAp $copNom"));

            /* ---- Otros datos ---- */
            $licencia  = $manifiesto->conductor?->numero_licencia ?? '-';
            $categoria = $manifiesto->conductor?->categoria_licencia ?? '-';
            $placa     = strtoupper($manifiesto->vehiculo?->placa ?? '-');
            $hora      = $horaSalida ?? date('H:i');
            $origen    = strtoupper($manifiesto->ruta?->origen ?? '-');
            $destino   = strtoupper($manifiesto->ruta?->destino ?? '-');
            $fecha     = $fechaSalida ?? date('d/m/Y');
        @endphp

        <!-- CABECERA: 3 FILAS x 3 DATOS -->
        <table class="header-info">
            <colgroup>
                <col style="width:12%;" />
                <col style="width:21%;" />
                <col style="width:8%;"  />
                <col style="width:21%;" />
                <col style="width:8%;"  />
                <col style="width:30%;" />
            </colgroup>
            <tr>
                <td class="lbl">Fecha Salida:</td>
                <td class="val">{{ $fecha }}</td>
                <td class="lbl">Origen:</td>
                <td class="val">{{ $origen }}</td>
                <td class="lbl">Destino:</td>
                <td class="val-last">{{ $destino }}</td>
            </tr>
            <tr>
                <td class="lbl">Conductor:</td>
                <td class="val">{{ $condFull }}</td>
                <td class="lbl">Copiloto:</td>
                <td class="val">{{ $copFull }}</td>
                <td class="lbl">N&ordm; Lic.:</td>
                <td class="val-last">{{ $licencia }}</td>
            </tr>
            <tr>
                <td class="lbl">Categor&iacute;a:</td>
                <td class="val">{{ $categoria }}</td>
                <td class="lbl">Placa:</td>
                <td class="val">{{ $placa }}</td>
                <td class="lbl">Hora:</td>
                <td class="val-last">{{ $hora }}</td>
            </tr>
        </table>

        <!-- TABLA DE 46 ASIENTOS -->
        <table class="grid">
            <thead>
                <tr>
                    <th class="col-asiento" style="width: 5.5%;">ASIENTO</th>
                    <th class="col-nombre left" style="width: 44.5%;">APELLIDOS Y NOMBRES</th>
                    <th class="col-dni" style="width: 9%;">DNI</th>
                    <th class="col-empresa" style="width: 25%;">EMPRESA</th>
                    <th class="col-firma" style="width: 16%;">FIRMA</th>
                </tr>
            </thead>
            <tbody>
                @for ($i = 1; $i <= 46; $i++)
                    @php
                        $detalle = $manifiesto->detalles->firstWhere('numero_asiento', $i)
                                   ?? ($manifiesto->detalles[$i - 1] ?? null);
                        $trab    = $detalle?->trabajador;
                        $nomComp = '';
                        if ($trab) {
                            $ap = trim(($trab->apellido_paterno ?? '') . ' ' . ($trab->apellido_materno ?? ''));
                            if (!$ap) $ap = $trab->apellidos ?? '';
                            $nomComp = strtoupper(trim("$ap " . ($trab->nombres ?? '')));
                        }
                    @endphp
                    <tr>
                        <td class="asiento">{{ $i }}</td>
                        <td class="pasajero-nombre">{{ $nomComp }}</td>
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