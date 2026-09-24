<?php

namespace App\Services\Facturacion;

use App\Models\Cargo;
use App\Models\Pago;
use Carbon\CarbonImmutable;
use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReporteFinancieroService
{
    public function ventana(string $fecha): array
    {
        $inicio = CarbonImmutable::createFromFormat('!Y-m-d', $fecha, config('app.timezone'));

        return [$inicio, $inicio->addDay()]; // inicio inclusivo, fin exclusivo
    }

    public function pagosConfirmados(array $filtros): Collection
    {
        $query = Pago::query()->confirmados()->with([
            'inscripcion.cursos', 'inscripcion.grupo', 'metodoPago', 'createdBy', 'confirmedBy',
            'aplicaciones.cargo.conceptoCobro',
        ]);
        $this->filtrarPagos($query, $filtros);

        return $query->orderBy('fecha_pago')->orderBy('pago_id')->get();
    }

    public function agrupacion(string $tipo, array $filtros): Collection
    {
        $filas = collect();
        foreach ($this->pagosConfirmados($filtros) as $pago) {
            if ($tipo === 'periodo') {
                $aplicado = '0.00';
                foreach ($pago->aplicaciones as $aplicacion) {
                    $cargo = $aplicacion->cargo;
                    $periodo = $cargo && $cargo->periodo_anio && $cargo->periodo_mes
                        ? sprintf('%04d-%02d', $cargo->periodo_anio, $cargo->periodo_mes) : 'Sin período de cargo';
                    $this->sumar($filas, $periodo, $pago->moneda, $pago->pago_id, (string) $aplicacion->importe_aplicado);
                    $aplicado = $this->add($aplicado, (string) $aplicacion->importe_aplicado);
                }
                $anticipo = $this->sub((string) $pago->monto, $aplicado);
                if (BigDecimal::of($anticipo)->isPositive()) {
                    $this->sumar($filas, 'Anticipo / sin asignación', $pago->moneda, $pago->pago_id, $anticipo);
                }
                continue;
            }

            $dimension = match ($tipo) {
                'grupo' => $pago->inscripcion?->grupo?->grupo_nombre ?: 'Sin grupo',
                'curso' => $pago->inscripcion?->cursos?->cursos_descripcion ?: 'Sin curso',
                'metodo' => $pago->metodoPago?->nombre ?: 'Sin método',
                'usuario' => 'Registró: '.($pago->createdBy?->name ?: 'Sin usuario').' / Confirmó: '.($pago->confirmedBy?->name ?: 'Sin usuario'),
                default => 'Sin dimensión',
            };
            $this->sumar($filas, $dimension, $pago->moneda, $pago->pago_id, (string) $pago->monto);
        }

        return $filas->values()->sortBy([['dimension', 'asc'], ['moneda', 'asc']])->values();
    }

    public function vencidos(array $filtros): Collection
    {
        $corte = $filtros['corte'];
        return Cargo::query()->with(['inscripcion.prospecto', 'inscripcion.cursos', 'inscripcion.grupo', 'conceptoCobro'])
            ->where('fecha_vencimiento', '<', $corte)->where('saldo_pendiente', '>', '0.00')
            ->where('estado', '!=', Cargo::ESTADO_CANCELADO)
            ->when($filtros['moneda'] ?? null, fn ($q, $v) => $q->where('moneda', $v))
            ->when($filtros['curso_id'] ?? null, fn ($q, $v) => $q->whereHas('inscripcion', fn ($i) => $i->where('cursos_id', $v)))
            ->when($filtros['grupo_id'] ?? null, fn ($q, $v) => $q->whereHas('inscripcion', fn ($i) => $i->where('grupo_id', $v)))
            ->orderBy('fecha_vencimiento')->orderBy('cargo_id')->get()->map(function ($cargo) use ($corte) {
                $cargo->dias_vencidos = CarbonImmutable::parse($cargo->fecha_vencimiento)->diffInDays(CarbonImmutable::parse($corte));
                $cargo->pagado = $this->sub((string) $cargo->total, (string) $cargo->saldo_pendiente);
                return $cargo;
            });
    }

    public function eventos(array $filtros): Collection
    {
        $query = Pago::query()->whereIn('estado', [Pago::ESTADO_CANCELADO, Pago::ESTADO_REEMBOLSADO])
            ->with(['inscripcion.prospecto', 'inscripcion.cursos', 'inscripcion.grupo', 'metodoPago', 'cancelledBy']);
        $tipo = $filtros['tipo'] ?? 'todos';
        if ($tipo !== 'todos') $query->where('estado', $tipo);
        $query->where(function (Builder $q) use ($filtros) {
            if (($filtros['desde'] ?? '') !== '') {
                [$inicio] = $this->ventana($filtros['desde']);
                $q->where(fn ($x) => $x->where('estado', Pago::ESTADO_CANCELADO)->where('fecha_cancelacion', '>=', $inicio)
                    ->orWhere('estado', Pago::ESTADO_REEMBOLSADO)->where('fecha_reembolso', '>=', $inicio));
            }
            if (($filtros['hasta'] ?? '') !== '') {
                [, $fin] = $this->ventana($filtros['hasta']);
                $q->where(fn ($x) => $x->where('estado', Pago::ESTADO_CANCELADO)->where('fecha_cancelacion', '<', $fin)
                    ->orWhere('estado', Pago::ESTADO_REEMBOLSADO)->where('fecha_reembolso', '<', $fin));
            }
        });
        $this->filtrosDimensiones($query, $filtros);
        if ($filtros['usuario_id'] ?? null) $query->where('cancelled_by', $filtros['usuario_id']);
        return $query->orderByRaw('COALESCE(fecha_reembolso, fecha_cancelacion)')->orderBy('pago_id')->get();
    }

    public function diario(string $fecha, ?int $cajeroId = null): array
    {
        [$inicio, $fin] = $this->ventana($fecha);
        $filtros = ['desde' => $fecha, 'hasta' => $fecha, 'usuario_id' => $cajeroId];
        $pagos = $this->pagosConfirmados($filtros);
        $eventos = $this->eventos(['desde' => $fecha, 'hasta' => $fecha, 'usuario_id' => $cajeroId, 'tipo' => 'todos']);
        $grupos = [];
        foreach ($pagos as $p) $this->acumular($grupos, $p->metodoPago?->nombre ?: 'Sin método', $p->moneda, (string) $p->monto, 'bruto');
        foreach ($eventos as $p) $this->acumular($grupos, $p->metodoPago?->nombre ?: 'Sin método', $p->moneda, (string) $p->monto, 'ajustes');
        foreach ($grupos as &$g) $g['neto'] = $this->sub($g['bruto'], $g['ajustes']);
        return ['inicio' => $inicio, 'fin' => $fin, 'pagos' => $pagos, 'eventos' => $eventos, 'totales' => collect($grupos)->values()];
    }

    private function filtrarPagos(Builder $query, array $filtros): void
    {
        if (($filtros['desde'] ?? '') !== '') { [$inicio] = $this->ventana($filtros['desde']); $query->where('fecha_pago', '>=', $inicio); }
        if (($filtros['hasta'] ?? '') !== '') { [, $fin] = $this->ventana($filtros['hasta']); $query->where('fecha_pago', '<', $fin); }
        $this->filtrosDimensiones($query, $filtros);
        if ($filtros['usuario_id'] ?? null) $query->where('created_by', $filtros['usuario_id']);
    }

    private function filtrosDimensiones(Builder $query, array $filtros): void
    {
        foreach (['metodo_pago_id' => 'metodo_pago_id', 'moneda' => 'moneda'] as $f => $c) if ($filtros[$f] ?? null) $query->where($c, $filtros[$f]);
        if ($filtros['curso_id'] ?? null) $query->whereHas('inscripcion', fn ($q) => $q->where('cursos_id', $filtros['curso_id']));
        if ($filtros['grupo_id'] ?? null) $query->whereHas('inscripcion', fn ($q) => $q->where('grupo_id', $filtros['grupo_id']));
    }

    private function sumar(Collection $filas, string $dimension, string $moneda, int $pagoId, string $monto): void
    {
        $key = $dimension.'|'.$moneda; $fila = $filas->get($key, ['dimension'=>$dimension, 'moneda'=>$moneda, 'pagos'=>[], 'monto'=>'0.00']);
        $fila['pagos'][$pagoId] = true; $fila['monto'] = $this->add($fila['monto'], $monto); $fila['cantidad'] = count($fila['pagos']); $filas->put($key, $fila);
    }

    private function acumular(array &$grupos, string $metodo, string $moneda, string $monto, string $campo): void
    {
        $k=$metodo.'|'.$moneda; $grupos[$k] ??= ['metodo'=>$metodo,'moneda'=>$moneda,'bruto'=>'0.00','ajustes'=>'0.00','cantidad'=>0];
        $grupos[$k][$campo]=$this->add($grupos[$k][$campo],$monto); $grupos[$k]['cantidad']++;
    }

    private function add(string $a, string $b): string { return (string) BigDecimal::of($a)->plus($b)->toScale(2, RoundingMode::UNNECESSARY); }
    private function sub(string $a, string $b): string { return (string) BigDecimal::of($a)->minus($b)->toScale(2, RoundingMode::UNNECESSARY); }
}
