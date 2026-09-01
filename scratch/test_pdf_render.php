<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Manifiesto;
use Barryvdh\DomPDF\Facade\Pdf;

$manifiesto = Manifiesto::with([
    'ruta',
    'vehiculo',
    'conductor.trabajador',
    'copiloto.trabajador',
    'detalles.trabajador.empresa'
])->first();

if (!$manifiesto) {
    echo "No manifesto found\n";
    exit;
}

$pdf = Pdf::loadView('pdf.manifiesto_preimpreso', [
    'manifiesto' => $manifiesto,
    'fechaSalida' => date('d/m/Y'),
    'horaSalida' => date('H:i'),
])->setPaper('a4', 'portrait');

$outputPath = __DIR__ . '/test_output.pdf';
file_put_contents($outputPath, $pdf->output());
echo "PDF successfully generated at: $outputPath\n";