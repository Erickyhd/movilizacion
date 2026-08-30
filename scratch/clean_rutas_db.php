<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete dummy test routes like 'yutf'
App\Models\Ruta::whereRaw("LOWER(origen) LIKE '%yutf%' OR origen = ''")->delete();

// Ensure clean default catalog points exist if empty or minimal
$pointsToEnsure = [
    ['origen' => 'HUANCAYO', 'departamento' => 'JUNÍN', 'destino' => 'HUANCAYO'],
    ['origen' => 'CAMPAMENTO CARMEN', 'departamento' => 'JUNÍN', 'destino' => 'CAMPAMENTO CARMEN'],
    ['origen' => 'AREQUIPA', 'departamento' => 'AREQUIPA', 'destino' => 'AREQUIPA'],
    ['origen' => 'CUSCO', 'departamento' => 'CUSCO', 'destino' => 'CUSCO'],
    ['origen' => 'LIMA', 'departamento' => 'LIMA', 'destino' => 'LIMA'],
];

foreach ($pointsToEnsure as $p) {
    App\Models\Ruta::firstOrCreate(
        ['origen' => $p['origen']],
        ['departamento' => $p['departamento'], 'destino' => $p['destino'], 'duracion_estimada_minutos' => 120, 'activa' => true]
    );
}

echo "Rutas DB cleaned and verified cleanly!\n";