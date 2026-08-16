<?php

namespace App\Console\Commands;

use App\Models\Inscripcion;
use App\Models\ResponsablePago;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ConfigureInscripcionesFinancieras extends Command
{
    protected $signature = 'inscripciones:configurar-finanzas
        {--export= : Ruta del CSV de inscripciones pendientes}
        {--file= : Ruta del CSV que se validará o aplicará}
        {--user= : ID del administrador responsable}
        {--apply : Aplicar el archivo validado (por defecto sólo simula)}';

    protected $description = 'Exporta, valida y configura de manera controlada las finanzas de inscripciones históricas';

    private const HEADERS = [
        'inscripciones_id', 'prospectos_id', 'nombre_alumno', 'fecha_inscripcion', 'updated_at_original',
        'estatus', 'fecha_inicio', 'fecha_fin', 'moneda', 'monto_inscripcion', 'monto_mensualidad',
        'dia_vencimiento', 'numero_mensualidades', 'descuento', 'beca', 'observaciones_financieras',
        'responsable_opcion', 'responsable_pago_id',
    ];

    private const FINANCIAL_FIELDS = [
        'estatus', 'fecha_inicio', 'fecha_fin', 'moneda', 'monto_inscripcion', 'monto_mensualidad',
        'dia_vencimiento', 'numero_mensualidades', 'descuento', 'beca', 'observaciones_financieras',
    ];

    public function handle(): int
    {
        $user = User::find($this->option('user'));
        if (! $user || ! Gate::forUser($user)->allows('manage-inscripciones') || optional($user->role)->roles_codigo !== 'admin') {
            $this->error('El usuario indicado no es un administrador autorizado.');
            return self::FAILURE;
        }

        $export = $this->option('export');
        $file = $this->option('file');
        if (($export && $file) || (! $export && ! $file) || ($export && $this->option('apply'))) {
            $this->error('Indique exactamente una operación: --export o --file; --apply sólo es válido con --file.');
            return self::FAILURE;
        }

        return $export ? $this->export((string) $export) : $this->process((string) $file, $user);
    }

    private function export(string $path): int
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0775, true) && ! is_dir($directory)) {
            $this->error('No fue posible crear el directorio destino.');
            return self::FAILURE;
        }
        $stream = fopen($path, 'wb');
        if (! $stream) {
            $this->error('No fue posible abrir el archivo destino.');
            return self::FAILURE;
        }
        fwrite($stream, "\xEF\xBB\xBF");
        fputcsv($stream, self::HEADERS);
        $count = 0;
        Inscripcion::query()->financieramentePendientes()->with('prospecto')->orderBy('inscripciones_id')
            ->chunkById(200, function ($rows) use ($stream, &$count) {
                foreach ($rows as $row) {
                    fputcsv($stream, [
                        $row->getKey(), $row->prospectos_id,
                        trim(($row->prospecto->prospectos_nombres ?? '').' '.($row->prospecto->prospectos_apellidos ?? '')),
                        $this->date($row->fecha_inscripcion), $row->updated_at?->format('Y-m-d H:i:s'), $row->estatus,
                        $this->date($row->fecha_inicio), $this->date($row->fecha_fin), $row->moneda,
                        $row->monto_inscripcion, $row->monto_mensualidad, $row->dia_vencimiento,
                        $row->numero_mensualidades, $row->descuento, $row->beca, $row->observaciones_financieras,
                        $row->responsable_pago_id ? 'conservar' : '', $row->responsable_pago_id,
                    ]);
                    $count++;
                }
            }, 'inscripciones_id');
        fclose($stream);
        $this->info("Registros exportados: {$count}");
        Log::info('Exportación financiera de inscripciones', ['registros' => $count]);
        return self::SUCCESS;
    }

    private function process(string $path, User $user): int
    {
        try {
            $rows = $this->readCsv($path);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());
            return self::FAILURE;
        }

        $result = $this->validateRows($rows);
        $this->summary(count($rows), $result);
        Log::info('Validación financiera de inscripciones', [
            'modo' => $this->option('apply') ? 'aplicacion' : 'dry-run',
            'leidos' => count($rows), 'validos' => $result['valid'], 'sin_cambios' => $result['unchanged'],
            'errores' => $result['errors'], 'conflictos' => $result['conflicts'],
        ]);
        if ($result['errors'] || $result['conflicts']) {
            foreach ($result['messages'] as $message) $this->error($message);
            return self::FAILURE;
        }
        if (! $this->option('apply')) {
            $this->info('DRY-RUN: no se realizó ningún cambio.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows, $user) {
            $changes = [];
            foreach ($rows as $row) {
                $inscripcion = Inscripcion::query()->whereKey($row['inscripciones_id'])->lockForUpdate()->firstOrFail();
                [$values, $responsableId] = $this->desiredValues($row, $inscripcion, true);
                if ($this->same($inscripcion, $values, $responsableId)) continue;
                if ($this->timestamp($inscripcion) !== $row['updated_at_original']) {
                    throw new \RuntimeException('Se detectó una modificación posterior mientras se obtenían los bloqueos.');
                }
                $changes[] = [$inscripcion, $values, $responsableId];
            }
            foreach ($changes as [$inscripcion, $values, $responsableId]) {
                foreach ($values as $field => $value) $inscripcion->{$field} = $value;
                $inscripcion->responsable_pago_id = $responsableId;
                $inscripcion->updated_by = $user->getKey();
                $inscripcion->save();
                if (! $inscripcion->fresh()->financieramente_configurada) {
                    throw new \RuntimeException('La fila aplicada continuaría pendiente.');
                }
            }
        });
        $this->info('Archivo aplicado completamente.');
        Log::info('Configuración financiera de inscripciones aplicada', ['registros' => count($rows), 'usuario_id' => $user->getKey()]);
        return self::SUCCESS;
    }

    private function readCsv(string $path): array
    {
        $stream = @fopen($path, 'rb');
        if (! $stream) throw new \RuntimeException('No fue posible abrir el CSV.');
        $header = fgetcsv($stream);
        if ($header) $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
        if ($header !== self::HEADERS) throw new \RuntimeException('El encabezado CSV es inválido o contiene columnas desconocidas.');
        $rows = []; $line = 1;
        while (($values = fgetcsv($stream)) !== false) {
            $line++;
            if (count($values) !== count(self::HEADERS) || count(array_filter($values, fn ($v) => $v !== '')) === 0) {
                throw new \RuntimeException("La fila {$line} está vacía o tiene un número inválido de columnas.");
            }
            $rows[] = array_merge(array_combine(self::HEADERS, $values), ['_line' => $line]);
        }
        fclose($stream);
        if (! $rows) throw new \RuntimeException('El CSV no contiene filas de datos.');
        return $rows;
    }

    private function validateRows(array $rows): array
    {
        $result = ['valid' => 0, 'unchanged' => 0, 'errors' => 0, 'conflicts' => 0, 'messages' => []];
        $seen = [];
        foreach ($rows as $row) {
            $line = $row['_line']; $id = $row['inscripciones_id'];
            if (isset($seen[$id])) { $result['errors']++; $result['messages'][] = "Fila {$line}: ID duplicado."; continue; }
            $seen[$id] = true;
            $inscripcion = Inscripcion::find($id);
            if (! $inscripcion) { $result['errors']++; $result['messages'][] = "Fila {$line}: inscripción inexistente o eliminada."; continue; }
            $validator = Validator::make($row, $this->rules($row), [], []);
            if ($validator->fails()) { $result['errors']++; $result['messages'][] = "Fila {$line}: ".$validator->errors()->first(); continue; }
            if ((float) $row['descuento'] + (float) $row['beca'] > 100) {
                $result['errors']++; $result['messages'][] = "Fila {$line}: descuento más beca supera 100."; continue;
            }
            try { [$values, $responsableId] = $this->desiredValues($row, $inscripcion, false); }
            catch (\RuntimeException $e) { $result['errors']++; $result['messages'][] = "Fila {$line}: {$e->getMessage()}"; continue; }
            if ($this->same($inscripcion, $values, $responsableId)) { $result['unchanged']++; continue; }
            if ($this->timestamp($inscripcion) !== $row['updated_at_original']) {
                $result['conflicts']++; $result['messages'][] = "Fila {$line}: conflicto por modificación posterior."; continue;
            }
            $result['valid']++;
        }
        return $result;
    }

    private function rules(array $row): array
    {
        $positive = is_numeric($row['monto_mensualidad']) && (float) $row['monto_mensualidad'] > 0;
        $decimal = ['required', 'regex:/^\d{1,10}(?:\.\d{1,2})?$/'];
        return [
            'inscripciones_id' => 'required|integer|min:1', 'prospectos_id' => 'required|integer',
            'updated_at_original' => 'nullable|date_format:Y-m-d H:i:s', 'estatus' => 'required|in:activa,suspendida,finalizada,cancelada',
            'fecha_inicio' => 'required|date_format:Y-m-d', 'fecha_fin' => 'nullable|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'moneda' => 'required|in:MXN', 'monto_inscripcion' => $decimal, 'monto_mensualidad' => $decimal,
            'dia_vencimiento' => ($positive ? 'required' : 'nullable').'|integer|between:1,31',
            'numero_mensualidades' => ($positive ? 'required' : 'nullable').'|integer|between:1,120',
            'descuento' => $decimal, 'beca' => $decimal, 'observaciones_financieras' => 'nullable|string|max:2000',
            'responsable_opcion' => 'required|in:alumno,existente,conservar',
            'responsable_pago_id' => 'nullable|required_if:responsable_opcion,existente|integer',
        ];
    }

    private function desiredValues(array $row, Inscripcion $inscripcion, bool $create): array
    {
        if ((int) $row['prospectos_id'] !== (int) $inscripcion->prospectos_id) throw new \RuntimeException('el prospecto no corresponde a la inscripción.');
        $responsable = null;
        if ($row['responsable_opcion'] === 'conservar') $responsable = $inscripcion->responsablePago;
        elseif ($row['responsable_opcion'] === 'existente') $responsable = ResponsablePago::find($row['responsable_pago_id']);
        else {
            $responsable = ResponsablePago::where('prospectos_id', $inscripcion->prospectos_id)->where('activo', true)->first();
            if (! $responsable && $create) $responsable = ResponsablePago::activeForProspect($inscripcion->prospecto);
        }
        if ($row['responsable_opcion'] !== 'alumno' && (! $responsable || ! $responsable->activo)) throw new \RuntimeException('el responsable no existe o no está activo.');
        // During validation, -1 represents the student responsible that will be created atomically on apply.
        $responsableId = $responsable?->getKey() ?? ($row['responsable_opcion'] === 'alumno' && ! $create ? -1 : null);
        $values = [];
        foreach (self::FINANCIAL_FIELDS as $field) $values[$field] = $row[$field] === '' ? null : $row[$field];
        return [$values, $responsableId];
    }

    private function same(Inscripcion $inscripcion, array $values, ?int $responsableId): bool
    {
        foreach ($values as $field => $value) {
            $current = $inscripcion->{$field};
            if ($current instanceof \DateTimeInterface) $current = $current->format('Y-m-d');
            if (in_array($field, ['monto_inscripcion','monto_mensualidad','descuento','beca'], true) && $value !== null) {
                if (number_format((float) $current, 2, '.', '') !== number_format((float) $value, 2, '.', '')) return false;
            } elseif ((string) ($current ?? '') !== (string) ($value ?? '')) return false;
        }
        return (int) $inscripcion->responsable_pago_id === (int) $responsableId;
    }

    private function summary(int $read, array $result): void
    {
        $this->table(['Leídos','Válidos','Sin cambios','Errores','Conflictos'], [[$read,$result['valid'],$result['unchanged'],$result['errors'],$result['conflicts']]]);
    }

    private function timestamp(Inscripcion $row): string { return $row->updated_at?->format('Y-m-d H:i:s') ?? ''; }
    private function date($value): string { return $value?->format('Y-m-d') ?? ''; }
}
