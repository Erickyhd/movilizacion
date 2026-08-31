<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

date_default_timezone_set('America/Lima');
$now = date('Y-m-d H:i:s');

foreach (App\Models\Manifiesto::all() as $m) {
    $m->update([
        'fecha_salida_programada' => $now
    ]);
}

echo "Manifest dates updated to local time successfully!\n";