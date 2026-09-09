<?php

namespace App\Http\Livewire;

use App\Models\Cargo;
use App\Models\Inscripcion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class RegistrarPago extends Component
{
    public $busqueda = '';
    public $inscripcionSeleccionadaId;
    public $cargosSeleccionados = [];
    public $importesAplicar = [];

    public function mount(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function updatedBusqueda(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function seleccionarInscripcion($inscripcionId): void
    {
        Gate::authorize('manage-pagos');
        $this->limpiarDatosSeleccionados();
        $this->resetErrorBag();
        $this->resetValidation();

        $inscripcionId = $this->normalizarInscripcionId($inscripcionId);
        abort_unless($inscripcionId !== null, 404);

        $inscripcion = Inscripcion::query()
            ->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])
            ->find($inscripcionId);

        abort_unless($inscripcion && $inscripcion->prospecto, 404);

        $this->inscripcionSeleccionadaId = $inscripcion->getKey();
    }

    public function limpiarSeleccion(): void
    {
        Gate::authorize('manage-pagos');
        $this->limpiarDatosSeleccionados();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function seleccionarCargo($cargoId): void
    {
        Gate::authorize('manage-pagos');
        $cargoId = $this->normalizarId($cargoId);
        $cargo = $cargoId === null ? null : $this->consultarCargoElegible($cargoId);

        if (! $cargo) {
            $this->addError('cargosSeleccionados', 'El cargo seleccionado ya no está disponible.');
            return;
        }

        $key = (string) $cargo->getKey();
        if (! in_array($cargo->getKey(), $this->cargosSeleccionados, true)) {
            $this->cargosSeleccionados[] = $cargo->getKey();
        }
        $this->importesAplicar[$key] = $cargo->saldo_pendiente;
        $this->resetErrorBag('importesAplicar.'.$key);
    }

    public function deseleccionarCargo($cargoId): void
    {
        Gate::authorize('manage-pagos');
        $cargoId = $this->normalizarId($cargoId);
        if ($cargoId === null) {
            $this->addError('cargosSeleccionados', 'El identificador del cargo no es válido.');
            return;
        }

        $this->cargosSeleccionados = array_values(array_filter(
            $this->normalizarIdsSeleccionados(), fn (int $id) => $id !== $cargoId
        ));
        unset($this->importesAplicar[(string) $cargoId], $this->importesAplicar[$cargoId]);
        $this->resetErrorBag('importesAplicar.'.$cargoId);
    }

    public function seleccionarTodosCargos(): void
    {
        Gate::authorize('manage-pagos');
        $inscripcionId = $this->normalizarId($this->inscripcionSeleccionadaId);
        if ($inscripcionId === null || ! $this->inscripcionPersistida($inscripcionId)) {
            $this->limpiarSeleccionCargosInterno();
            return;
        }

        $cargos = $this->consultaCargosAbiertos($inscripcionId)->get();
        $this->cargosSeleccionados = $cargos->modelKeys();
        $this->importesAplicar = $cargos->mapWithKeys(fn (Cargo $cargo) => [(string) $cargo->getKey() => $cargo->saldo_pendiente])->all();
        $this->resetErrorBag();
    }

    public function limpiarSeleccionCargos(): void
    {
        Gate::authorize('manage-pagos');
        $this->limpiarSeleccionCargosInterno();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatedImportesAplicar($value, $key): void
    {
        Gate::authorize('manage-pagos');
        $id = $this->normalizarId($key);
        if ($id === null || ! in_array($id, $this->normalizarIdsSeleccionados(), true)) {
            unset($this->importesAplicar[$key]);
            $this->addError('importesAplicar.'.$key, 'El cargo no forma parte de la selección válida.');
            return;
        }

        $cargo = $this->consultarCargoElegible($id);
        $centavos = $this->importeACentavos($value);
        if (! $cargo) {
            $this->retirarCargoObsoleto($id, 'El cargo seleccionado ya no está disponible.');
        } elseif ($centavos === null || $centavos <= 0) {
            $this->addError('importesAplicar.'.$id, 'El importe debe ser mayor a 0.00 y tener máximo dos decimales.');
        } elseif ($centavos > $this->importeACentavos($cargo->saldo_pendiente)) {
            $this->addError('importesAplicar.'.$id, 'El importe no puede superar el saldo pendiente actual.');
        } else {
            $this->importesAplicar[(string) $id] = $this->centavosAImporte($centavos);
            $this->resetErrorBag('importesAplicar.'.$id);
        }
    }

    public function prepararPago(): void
    {
        Gate::authorize('manage-pagos');
        $this->reconciliarSeleccion();
        if ($this->resumenSeleccion()['cantidad'] === 0) {
            $this->addError('cargosSeleccionados', 'Selecciona al menos un cargo válido.');
        }
    }

    public function render()
    {
        Gate::authorize('manage-pagos');

        $termino = trim((string) $this->busqueda);
        $resultados = collect();
        if ($termino !== '') {
            $resultados = $this->consultaBusqueda($termino)->limit(25)->get();
        }

        $inscripcion = null;
        $cargos = collect();
        $resumen = $this->resumenVacio();
        $inscripcionId = $this->normalizarInscripcionId($this->inscripcionSeleccionadaId);
        if ($inscripcionId !== null) {
            $inscripcion = Inscripcion::query()
                ->with(['prospecto', 'cursos', 'grupo', 'responsablePago'])
                ->find($inscripcionId);

            if (! $inscripcion || ! $inscripcion->prospecto) {
                $this->limpiarDatosSeleccionados();
            } else {
                // The cards and table are derived from this single, freshly persisted collection.
                $cargos = $this->consultaCargosAbiertos($inscripcionId)
                    ->with('conceptoCobro')
                    ->orderByRaw('CASE WHEN estado = ? OR fecha_vencimiento < ? THEN 0 ELSE 1 END', [Cargo::ESTADO_VENCIDO, now()->toDateString()])
                    ->orderBy('fecha_vencimiento')
                    ->orderBy('cargo_id')
                    ->get();
                $resumen = $this->calcularResumenFinanciero($cargos);
                $this->reconciliarSeleccion();
            }
        } elseif ($this->inscripcionSeleccionadaId !== null) {
            $this->limpiarDatosSeleccionados();
        }

        $resumenSeleccion = $this->resumenSeleccion();

        return view('livewire.registrar-pago', compact('resultados', 'inscripcion', 'cargos', 'resumen', 'resumenSeleccion'));
    }

    private function consultaBusqueda(string $termino): Builder
    {
        $query = Inscripcion::query()->with(['prospecto', 'cursos', 'grupo']);
        if (ctype_digit($termino)) {
            return $query->whereKey((int) $termino)->orderBy('inscripciones_id');
        }

        $palabras = preg_split('/\s+/u', $termino, -1, PREG_SPLIT_NO_EMPTY);
        return $query->whereHas('prospecto', function (Builder $prospectos) use ($palabras) {
            foreach ($palabras as $palabra) {
                $like = '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $palabra).'%';
                $prospectos->where(function (Builder $parte) use ($like) {
                    $parte->whereRaw("prospectos_nombres LIKE ? ESCAPE '!'", [$like])
                        ->orWhereRaw("prospectos_apellidos LIKE ? ESCAPE '!'", [$like]);
                });
            }
        })->orderBy('inscripciones_id');
    }

    private function consultaCargosAbiertos(int $inscripcionId): Builder
    {
        return Cargo::query()
            ->where('inscripciones_id', $inscripcionId)
            ->where('moneda', $this->inscripcionPersistida($inscripcionId)?->moneda)
            ->whereIn('estado', [Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_PARCIAL, Cargo::ESTADO_VENCIDO])
            ->where('saldo_pendiente', '>', '0.00');
    }

    private function calcularResumenFinanciero($cargos): array
    {
        $hoy = now()->toDateString();
        $vencidos = $cargos->filter(fn (Cargo $cargo) => $cargo->estado === Cargo::ESTADO_VENCIDO || $cargo->fecha_vencimiento->toDateString() < $hoy);
        $proximo = $cargos->filter(fn (Cargo $cargo) => $cargo->estado !== Cargo::ESTADO_VENCIDO && $cargo->fecha_vencimiento->toDateString() >= $hoy)
            ->sortBy('fecha_vencimiento')->first();

        return [
            'saldoPendiente' => $this->sumarImportes($cargos->pluck('saldo_pendiente')->all()),
            'saldoVencido' => $this->sumarImportes($vencidos->pluck('saldo_pendiente')->all()),
            'cantidadCargosAbiertos' => $cargos->count(),
            'cantidadCargosVencidos' => $vencidos->count(),
            'proximoVencimiento' => $proximo?->fecha_vencimiento->toDateString(),
        ];
    }

    private function sumarImportes(array $importes): string
    {
        $centavos = 0;
        foreach ($importes as $importe) {
            [$enteros, $decimales] = array_pad(explode('.', (string) $importe, 2), 2, '');
            $centavos += ((int) $enteros * 100) + (int) str_pad(substr($decimales, 0, 2), 2, '0');
        }

        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }

    private function limpiarDatosSeleccionados(): void
    {
        $this->inscripcionSeleccionadaId = null;
        $this->limpiarSeleccionCargosInterno();
    }

    private function normalizarId($value): ?int
    {
        return $this->normalizarInscripcionId($value);
    }

    private function inscripcionPersistida(int $id): ?Inscripcion
    {
        return Inscripcion::query()->find($id);
    }

    private function consultarCargoElegible(int $cargoId): ?Cargo
    {
        $inscripcionId = $this->normalizarId($this->inscripcionSeleccionadaId);
        if ($inscripcionId === null || ! $this->inscripcionPersistida($inscripcionId)) {
            return null;
        }

        return $this->consultaCargosAbiertos($inscripcionId)->find($cargoId);
    }

    private function normalizarIdsSeleccionados(): array
    {
        if (! is_array($this->cargosSeleccionados)) {
            return [];
        }
        $ids = [];
        foreach ($this->cargosSeleccionados as $value) {
            $id = $this->normalizarId($value);
            if ($id !== null) {
                $ids[$id] = $id;
            }
        }
        return array_values($ids);
    }

    private function reconciliarSeleccion(): void
    {
        $ids = $this->normalizarIdsSeleccionados();
        $validos = [];
        foreach ($ids as $id) {
            $cargo = $this->consultarCargoElegible($id);
            $importe = is_array($this->importesAplicar) ? ($this->importesAplicar[(string) $id] ?? null) : null;
            $centavos = $this->importeACentavos($importe);
            if (! $cargo) {
                $this->retirarCargoObsoleto($id, 'Un cargo seleccionado ya no está disponible y fue retirado.');
                continue;
            }
            if ($centavos !== null && $centavos > $this->importeACentavos($cargo->saldo_pendiente)) {
                $this->retirarCargoObsoleto($id, 'El saldo del cargo cambió y fue retirado de la selección.');
                continue;
            }
            if ($centavos === null || $centavos <= 0) {
                $this->addError('importesAplicar.'.$id, 'Revisa el importe: debe ser positivo y no superar el saldo pendiente actual.');
                $validos[] = $id;
                continue;
            }
            $validos[] = $id;
            $this->importesAplicar[(string) $id] = $this->centavosAImporte($centavos);
        }
        $this->cargosSeleccionados = $validos;
        if (! is_array($this->importesAplicar)) {
            $this->importesAplicar = [];
        }
        $this->importesAplicar = array_intersect_key($this->importesAplicar, array_flip(array_map('strval', $validos)));
    }

    private function retirarCargoObsoleto(int $id, string $mensaje): void
    {
        $this->cargosSeleccionados = array_values(array_diff($this->normalizarIdsSeleccionados(), [$id]));
        if (is_array($this->importesAplicar)) {
            unset($this->importesAplicar[(string) $id], $this->importesAplicar[$id]);
        }
        $this->addError('cargosSeleccionados', $mensaje);
    }

    private function resumenSeleccion(): array
    {
        $total = $restante = 0;
        $restantes = [];
        $parciales = false;
        $cantidadValida = 0;
        $ids = $this->normalizarIdsSeleccionados();
        $cargos = empty($ids) ? collect() : Cargo::query()->whereKey($ids)->get()->keyBy(fn (Cargo $cargo) => $cargo->getKey());
        foreach ($ids as $id) {
            $cargo = $cargos->get($id);
            $aplicar = $this->importeACentavos($this->importesAplicar[(string) $id] ?? null);
            if (! $cargo || $aplicar === null || $aplicar <= 0 || $aplicar > $this->importeACentavos($cargo->saldo_pendiente)) {
                continue;
            }
            $saldo = $this->importeACentavos($cargo->saldo_pendiente);
            $cantidadValida++;
            $total += $aplicar;
            $restante += $saldo - $aplicar;
            $restantes[$id] = $this->centavosAImporte($saldo - $aplicar);
            $parciales = $parciales || $aplicar < $saldo;
        }
        return ['cantidad' => $cantidadValida, 'total' => $this->centavosAImporte($total), 'moneda' => $this->inscripcionPersistida((int) $this->inscripcionSeleccionadaId)?->moneda ?? 'MXN', 'tieneParciales' => $parciales, 'saldoRestante' => $this->centavosAImporte($restante), 'restantes' => $restantes];
    }

    private function importeACentavos($importe): ?int
    {
        if (! is_string($importe) && ! is_int($importe)) {
            return null;
        }
        $importe = (string) $importe;
        if (! preg_match('/^(0|[1-9][0-9]*)(?:\.([0-9]{1,2}))?$/D', $importe, $partes)) {
            return null;
        }
        $enteros = filter_var($partes[1], FILTER_VALIDATE_INT);
        if ($enteros === false || $enteros > intdiv(PHP_INT_MAX - 99, 100)) {
            return null;
        }
        return ($enteros * 100) + (int) str_pad($partes[2] ?? '', 2, '0');
    }

    private function centavosAImporte(int $centavos): string
    {
        return sprintf('%d.%02d', intdiv($centavos, 100), $centavos % 100);
    }

    private function limpiarSeleccionCargosInterno(): void
    {
        $this->cargosSeleccionados = [];
        $this->importesAplicar = [];
    }

    private function normalizarInscripcionId($value): ?int
    {
        if (! is_int($value) && ! (is_string($value) && preg_match('/^[1-9][0-9]*$/D', $value))) {
            return null;
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function resumenVacio(): array
    {
        return [
            'saldoPendiente' => '0.00',
            'saldoVencido' => '0.00',
            'cantidadCargosAbiertos' => 0,
            'cantidadCargosVencidos' => 0,
            'proximoVencimiento' => null,
        ];
    }
}
