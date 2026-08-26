<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Trabajador;
use App\Models\DocumentoTrabajador;
use App\Models\Vehiculo;
use App\Models\Conductor;
use App\Models\Ruta;
use App\Models\Manifiesto;
use App\Models\ManifiestoDetalle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Admin User
        $admin = User::create([
            'name' => 'Admin Operaciones',
            'email' => 'admin@movilizacion.local',
            'password' => Hash::make('admin1234'),
        ]);

        // 2. Empresas
        $empresaPrincipal = Empresa::create([
            'ruc' => '20123456789',
            'razon_social' => 'Consorcio Minero del Sur S.A.C.',
            'es_contratista' => false,
        ]);

        $contratista1 = Empresa::create([
            'ruc' => '20987654321',
            'razon_social' => 'Constructora e Inversiones Andes E.I.R.L.',
            'es_contratista' => true,
        ]);

        $contratista2 = Empresa::create([
            'ruc' => '20555444333',
            'razon_social' => 'Servicios Logísticos del Norte S.R.L.',
            'es_contratista' => true,
        ]);

        // 3. Trabajadores
        $t1 = Trabajador::create([
            'empresa_id' => $empresaPrincipal->id,
            'dni' => '71234567',
            'nombres' => 'Carlos Alberto',
            'apellidos' => 'Mendoza Ríos',
            'cargo' => 'Ingeniero de Residencia',
            'grupo_sanguineo' => 'O+',
            'telefono_emergencia' => '987654321',
            'estado_acreditacion' => 'APTO',
        ]);

        $t2 = Trabajador::create([
            'empresa_id' => $contratista1->id,
            'dni' => '72345678',
            'nombres' => 'Juan Diego',
            'apellidos' => 'Salazar Quispe',
            'cargo' => 'Operador de Maquinaria Heavy',
            'grupo_sanguineo' => 'A+',
            'telefono_emergencia' => '912345678',
            'estado_acreditacion' => 'APTO',
        ]);

        $t3 = Trabajador::create([
            'empresa_id' => $contratista1->id,
            'dni' => '73456789',
            'nombres' => 'Luis Fernando',
            'apellidos' => 'Gómez Huamán',
            'cargo' => 'Técnico Electricista',
            'grupo_sanguineo' => 'O-',
            'telefono_emergencia' => '923456789',
            'estado_acreditacion' => 'OBSERVADO',
        ]);

        $t4 = Trabajador::create([
            'empresa_id' => $contratista2->id,
            'dni' => '74567890',
            'nombres' => 'Jorge Luis',
            'apellidos' => 'Pérez Ramos',
            'cargo' => 'Conductor Profesional',
            'grupo_sanguineo' => 'B+',
            'telefono_emergencia' => '934567890',
            'estado_acreditacion' => 'APTO',
        ]);

        // 4. Documentos HSEQ
        DocumentoTrabajador::create([
            'trabajador_id' => $t1->id,
            'tipo_documento' => 'EMO',
            'fecha_emision' => now()->subMonths(2)->format('Y-m-d'),
            'fecha_vencimiento' => now()->addMonths(10)->format('Y-m-d'),
            'es_vigente' => true,
        ]);

        DocumentoTrabajador::create([
            'trabajador_id' => $t2->id,
            'tipo_documento' => 'PASE_INGRESO',
            'fecha_emision' => now()->subMonths(1)->format('Y-m-d'),
            'fecha_vencimiento' => now()->addMonths(5)->format('Y-m-d'),
            'es_vigente' => true,
        ]);

        // 5. Vehículos
        $v1 = Vehiculo::create([
            'empresa_id' => $contratista2->id,
            'placa' => 'F1A-892',
            'marca_modelo' => 'Volvo Bus B450R 6x2',
            'capacidad_pasajeros' => 45,
            'soat_vencimiento' => now()->addMonths(8)->format('Y-m-d'),
            'rt_vencimiento' => now()->addMonths(6)->format('Y-m-d'),
            'activo' => true,
        ]);

        $v2 = Vehiculo::create([
            'empresa_id' => $contratista2->id,
            'placa' => 'B9C-410',
            'marca_modelo' => 'Toyota HiAce VIP 4x4',
            'capacidad_pasajeros' => 15,
            'soat_vencimiento' => now()->addMonths(11)->format('Y-m-d'),
            'rt_vencimiento' => now()->addMonths(9)->format('Y-m-d'),
            'activo' => true,
        ]);

        // 6. Conductor
        $conductor = Conductor::create([
            'trabajador_id' => $t4->id,
            'numero_licencia' => 'Q-74567890',
            'categoria_licencia' => 'A-IIIc',
            'brevete_interno_vencimiento' => now()->addYear()->format('Y-m-d'),
            'activo' => true,
        ]);

        // 7. Rutas
        $r1 = Ruta::create([
            'origen' => 'Arequipa (Base Central)',
            'destino' => 'Mina Las Bambas (Campamento 1)',
            'duracion_estimada_minutos' => 360,
            'activa' => true,
        ]);

        $r2 = Ruta::create([
            'origen' => 'Cusco (Aeropuerto)',
            'destino' => 'Espinar (Proyecto Antapaccay)',
            'duracion_estimada_minutos' => 240,
            'activa' => true,
        ]);

        // 8. Manifiesto (Cabecera y Detalle)
        $manifiesto = Manifiesto::create([
            'codigo_manifiesto' => 'MNF-2026-001',
            'ruta_id' => $r1->id,
            'vehiculo_id' => $v1->id,
            'conductor_id' => $conductor->id,
            'fecha_salida_programada' => now()->addHours(3),
            'estado' => 'CONFIRMADO',
            'codigo_qr_token' => Str::random(32),
            'creado_por' => $admin->id,
        ]);

        ManifiestoDetalle::create([
            'manifiesto_id' => $manifiesto->id,
            'trabajador_id' => $t1->id,
            'numero_asiento' => 1,
            'estado_embarque' => 'PENDIENTE',
            'observacion' => 'Pase VIP Acreditado',
        ]);

        ManifiestoDetalle::create([
            'manifiesto_id' => $manifiesto->id,
            'trabajador_id' => $t2->id,
            'numero_asiento' => 2,
            'estado_embarque' => 'PENDIENTE',
            'observacion' => 'Equipaje de mano y herramientas',
        ]);
    }
}