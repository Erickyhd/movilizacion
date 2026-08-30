<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$rutas = App\Models\Ruta::all();
foreach ($rutas as $r) {
    echo "ID: {$r->id} | Origen: {$r->origen} | Depto: {$r->departamento}\n";
}