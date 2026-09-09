<?php

namespace App\Http\Livewire;

use App\Models\Cargo;
use App\Models\Inscripcion;
use App\Models\Pago;
use App\Services\Facturacion\MetodoPagoBehaviorService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class RegistrarPago extends Component
{
    use WithFileUploads;

    /** A payment timestamp may be at most five minutes ahead of the server clock. */
    private const TOLERANCIA_FECHA_FUTURA_MINUTOS = 5;

    public $busqueda = '';
    public $inscripcionSeleccionadaId;
    public $cargosSeleccionados = [];
    public $importesAplicar = [];
    public $fechaPago;
    public $metodoPagoId;
    public $montoRecibido;
    public $datosMetodo = [];
    public $observaciones;
    public $comprobante;
    public $mostrarConfirmacion = false;
    public $confirmacionFingerprint;

    public function mount(): void
    {
        Gate::authorize('manage-pagos');
        $this->fechaPago = now()->format('Y-m-d\TH:i');
    }

    public function updatedBusqueda(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function updated($name, $value): void
    {
        Gate::authorize('manage-pagos');
        if (in_array($name, ['inscripcionSeleccionadaId', 'cargosSeleccionados', 'importesAplicar', 'metodoPagoId', 'datosMetodo', 'fechaPago', 'montoRecibido', 'comprobante', 'observaciones'], true)
            || str_starts_with($name, 'importesAplicar.') || str_starts_with($name, 'datosMetodo.')) {
            $this->mostrarConfirmacion = false;
            $this->confirmacionFingerprint = null;
        }
    }

    public function updatedMetodoPagoId(): void
    {
        Gate::authorize('manage-pagos');
        $this->mostrarConfirmacion = false;
        $this->confirmacionFingerprint = null;
        $this->datosMetodo = [];
        $this->comprobante = null;
        $campos = array_map(
            fn (array $metadata) => 'datosMetodo.'.$metadata['campo'],
            $this->servicioMetodos()->catalogoCampos()
        );
        $this->resetErrorBag(array_merge(['metodoPagoId', 'datosMetodo', 'comprobante'], $campos));

        try {
            $this->servicioMetodos()->seleccionarActivo($this->metodoPagoId);
        } catch (ValidationException $exception) {
            $this->copiarErrores($exception, 'metodoPagoId');
        }
    }

    public function updatedDatosMetodo(): void
    {
        Gate::authorize('manage-pagos');
        $this->mostrarConfirmacion = false;
        $this->confirmacionFingerprint = null;
    }

    public function updatedFechaPago(): void { Gate::authorize('manage-pagos'); $this->invalidarConfirmacion(); }
    public function updatedMontoRecibido(): void { Gate::authorize('manage-pagos'); $this->invalidarConfirmacion(); }
    public function updatedObservaciones(): void { Gate::authorize('manage-pagos'); $this->invalidarConfirmacion(); }
    public function updatedComprobante(): void { Gate::authorize('manage-pagos'); $this->invalidarConfirmacion(); }

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
        $this->normalizarEstadoSeleccion();
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
        $this->actualizarMontoPropuesto();
        $this->resetErrorBag('importesAplicar.'.$key);
    }

    public function deseleccionarCargo($cargoId): void
    {
        Gate::authorize('manage-pagos');
        $this->normalizarEstadoSeleccion();
        $cargoId = $this->normalizarId($cargoId);
        if ($cargoId === null) {
            $this->addError('cargosSeleccionados', 'El identificador del cargo no es válido.');
            return;
        }

        $this->cargosSeleccionados = array_values(array_filter(
            $this->normalizarIdsSeleccionados(), fn (int $id) => $id !== $cargoId
        ));
        unset($this->importesAplicar[(string) $cargoId], $this->importesAplicar[$cargoId]);
        $this->actualizarMontoPropuesto();
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
        $this->actualizarMontoPropuesto();
        $this->resetErrorBag();
    }

    public function limpiarSeleccionCargos(): void
    {
        Gate::authorize('manage-pagos');
        $this->limpiarSeleccionCargosInterno();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function updatedImportesAplicar($value, $key = null): void
    {
        Gate::authorize('manage-pagos');
        if (! is_array($this->importesAplicar)) {
            $this->importesAplicar = [];
            $this->addError('importesAplicar', 'La estructura de importes no es válida y fue limpiada.');
            return;
        }
        if (! is_array($this->cargosSeleccionados)) {
            $this->cargosSeleccionados = [];
            $this->importesAplicar = [];
            $this->addError('cargosSeleccionados', 'La estructura de selección no es válida y fue limpiada.');
            return;
        }
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
            $this->actualizarMontoPropuesto();
            $this->resetErrorBag('importesAplicar.'.$id);
        }
    }

    public function prepararPago(): void
    {
        Gate::authorize('manage-pagos');
        $this->mostrarConfirmacion = false;
        $this->confirmacionFingerprint = null;
        $this->resetErrorBag();
        $inscripcionId = $this->normalizarId($this->inscripcionSeleccionadaId);
        $inscripcion = $inscripcionId ? Inscripcion::query()->with(['prospecto', 'responsablePago'])->find($inscripcionId) : null;
        if (! $inscripcion || ! $inscripcion->prospecto || $inscripcion->estatus === 'cancelada') {
            $this->addError('inscripcionSeleccionadaId', 'La inscripción seleccionada ya no es válida.');
            return;
        }
        if (! $inscripcion->responsablePago || ! $inscripcion->responsablePago->activo) {
            $this->addError('inscripcionSeleccionadaId', 'La inscripción no tiene un responsable de pago activo.');
            return;
        }
        $this->reconciliarSeleccion();
        $resumen = $this->resumenSeleccion();
        if ($resumen['cantidad'] === 0) {
            $this->addError('cargosSeleccionados', 'Selecciona al menos un cargo válido.');
            return;
        }

        $fecha = $this->validarFechaPago();
        $monto = $this->importeACentavos($this->montoRecibido);
        if ($monto === null || $monto <= 0) $this->addError('montoRecibido', 'El importe recibido debe ser mayor a 0.00 y tener máximo dos decimales.');
        elseif ($monto !== $this->importeACentavos($resumen['total'])) $this->addError('montoRecibido', 'El importe recibido debe coincidir exactamente con el total aplicado.');
        else $this->montoRecibido = $this->centavosAImporte($monto);

        $this->observaciones = is_string($this->observaciones) ? (trim($this->observaciones) ?: null) : $this->observaciones;
        Validator::make(['observaciones' => $this->observaciones], ['observaciones' => ['nullable', 'string', 'max:2000']], [], ['observaciones' => 'observaciones'])
            ->after(function ($validator) { foreach ($validator->errors()->get('observaciones') as $message) $this->addError('observaciones', $message); })->passes();

        try {
            $metodoVigente = $this->servicioMetodos()->seleccionarActivo($this->metodoPagoId);
            if ($metodoVigente->requiere_anticipo_relacionado) {
                $this->datosMetodo = [];
                $this->comprobante = null;
                $this->addError('metodoPagoId', 'La aplicación de anticipos se habilitará en el bloque correspondiente.');
                return;
            }
            $datosCapturados = is_array($this->datosMetodo) ? $this->datosMetodo : [];
            $datosCapturados['comprobante'] = $this->comprobante;
            $resultado = $this->servicioMetodos()->validarYNormalizarParaNuevoPago($this->metodoPagoId, $datosCapturados);
            $metodo = $resultado['metodo'];
            unset($resultado['datos']['comprobante']);
            $this->datosMetodo = $resultado['datos'];
            if (! $metodo->requiere_comprobante) {
                $this->comprobante = null;
                $this->resetErrorBag('comprobante');
            }
        } catch (ValidationException $exception) {
            $this->copiarErrores($exception, 'datosMetodo');
        }

        if ($fecha && ! $this->getErrorBag()->isNotEmpty()) {
            $this->confirmacionFingerprint = $this->fingerprintEstado();
            $this->mostrarConfirmacion = true;
        }
    }

    public function volverAEditar(): void { Gate::authorize('manage-pagos'); $this->invalidarConfirmacion(); }

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
        $metodosPago = $this->servicioMetodos()->metodosDisponibles();
        $configuracionMetodo = null;
        try { $configuracionMetodo = $this->servicioMetodos()->configuracion($this->servicioMetodos()->seleccionarActivo($this->metodoPagoId)); } catch (ValidationException $e) {}
        $resumenConfirmacion = $this->mostrarConfirmacion
            && is_string($this->confirmacionFingerprint)
            && hash_equals($this->confirmacionFingerprint, $this->fingerprintEstado())
                ? $this->crearResumenConfirmacion($inscripcion, $resumenSeleccion)
                : null;
        $advertenciaDuplicidad = $this->advertenciaDuplicidad();

        return view('livewire.registrar-pago', compact('resultados', 'inscripcion', 'cargos', 'resumen', 'resumenSeleccion', 'metodosPago', 'configuracionMetodo', 'resumenConfirmacion', 'advertenciaDuplicidad'));
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
        $this->mostrarConfirmacion = false;
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
        $estadoManipulado = $this->normalizarEstadoSeleccion();
        if ($estadoManipulado) {
            return;
        }
        $ids = $this->normalizarIdsSeleccionados();
        $inscripcionId = $this->normalizarId($this->inscripcionSeleccionadaId);
        $inscripcion = $inscripcionId === null ? null : Inscripcion::query()->find($inscripcionId);
        $cargos = ! $inscripcion || empty($ids)
            ? collect()
            : Cargo::query()->whereKey($ids)->get()->keyBy(fn (Cargo $cargo) => $cargo->getKey());
        $validos = [];
        foreach ($ids as $id) {
            $cargo = $cargos->get($id);
            $elegible = $cargo && $cargo->inscripciones_id === $inscripcionId
                && $cargo->moneda === $inscripcion->moneda
                && in_array($cargo->estado, [Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_PARCIAL, Cargo::ESTADO_VENCIDO], true)
                && $this->importeACentavos($cargo->saldo_pendiente) > 0;
            $importe = $this->importesAplicar[(string) $id] ?? null;
            $centavos = $this->importeACentavos($importe);
            if (! $elegible) {
                $this->retirarCargoObsoleto($id, 'Un cargo seleccionado ya no está disponible y fue retirado.');
                continue;
            }
            if ($centavos !== null && $centavos > $this->importeACentavos($cargo->saldo_pendiente)) {
                // An amount rejected by the input hook is a capture error. A formerly
                // valid amount that now exceeds persisted balance is a concurrent change.
                if ($this->getErrorBag()->has('importesAplicar.'.$id)) {
                    $validos[] = $id;
                    continue;
                }
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
        if (! is_array($this->cargosSeleccionados) || ! is_array($this->importesAplicar)) {
            return ['cantidad' => 0, 'total' => '0.00', 'moneda' => 'MXN', 'tieneParciales' => false, 'saldoRestante' => '0.00', 'restantes' => []];
        }
        $ids = $this->normalizarIdsSeleccionados();
        $inscripcionId = $this->normalizarId($this->inscripcionSeleccionadaId);
        $inscripcion = $inscripcionId === null ? null : Inscripcion::query()->find($inscripcionId);
        $cargos = ! $inscripcion || empty($ids) ? collect() : Cargo::query()->whereKey($ids)->get()->keyBy(fn (Cargo $cargo) => $cargo->getKey());
        foreach ($ids as $id) {
            $cargo = $cargos->get($id);
            $aplicar = $this->importeACentavos($this->importesAplicar[(string) $id] ?? null);
            if (! $cargo || $cargo->inscripciones_id !== $inscripcionId || $cargo->moneda !== $inscripcion->moneda
                || ! in_array($cargo->estado, [Cargo::ESTADO_PENDIENTE, Cargo::ESTADO_PARCIAL, Cargo::ESTADO_VENCIDO], true)
                || $aplicar === null || $aplicar <= 0 || $aplicar > $this->importeACentavos($cargo->saldo_pendiente)) {
                continue;
            }
            $saldo = $this->importeACentavos($cargo->saldo_pendiente);
            $cantidadValida++;
            $total += $aplicar;
            $restante += $saldo - $aplicar;
            $restantes[$id] = $this->centavosAImporte($saldo - $aplicar);
            $parciales = $parciales || $aplicar < $saldo;
        }
        return ['cantidad' => $cantidadValida, 'total' => $this->centavosAImporte($total), 'moneda' => $inscripcion?->moneda ?? 'MXN', 'tieneParciales' => $parciales, 'saldoRestante' => $this->centavosAImporte($restante), 'restantes' => $restantes];
    }

    /** Normalize all client-controlled collection state before array operations. */
    private function normalizarEstadoSeleccion(): bool
    {
        $manipulado = false;
        if (! is_array($this->cargosSeleccionados)) {
            $this->cargosSeleccionados = [];
            $manipulado = true;
            $this->addError('cargosSeleccionados', 'La estructura de selección no es válida y fue limpiada.');
        } else {
            $cantidadOriginal = count($this->cargosSeleccionados);
            $this->cargosSeleccionados = $this->normalizarIdsSeleccionados();
            if (count($this->cargosSeleccionados) !== $cantidadOriginal) {
                $manipulado = true;
                $this->addError('cargosSeleccionados', 'La selección contenía identificadores inválidos y fue corregida.');
            }
        }
        if (! is_array($this->importesAplicar)) {
            $this->importesAplicar = [];
            $manipulado = true;
            $this->addError('importesAplicar', 'La estructura de importes no es válida y fue limpiada.');
        }
        return $manipulado;
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
        $this->montoRecibido = null;
        $this->mostrarConfirmacion = false;
    }

    private function actualizarMontoPropuesto(): void
    {
        $this->montoRecibido = $this->resumenSeleccion()['total'];
        $this->mostrarConfirmacion = false;
    }

    private function validarFechaPago(): ?Carbon
    {
        if (! is_string($this->fechaPago)) {
            $this->addError('fechaPago', 'La fecha y hora del pago no es válida.');
            return null;
        }
        try {
            $fecha = Carbon::createFromFormat('Y-m-d\TH:i', $this->fechaPago, config('app.timezone'));
            if (! $fecha || $fecha->format('Y-m-d\TH:i') !== $this->fechaPago) throw new \InvalidArgumentException();
        } catch (\Throwable $exception) {
            $this->addError('fechaPago', 'La fecha y hora del pago no es válida.');
            return null;
        }
        if ($fecha->greaterThan(now()->addMinutes(self::TOLERANCIA_FECHA_FUTURA_MINUTOS))) {
            $this->addError('fechaPago', 'La fecha del pago no puede superar por más de 5 minutos la hora del servidor.');
            return null;
        }
        return $fecha;
    }

    private function servicioMetodos(): MetodoPagoBehaviorService
    {
        return app(MetodoPagoBehaviorService::class);
    }

    private function copiarErrores(ValidationException $exception, string $prefijo): void
    {
        foreach ($exception->errors() as $campo => $mensajes) {
            if ($campo === 'metodo_pago_id') {
                $destino = 'metodoPagoId';
            } elseif ($campo === 'comprobante') {
                $destino = 'comprobante';
            } else {
                $destino = $prefijo.'.'.$campo;
            }
            foreach ($mensajes as $mensaje) $this->addError($destino, $mensaje);
        }
    }

    private function advertenciaDuplicidad(): ?string
    {
        if (! is_array($this->datosMetodo)) return null;
        // Banco describe el origen, pero nunca identifica por sí solo un pago.
        $campos = ['referencia', 'rastreo_spei', 'numero_cheque', 'numero_autorizacion'];
        $valores = [];
        try {
            $metodo = $this->servicioMetodos()->seleccionarActivo($this->metodoPagoId);
            $aplicables = array_keys($this->servicioMetodos()->camposAplicables($metodo));
        } catch (ValidationException $e) { return null; }
        foreach (array_intersect($campos, $aplicables) as $campo) {
            $valor = $this->datosMetodo[$campo] ?? null;
            if (is_string($valor) && trim($valor) !== '') {
                $valores[$campo] = mb_strtolower(trim($valor), 'UTF-8');
            }
        }
        if ($valores === []) return null;
        $existe = Pago::query()->where('estado', '!=', Pago::ESTADO_CANCELADO)
            ->where(function (Builder $query) use ($valores) {
                foreach ($valores as $campo => $valor) {
                    $query->orWhereRaw('LOWER(TRIM('.$campo.')) = ?', [$valor]);
                }
            })->exists();
        return $existe ? 'Existe otro pago activo con una referencia o identificador coincidente. Verifica los datos antes de continuar.' : null;
    }

    private function crearResumenConfirmacion(?Inscripcion $inscripcion, array $seleccion): ?array
    {
        $inscripcionId = $this->normalizarId($this->inscripcionSeleccionadaId);
        if (! $inscripcion || $inscripcionId === null || $inscripcion->getKey() !== $inscripcionId
            || ! $inscripcion->prospecto || $inscripcion->estatus === 'cancelada'
            || ! $inscripcion->responsablePago || ! $inscripcion->responsablePago->activo
            || $seleccion['cantidad'] < 1
            || $this->importeACentavos($this->montoRecibido) === null
            || $this->importeACentavos($this->montoRecibido) !== $this->importeACentavos($seleccion['total'])
            || ! $this->fechaPagoEsValida()) {
            return null;
        }
        try {
            $datosCapturados = is_array($this->datosMetodo) ? $this->datosMetodo : [];
            $datosCapturados['comprobante'] = $this->comprobante;
            $resultado = $this->servicioMetodos()->validarYNormalizarParaNuevoPago($this->metodoPagoId, $datosCapturados);
            unset($resultado['datos']['comprobante']);
        } catch (ValidationException $e) { return null; }
        return [
            'alumno' => trim(($inscripcion->prospecto?->prospectos_nombres ?? '').' '.($inscripcion->prospecto?->prospectos_apellidos ?? '')),
            'inscripcion' => $inscripcion->getKey(),
            'responsable' => $inscripcion->responsablePago?->nombre_razon_social,
            'cantidad' => $seleccion['cantidad'], 'totalRecibido' => $this->montoRecibido,
            'totalAplicado' => $seleccion['total'], 'saldoRestante' => $seleccion['saldoRestante'],
            'moneda' => $inscripcion->moneda, 'metodo' => $resultado['metodo']->nombre,
            'datos' => $resultado['datos'], 'fecha' => $this->fechaPago,
            'zonaHoraria' => config('app.timezone'), 'comprobante' => $resultado['metodo']->requiere_comprobante && $this->comprobante !== null,
        ];
    }

    /** Firma opaca: el navegador puede verla, pero no fabricar una para otro estado. */
    private function fingerprintEstado(): string
    {
        $archivo = null;
        if (is_object($this->comprobante)) {
            $archivo = [
                method_exists($this->comprobante, 'getClientOriginalName') ? $this->comprobante->getClientOriginalName() : null,
                method_exists($this->comprobante, 'getSize') ? $this->comprobante->getSize() : null,
            ];
        }
        $estado = [
            $this->inscripcionSeleccionadaId, $this->cargosSeleccionados, $this->importesAplicar,
            $this->fechaPago, $this->metodoPagoId, $this->montoRecibido, $this->datosMetodo,
            $this->observaciones, $archivo,
        ];

        return hash_hmac('sha256', serialize($estado), (string) config('app.key'));
    }

    private function fechaPagoEsValida(): bool
    {
        if (! is_string($this->fechaPago)) return false;
        try {
            $fecha = Carbon::createFromFormat('Y-m-d\TH:i', $this->fechaPago, config('app.timezone'));
        } catch (\Throwable $exception) {
            return false;
        }

        return $fecha && $fecha->format('Y-m-d\TH:i') === $this->fechaPago
            && ! $fecha->greaterThan(now()->addMinutes(self::TOLERANCIA_FECHA_FUTURA_MINUTOS));
    }

    private function invalidarConfirmacion(): void
    {
        $this->mostrarConfirmacion = false;
        $this->confirmacionFingerprint = null;
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
