<?php

namespace App\Services\Facturacion;

use App\Models\Cargo;
use App\Models\MetodoPago;
use App\Models\Pago;
use Carbon\CarbonImmutable;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\LazyCollection;
use Illuminate\Validation\ValidationException;

class ReporteFinancieroService
{
    public function ventana(string $fecha): array
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, config('app.timezone'));
        if (! $inicio || $inicio->format('Y-m-d') !== $fecha) {
            throw ValidationException::withMessages(['fechaCaja' => 'Indica una fecha real (AAAA-MM-DD).']);
        }

        return [$inicio, $inicio->addDay()];
    }

    public function pagosConfirmados(array $filtros): Collection
    {
        return $this->iterarPagosConfirmados($filtros)->collect();
    }

    /** Iterate in stable export order while eager-loading only one bounded batch. */
    public function iterarPagosConfirmados(array $filtros): LazyCollection
    {
        $query = $this->consultaPagosConfirmados($filtros);

        return $query->orderBy('fecha_pago')->orderBy('pago_id')->lazy($this->chunkSize());
    }

    public function agrupacion(string $tipo, array $filtros): Collection
    {
        $filas = collect();
        foreach ($this->iterarPagosConfirmados($filtros) as $pago) {
            if ($tipo === 'periodo') {
                $aplicado = '0.00';
                $periodosDelPago = [];
                foreach ($pago->aplicaciones as $aplicacion) {
                    $cargo = $aplicacion->cargo;
                    $periodo = $cargo && $cargo->periodo_anio && $cargo->periodo_mes
                        ? sprintf('%04d-%02d', $cargo->periodo_anio, $cargo->periodo_mes) : 'Sin período de cargo';
                    $periodosDelPago[$periodo] = $this->add($periodosDelPago[$periodo] ?? '0.00', (string) $aplicacion->importe_aplicado);
                    $aplicado = $this->add($aplicado, (string) $aplicacion->importe_aplicado);
                }
                $anticipo = $this->sub((string) $pago->monto, $aplicado);
                if (BigDecimal::of($anticipo)->isPositive()) {
                    $periodosDelPago['Anticipo / sin asignación'] = $this->add($periodosDelPago['Anticipo / sin asignación'] ?? '0.00', $anticipo);
                }
                foreach ($periodosDelPago as $periodo => $monto) $this->sumar($filas, $periodo, $pago->moneda, $monto);
                continue;
            }

            if ($tipo === 'usuario') {
                $this->sumar($filas, 'Registró: '.($pago->createdBy?->name ?: 'Sin usuario registrado'), $pago->moneda, (string) $pago->monto, 'registrador');
                $this->sumar($filas, 'Cajero confirmó: '.($pago->confirmedBy?->name ?: 'Sin cajero registrado'), $pago->moneda, (string) $pago->monto, 'cajero');
                continue;
            }

            $dimension = match ($tipo) {
                'grupo' => $pago->inscripcion?->grupo?->grupo_nombre ?: 'Sin grupo',
                'curso' => $pago->inscripcion?->cursos?->cursos_descripcion ?: 'Sin curso',
                'metodo' => $pago->metodoPago?->nombre ?: 'Sin método',
                default => 'Sin dimensión',
            };
            $this->sumar($filas, $dimension, $pago->moneda, (string) $pago->monto);
        }

        return $filas->values()->sortBy([['tipo_usuario', 'asc'], ['dimension', 'asc'], ['moneda', 'asc']])->values();
    }

    public function vencidos(array $filtros): Collection
    {
        return $this->iterarVencidos($filtros)->collect();
    }

    public function iterarVencidos(array $filtros): LazyCollection
    {
        $corte = $filtros['corte'];
        return Cargo::query()->with(['inscripcion.prospecto', 'inscripcion.cursos', 'inscripcion.grupo', 'conceptoCobro'])
            ->where('fecha_vencimiento', '<', $corte)->where('saldo_pendiente', '>', '0.00')
            ->where('estado', '!=', Cargo::ESTADO_CANCELADO)
            ->when($filtros['moneda'] ?? null, fn ($q, $v) => $q->where('moneda', $v))
            ->when($filtros['curso_id'] ?? null, fn ($q, $v) => $q->whereHas('inscripcion', fn ($i) => $i->where('cursos_id', $v)))
            ->when($filtros['grupo_id'] ?? null, fn ($q, $v) => $q->whereHas('inscripcion', fn ($i) => $i->where('grupo_id', $v)))
            ->orderBy('fecha_vencimiento')->orderBy('cargo_id')->lazy($this->chunkSize())->map(function ($cargo) use ($corte) {
                $cargo->dias_vencidos = CarbonImmutable::parse($cargo->fecha_vencimiento)->diffInDays(CarbonImmutable::parse($corte));
                $cargo->pagado = $this->sub((string) $cargo->total, (string) $cargo->saldo_pendiente);
                return $cargo;
            });
    }

    public function eventos(array $filtros): Collection
    {
        return $this->iterarEventos($filtros)->collect();
    }

    public function iterarEventos(array $filtros): LazyCollection
    {
        $query = Pago::query()->whereIn('estado', [Pago::ESTADO_CANCELADO, Pago::ESTADO_REEMBOLSADO])
            ->with(['inscripcion.prospecto', 'inscripcion.cursos', 'inscripcion.grupo', 'metodoPago', 'cancelledBy']);
        $tipo = $filtros['tipo'] ?? 'todos';
        if ($tipo !== 'todos') $query->where('estado', $tipo);
        $query->where(function (Builder $q) use ($filtros) {
            if (($filtros['desde'] ?? '') !== '') {
                [$inicio] = $this->ventana($filtros['desde']);
                $q->where(fn ($x) => $x->where('estado', Pago::ESTADO_CANCELADO)->where('fecha_cancelacion', '>=', $inicio)
                    ->orWhere(fn ($x) => $x->where('estado', Pago::ESTADO_REEMBOLSADO)->where('fecha_reembolso', '>=', $inicio)));
            }
            if (($filtros['hasta'] ?? '') !== '') {
                [, $fin] = $this->ventana($filtros['hasta']);
                $q->where(fn ($x) => $x->where('estado', Pago::ESTADO_CANCELADO)->where('fecha_cancelacion', '<', $fin)
                    ->orWhere(fn ($x) => $x->where('estado', Pago::ESTADO_REEMBOLSADO)->where('fecha_reembolso', '<', $fin)));
            }
        });
        $this->filtrosDimensiones($query, $filtros);
        if ($filtros['cancelled_by'] ?? $filtros['usuario_id'] ?? null) {
            $query->where('cancelled_by', $filtros['cancelled_by'] ?? $filtros['usuario_id']);
        }
        return $query->orderByRaw('COALESCE(fecha_reembolso, fecha_cancelacion)')->orderBy('pago_id')->lazy($this->chunkSize());
    }

    /** The daily register uses confirmed_by for receipts and cancelled_by for adjustments. */
    public function diario(string $fecha, ?int $cajeroId = null): array
    {
        [$inicio, $fin] = $this->ventana($fecha);
        $filtrosPagos = ['desde' => $fecha, 'hasta' => $fecha, 'confirmed_by' => $cajeroId];
        $filtrosEventos = ['desde' => $fecha, 'hasta' => $fecha, 'cancelled_by' => $cajeroId, 'tipo' => 'todos'];
        $grupos = [];
        foreach ($this->iterarPagosConfirmados($filtrosPagos) as $p) $this->acumular($grupos, $p->metodo_pago_id, $p->metodoPago?->nombre ?: 'Sin método', $p->moneda, (string) $p->monto, 'bruto');
        foreach ($this->iterarEventos($filtrosEventos) as $p) $this->acumular($grupos, $p->metodo_pago_id, $p->metodoPago?->nombre ?: 'Sin método', $p->moneda, (string) $p->monto, 'ajustes');
        foreach ($grupos as &$grupo) $grupo['neto'] = $this->sub($grupo['bruto'], $grupo['ajustes']);

        return ['fecha' => $fecha, 'inicio' => $inicio, 'fin' => $fin, 'zona_horaria' => config('app.timezone'),
            'cajero_id' => $cajeroId,
            // Each lazy collection owns a query factory, so multiple consumers get a fresh batched traversal.
            'pagos' => LazyCollection::make(fn () => yield from $this->iterarPagosConfirmados($filtrosPagos)),
            'eventos' => LazyCollection::make(fn () => yield from $this->iterarEventos($filtrosEventos)),
            'totales' => collect($grupos)->values()];
    }

    public function combinacionesContables(array $resumen): Collection
    {
        $monedas = collect(config('facturacion.monedas_permitidas', ['MXN']))
            ->merge($resumen['totales']->pluck('moneda'))
            ->filter(fn ($m) => is_string($m) && preg_match('/^[A-Z]{3}$/D', $m))->unique()->sort()->values();
        $metodos = MetodoPago::query()->activos()->ordenados()->get(['metodo_pago_id', 'nombre']);
        $existentes = $resumen['totales']->keyBy(fn ($r) => self::claveCombinacion((int) $r['metodo_id'], $r['moneda']));
        $combinaciones = collect();
        foreach ($metodos as $metodo) foreach ($monedas as $moneda) {
            $clave = self::claveCombinacion($metodo->metodo_pago_id, $moneda);
            $fila = $existentes->get($clave, ['metodo_id' => $metodo->metodo_pago_id, 'metodo' => $metodo->nombre,
                'moneda' => $moneda, 'bruto' => '0.00', 'ajustes' => '0.00', 'neto' => '0.00', 'cantidad' => 0]);
            $combinaciones->put($clave, $fila);
        }
        foreach ($existentes as $clave => $fila) $combinaciones->put($clave, $fila);
        return $combinaciones;
    }

    public static function claveCombinacion(int $metodoId, string $moneda): string { return $metodoId.'|'.$moneda; }

    private function filtrarPagos(Builder $query, array $filtros): void
    {
        if (($filtros['desde'] ?? '') !== '') { [$inicio] = $this->ventana($filtros['desde']); $query->where('fecha_pago', '>=', $inicio); }
        if (($filtros['hasta'] ?? '') !== '') { [, $fin] = $this->ventana($filtros['hasta']); $query->where('fecha_pago', '<', $fin); }
        $this->filtrosDimensiones($query, $filtros);
        if ($filtros['created_by'] ?? null) $query->where('created_by', $filtros['created_by']);
        if (array_key_exists('confirmed_by', $filtros) && $filtros['confirmed_by'] !== null) $query->where('confirmed_by', $filtros['confirmed_by']);
        elseif ($filtros['usuario_id'] ?? null) $query->where('created_by', $filtros['usuario_id']);
    }

    private function consultaPagosConfirmados(array $filtros): Builder
    {
        $query = Pago::query()->confirmados()->with([
            'inscripcion.cursos', 'inscripcion.grupo', 'metodoPago', 'createdBy', 'confirmedBy',
            'aplicaciones.cargo.conceptoCobro',
        ]);
        $this->filtrarPagos($query, $filtros);
        return $query;
    }

    private function chunkSize(): int
    {
        return max(1, (int) config('facturacion.export_chunk_size', 500));
    }

    private function filtrosDimensiones(Builder $query, array $filtros): void
    {
        foreach (['metodo_pago_id' => 'metodo_pago_id', 'moneda' => 'moneda'] as $f => $c) if ($filtros[$f] ?? null) $query->where($c, $filtros[$f]);
        if ($filtros['curso_id'] ?? null) $query->whereHas('inscripcion', fn ($q) => $q->where('cursos_id', $filtros['curso_id']));
        if ($filtros['grupo_id'] ?? null) $query->whereHas('inscripcion', fn ($q) => $q->where('grupo_id', $filtros['grupo_id']));
    }

    private function sumar(Collection $filas, string $dimension, string $moneda, string $monto, ?string $tipoUsuario = null): void
    {
        $key = ($tipoUsuario ?? '').'|'.$dimension.'|'.$moneda;
        $fila = $filas->get($key, ['dimension'=>$dimension, 'tipo_usuario'=>$tipoUsuario, 'moneda'=>$moneda, 'cantidad'=>0, 'monto'=>'0.00']);
        $fila['cantidad']++; $fila['monto'] = $this->add($fila['monto'], $monto); $filas->put($key, $fila);
    }

    private function acumular(array &$grupos, int $metodoId, string $metodo, string $moneda, string $monto, string $campo): void
    {
        $k = self::claveCombinacion($metodoId, $moneda);
        $grupos[$k] ??= ['metodo_id'=>$metodoId, 'metodo'=>$metodo, 'moneda'=>$moneda, 'bruto'=>'0.00', 'ajustes'=>'0.00', 'cantidad'=>0];
        $grupos[$k][$campo]=$this->add($grupos[$k][$campo],$monto); $grupos[$k]['cantidad']++;
    }

    private function add(string $a, string $b): string { return (string) BigDecimal::of($a)->plus($b)->toScale(2, RoundingMode::UNNECESSARY); }
    private function sub(string $a, string $b): string { return (string) BigDecimal::of($a)->minus($b)->toScale(2, RoundingMode::UNNECESSARY); }
}
