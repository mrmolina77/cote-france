<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Prospecto;
use App\Models\ResponsablePago;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

class ConfigureInscripcionesFinancierasTest extends InscripcionesTestCase
{
    private array $temporaryFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->temporaryFiles as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    public function test_command_is_registered_and_rejects_unauthorized_users_without_changes(): void
    {
        $this->assertArrayHasKey('inscripciones:configurar-finanzas', Artisan::all());
        $path = $this->temporaryPath();
        $this->artisan('inscripciones:configurar-finanzas', ['--export' => $path, '--user' => 999])->assertExitCode(1);
        $ordinary = $this->commandUser('venta');
        $this->artisan('inscripciones:configurar-finanzas', ['--export' => $path, '--user' => $ordinary->getKey()])
            ->expectsOutputToContain('no es un administrador')->assertExitCode(1);
        $this->assertFileDoesNotExist($path);
    }

    public function test_command_validates_mutually_exclusive_options(): void
    {
        $admin = $this->commandUser('admin');
        $path = $this->temporaryPath();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey()])->assertExitCode(1);
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--export' => $path, '--file' => $path])->assertExitCode(1);
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--export' => $path, '--apply' => true])->assertExitCode(1);
    }

    public function test_export_contains_only_pending_rows_with_exact_headers_and_is_readable(): void
    {
        $admin = $this->commandUser('admin');
        [$pending, $configured] = $this->commandEnrollments();
        $path = $this->temporaryPath();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--export' => $path])
            ->expectsOutputToContain('Registros exportados: 1')->assertExitCode(0);
        $stream = fopen($path, 'rb');
        $headers = fgetcsv($stream); $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $row = array_combine($headers, fgetcsv($stream)); fclose($stream);
        $this->assertSame($this->headers(), $headers);
        $this->assertSame((string) $pending->getKey(), $row['inscripciones_id']);
        $this->assertSame((string) $pending->prospectos_id, $row['prospectos_id']);
        $this->assertSame('2026-08-16', $row['fecha_inscripcion']);
        $this->assertSame('MXN', $row['moneda']);
        $this->assertSame($pending->updated_at->format('Y-m-d H:i:s'), $row['updated_at_original']);
        $this->assertNotSame((string) $configured->getKey(), $row['inscripciones_id']);
        foreach (array_combine($headers, $this->validRow($pending, 'alumno')) as $column => $value) {
            if (in_array($column, ['estatus', 'fecha_inicio', 'fecha_fin', 'monto_inscripcion', 'monto_mensualidad', 'dia_vencimiento', 'numero_mensualidades', 'descuento', 'beca', 'observaciones_financieras', 'responsable_opcion', 'responsable_pago_id'], true)) {
                $row[$column] = $value;
            }
        }
        $stream = fopen($path, 'wb');
        fputcsv($stream, $headers);
        fputcsv($stream, array_map(fn ($header) => $row[$header], $headers));
        fclose($stream);
        $before = $pending->fresh()->toArray();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $path])
            ->expectsOutputToContain('DRY-RUN')->assertExitCode(0);
        $this->assertSame($before, $pending->fresh()->toArray());
    }

    public function test_csv_structure_and_row_identity_validation_fail_safely(): void
    {
        $admin = $this->commandUser('admin');
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => '/missing.csv'])->assertExitCode(1);
        foreach ([[], [['bad']], [$this->headers()], [$this->headers(), array_fill(0, 17, '')]] as $contents) {
            $path = $this->csv($contents);
            $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $path])->assertExitCode(1);
        }
        [$pending] = $this->commandEnrollments();
        $valid = $this->validRow($pending);
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $valid, $valid])])
            ->expectsOutputToContain('duplicado')->assertExitCode(1);
        $valid[0] = 99999;
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $valid])])->assertExitCode(1);
    }

    /** @dataProvider invalidCsvValues */
    public function test_csv_rejects_invalid_financial_values(array $changes): void
    {
        $admin = $this->commandUser('admin');
        [$pending] = $this->commandEnrollments();
        $row = $this->validRow($pending);
        foreach ($changes as $column => $value) $row[$column] = $value;
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row])])
            ->assertExitCode(1);
        $this->assertNull($pending->fresh()->estatus);
    }

    public function invalidCsvValues(): array
    {
        return [
            'unknown status' => [[5 => 'bad']], 'end before start' => [[7 => '2020-01-01']],
            'negative enrollment amount' => [[9 => '-1']], 'amount too large' => [[9 => '10000000000.00']],
            'monthly amount too large' => [[10 => '10000000000.00']],
            'enrollment amount scale' => [[9 => '1.001']], 'monthly amount scale' => [[10 => '1.001']],
            'negative monthly amount' => [[10 => '-1']], 'monthly missing day' => [[11 => '']],
            'monthly missing installments' => [[12 => '']], 'day zero' => [[11 => '0']], 'day 32' => [[11 => '32']],
            'installments zero' => [[12 => '0']], 'installments 121' => [[12 => '121']],
            'negative discount' => [[13 => '-1']], 'discount over 100' => [[13 => '101']],
            'negative scholarship' => [[14 => '-1']], 'scholarship over 100' => [[14 => '101']],
            'combined over 100' => [[13 => '60', 14 => '41']],
        ];
    }

    public function test_csv_rejects_wrong_prospect_and_soft_deleted_enrollment_without_changes(): void
    {
        $admin = $this->commandUser('admin');
        [$pending] = $this->commandEnrollments();
        $row = $this->validRow($pending);
        $row[1] = $pending->prospectos_id + 999;
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row]), '--apply' => true])
            ->expectsOutputToContain('no corresponde')->assertExitCode(1);
        $this->assertNull($pending->fresh()->estatus);

        $row = $this->validRow($pending);
        $pending->delete();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row]), '--apply' => true])
            ->expectsOutputToContain('inexistente o eliminada')->assertExitCode(1);
        $this->assertNull($pending->fresh()->estatus);
    }

    public function test_existing_responsible_must_exist_and_be_active_and_exact_active_one_is_applied(): void
    {
        $admin = $this->commandUser('admin');
        [$pending] = $this->commandEnrollments();
        foreach ([999999, ResponsablePago::create(['tipo' => 'persona', 'nombre_razon_social' => 'Inactivo', 'activo' => false])->getKey()] as $id) {
            $row = $this->validRow($pending);
            $row[17] = $id;
            $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row]), '--apply' => true])->assertExitCode(1);
            $this->assertNull($pending->fresh()->estatus);
        }
        $active = ResponsablePago::create(['tipo' => 'persona', 'nombre_razon_social' => 'Tutor activo', 'activo' => true]);
        $row = $this->validRow($pending);
        $row[17] = $active->getKey();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row]), '--apply' => true])->assertExitCode(0);
        $this->assertSame($active->getKey(), $pending->fresh()->responsable_pago_id);
    }

    public function test_student_option_reuses_active_responsible_without_duplicates(): void
    {
        $admin = $this->commandUser('admin');
        [$pending] = $this->commandEnrollments();
        $responsible = ResponsablePago::activeForProspect($pending->prospecto);
        $count = ResponsablePago::count();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $this->validRow($pending, 'alumno')]), '--apply' => true])->assertExitCode(0);
        $this->assertSame($responsible->getKey(), $pending->fresh()->responsable_pago_id);
        $this->assertSame($count, ResponsablePago::count());
    }

    /** @dataProvider validBoundaryRows */
    public function test_csv_accepts_financial_boundaries_and_allowed_statuses(string $status, string $monthly, string $day, string $installments): void
    {
        $admin = $this->commandUser('admin');
        [$pending] = $this->commandEnrollments();
        $row = $this->validRow($pending);
        $row[5] = $status; $row[7] = ''; $row[10] = $monthly; $row[11] = $day; $row[12] = $installments;
        $row[13] = '40'; $row[14] = '60';
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row])])->assertExitCode(0);
        $this->assertNull($pending->fresh()->estatus);
    }

    public function validBoundaryRows(): array
    {
        return [
            'active with zero monthly' => ['activa', '0.00', '', ''],
            'suspended maximum schedule' => ['suspendida', '1.00', '31', '120'],
            'finished' => ['finalizada', '200.00', '15', '12'],
            'cancelled' => ['cancelada', '200.00', '15', '12'],
        ];
    }

    public function test_dry_run_does_not_change_data_or_create_responsibles(): void
    {
        $admin = $this->commandUser('admin'); [$pending] = $this->commandEnrollments();
        $before = $pending->updated_at; $count = ResponsablePago::count();
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $this->validRow($pending, 'alumno')])])
            ->expectsOutputToContain('DRY-RUN')->assertExitCode(0);
        $this->assertNull($pending->fresh()->estatus);
        $this->assertNull($pending->fresh()->updated_by);
        $this->assertEquals($before, $pending->fresh()->updated_at);
        $this->assertSame($count, ResponsablePago::count());
    }

    public function test_apply_is_audited_configured_and_idempotent(): void
    {
        $admin = $this->commandUser('admin'); [$pending] = $this->commandEnrollments();
        $creator = $this->commandUser('admin');
        $pending->update(['created_by' => $creator->getKey()]);
        $pending->refresh();
        $path = $this->csv([$this->headers(), $this->validRow($pending, 'alumno')]);
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $path, '--apply' => true])
            ->expectsOutputToContain('completamente')->assertExitCode(0);
        $fresh = $pending->fresh();
        $this->assertTrue($fresh->financieramente_configurada);
        $this->assertSame('activa', $fresh->estatus);
        $this->assertSame('2026-08-16', $fresh->fecha_inicio->format('Y-m-d'));
        $this->assertNull($fresh->fecha_fin);
        $this->assertSame('MXN', $fresh->moneda);
        $this->assertSame('100.00', $fresh->monto_inscripcion);
        $this->assertSame('200.00', $fresh->monto_mensualidad);
        $this->assertSame(15, $fresh->dia_vencimiento);
        $this->assertSame(12, $fresh->numero_mensualidades);
        $this->assertSame('40.00', $fresh->descuento);
        $this->assertSame('60.00', $fresh->beca);
        $this->assertSame('CSV', $fresh->observaciones_financieras);
        $this->assertNotNull($fresh->responsable_pago_id);
        $this->assertSame($creator->getKey(), $fresh->created_by);
        $this->assertSame($admin->getKey(), $fresh->updated_by);
        $count = ResponsablePago::count(); $updatedAt = $fresh->updated_at;
        $row = $this->validRow($fresh, 'alumno');
        $row[4] = $fresh->updated_at->format('Y-m-d H:i:s');
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row]), '--apply' => true])->assertExitCode(0);
        $this->assertSame($count, ResponsablePago::count());
        $this->assertEquals($updatedAt, $fresh->fresh()->updated_at);
        $this->assertSame($admin->getKey(), $fresh->fresh()->updated_by);
    }

    public function test_concurrency_conflict_does_not_overwrite_later_changes(): void
    {
        $admin = $this->commandUser('admin'); [$pending] = $this->commandEnrollments();
        $row = $this->validRow($pending, 'alumno');
        $pending->update(['observaciones_financieras' => 'cambio posterior', 'updated_at' => now()->addMinute()]);
        $this->artisan('inscripciones:configurar-finanzas', ['--user' => $admin->getKey(), '--file' => $this->csv([$this->headers(), $row]), '--apply' => true])
            ->expectsOutputToContain('conflicto')->assertExitCode(1);
        $this->assertSame('cambio posterior', $pending->fresh()->observaciones_financieras);
    }

    public function test_application_failure_rolls_back_every_enrollment_and_created_responsible(): void
    {
        $admin = $this->commandUser('admin');
        [$first] = $this->commandEnrollments();
        $student = Prospecto::create(['prospectos_nombres' => 'Segunda', 'prospectos_telefono1' => '3']);
        $second = Inscripcion::create([
            'fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $student->getKey(),
            'cursos_id' => $first->cursos_id, 'grupo_id' => $first->grupo_id,
        ]);
        $responsibleCount = ResponsablePago::count();
        $path = $this->csv([$this->headers(), $this->validRow($first, 'alumno'), $this->validRow($second, 'alumno')]);
        $failUpdates = true;
        Inscripcion::updating(function (Inscripcion $enrollment) use ($second, &$failUpdates) {
            if ($failUpdates && $enrollment->is($second)) {
                throw new \RuntimeException('fallo simulado');
            }
        });

        try {
            $this->artisan('inscripciones:configurar-finanzas', [
                '--user' => $admin->getKey(), '--file' => $path, '--apply' => true,
            ])->expectsOutputToContain('No fue posible aplicar el archivo: fallo simulado')->assertExitCode(1);
        } finally {
            $failUpdates = false;
        }

        $this->assertNull($first->fresh()->estatus);
        $this->assertNull($second->fresh()->estatus);
        $this->assertSame($responsibleCount, ResponsablePago::count());
    }

    private function commandUser(string $roleCode): User
    {
        $role = Role::create(['roles_codigo' => $roleCode, 'roles_nombre' => ucfirst($roleCode)]);
        return User::factory()->create(['roles_id' => $role->getKey()]);
    }

    private function commandEnrollments(): array
    {
        $course = Curso::create(['cursos_descripcion' => 'Francés', 'cursos_fecha_creacion' => '2026-08-16']);
        $group = Grupo::create(['grupo_nombre' => 'A1', 'modalidad_id' => 1]);
        $studentA = Prospecto::create(['prospectos_nombres' => 'Pendiente', 'prospectos_telefono1' => '1']);
        $studentB = Prospecto::create(['prospectos_nombres' => 'Configurado', 'prospectos_telefono1' => '2']);
        $pending = Inscripcion::create(['fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $studentA->getKey(), 'cursos_id' => $course->getKey(), 'grupo_id' => $group->getKey()]);
        $responsible = ResponsablePago::activeForProspect($studentB);
        $configured = Inscripcion::create(array_merge($this->financialValues(), ['fecha_inscripcion' => '2026-08-16', 'prospectos_id' => $studentB->getKey(), 'cursos_id' => $course->getKey(), 'grupo_id' => $group->getKey(), 'responsable_pago_id' => $responsible->getKey()]));
        return [$pending, $configured];
    }

    private function financialValues(): array
    {
        return ['estatus' => 'activa', 'fecha_inicio' => '2026-08-16', 'fecha_fin' => '', 'moneda' => 'MXN', 'monto_inscripcion' => '100.00', 'monto_mensualidad' => '200.00', 'dia_vencimiento' => 15, 'numero_mensualidades' => 12, 'descuento' => '40.00', 'beca' => '60.00', 'observaciones_financieras' => 'CSV'];
    }

    private function validRow(Inscripcion $enrollment, string $option = 'existente'): array
    {
        $responsible = $enrollment->responsable_pago_id ?: ResponsablePago::where('activo', true)->first()->getKey();
        return [$enrollment->getKey(), $enrollment->prospectos_id, 'Alumno', '2026-08-16', $enrollment->updated_at->format('Y-m-d H:i:s'), 'activa', '2026-08-16', '', 'MXN', '100.00', '200.00', 15, 12, '40.00', '60.00', 'CSV', $option, $option === 'alumno' ? '' : $responsible];
    }

    private function csv(array $rows): string
    {
        $path = $this->temporaryPath(); $stream = fopen($path, 'wb');
        foreach ($rows as $row) fputcsv($stream, $row);
        fclose($stream); return $path;
    }

    private function temporaryPath(): string
    {
        $path = tempnam(sys_get_temp_dir(), 'cf-finance-'); @unlink($path);
        $this->temporaryFiles[] = $path; return $path;
    }

    private function headers(): array
    {
        return ['inscripciones_id', 'prospectos_id', 'nombre_alumno', 'fecha_inscripcion', 'updated_at_original', 'estatus', 'fecha_inicio', 'fecha_fin', 'moneda', 'monto_inscripcion', 'monto_mensualidad', 'dia_vencimiento', 'numero_mensualidades', 'descuento', 'beca', 'observaciones_financieras', 'responsable_opcion', 'responsable_pago_id'];
    }
}
