<?php

namespace App\Http\Livewire;

use App\Models\MetodoPago;
use App\Models\Pago;
use App\Services\Facturacion\CancelarPagoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithPagination;

class ShowPagos extends Component
{
    use WithPagination;

    private const POR_PAGINA = [10, 25, 50];
    private const BUSQUEDA_MAXIMA = 120;
    private const CANCELACION_TOKEN_TTL = 1800;

    public $busqueda = '';
    public $estado = 'todos';
    public $metodoPagoId = 'todos';
    public $fechaDesde = '';
    public $fechaHasta = '';
    public $porPagina = 10;
    public $pagoDetalleId;
    public $pagoCancelarId;
    public $motivoCancelacion = '';
    public $cancelacionToken;
    public $mostrarModalDetalle = false;
    public $mostrarModalCancelacion = false;

    public function mount(): void
    {
        Gate::authorize('manage-pagos');
    }

    public function updated($name): void
    {
        Gate::authorize('manage-pagos');
        if (in_array($name, ['busqueda', 'estado', 'metodoPagoId', 'fechaDesde', 'fechaHasta', 'porPagina'], true)) {
            $this->resetPage();
        }
        if (in_array($name, ['fechaDesde', 'fechaHasta'], true)) {
            $this->validarFechas();
        }
    }

    public function verDetalle($pagoId): void
    {
        Gate::authorize('manage-pagos');
        $id = $this->normalizarId($pagoId);
        abort_unless($id !== null && Pago::query()->whereKey($id)->exists(), 404);
        $this->pagoDetalleId = $id;
        $this->mostrarModalDetalle = true;
    }

    public function cerrarDetalle(): void
    {
        Gate::authorize('manage-pagos');
        $this->pagoDetalleId = null;
        $this->mostrarModalDetalle = false;
    }

    public function prepararCancelacion($pagoId): void
    {
        Gate::authorize('cancel-pagos');
        $id = $this->normalizarId($pagoId);
        $pago = $id === null ? null : Pago::query()->whereKey($id)->first();
        if (! $pago || $pago->estado !== Pago::ESTADO_CONFIRMADO) {
            throw ValidationException::withMessages(['pagoCancelarId' => 'El pago seleccionado no está disponible para cancelación.']);
        }

        $this->resetErrorBag();
        $this->resetValidation();
        $this->cancelacionToken = null;
        $this->pagoCancelarId = $pago->getKey();
        $this->cancelacionToken = $this->crearTokenCancelacion($pago->getKey(), (int) Auth::id());
        $this->motivoCancelacion = '';
        $this->mostrarModalCancelacion = true;
    }

    public function cerrarCancelacion(): void
    {
        Gate::authorize('cancel-pagos');
        $this->limpiarCancelacion();
    }

    public function confirmarCancelacion(CancelarPagoService $service): void
    {
        Gate::authorize('cancel-pagos');
        $id = $this->normalizarId($this->pagoCancelarId);
        $motivo = is_string($this->motivoCancelacion) ? trim($this->motivoCancelacion) : '';
        $this->motivoCancelacion = $motivo;
        $this->validate([
            'pagoCancelarId' => ['required'],
            'motivoCancelacion' => ['required', 'string', 'max:2000'],
        ], [], [
            'pagoCancelarId' => 'pago',
            'motivoCancelacion' => 'motivo de cancelación',
        ]);
        if ($id === null) {
            $this->addError('pagoCancelarId', 'El pago seleccionado no es válido.');
            return;
        }
        if ($this->mostrarModalCancelacion !== true || ! $this->tokenCancelacionValido($this->cancelacionToken, $id, (int) Auth::id())) {
            $this->addError('pagoCancelarId', 'La selección del pago ya no es válida. Vuelva a iniciar la cancelación.');
            return;
        }

        try {
            $pago = $service->cancelar($id, $motivo, (int) Auth::id());
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                $this->addError($key === 'motivo' ? 'motivoCancelacion' : 'pagoCancelarId', $messages[0]);
            }
            return;
        }

        $folio = $pago->folio;
        $this->limpiarCancelacion();
        session()->flash('status', 'El pago '.$folio.' fue cancelado correctamente.');
    }

    public function render()
    {
        Gate::authorize('manage-pagos');
        $busqueda = mb_substr(trim(is_string($this->busqueda) ? $this->busqueda : ''), 0, self::BUSQUEDA_MAXIMA);
        $estado = in_array($this->estado, Pago::ESTADOS, true) ? $this->estado : 'todos';
        $porPagina = in_array((int) $this->porPagina, self::POR_PAGINA, true) ? (int) $this->porPagina : 10;
        $this->porPagina = $porPagina;
        $metodoId = $this->normalizarId($this->metodoPagoId);

        $query = Pago::query()->with(['prospecto', 'responsablePago', 'metodoPago', 'confirmedBy']);
        if ($busqueda !== '') {
            $query->where(function (Builder $query) use ($busqueda) {
                $query->where('folio', 'like', '%'.$busqueda.'%')
                    ->orWhere('referencia', 'like', '%'.$busqueda.'%')
                    ->orWhere('rastreo_spei', 'like', '%'.$busqueda.'%')
                    ->orWhereHas('prospecto', function (Builder $prospectos) use ($busqueda) {
                        $prospectos->where('prospectos_nombres', 'like', '%'.$busqueda.'%')
                            ->orWhere('prospectos_apellidos', 'like', '%'.$busqueda.'%');
                    });
                if (ctype_digit($busqueda)) {
                    $query->orWhere('inscripciones_id', (int) $busqueda);
                }
            });
        }
        if ($estado !== 'todos') $query->where('estado', $estado);
        if ($metodoId !== null) $query->where('metodo_pago_id', $metodoId);
        if ($this->fechasValidas()) {
            if (! empty($this->fechaDesde)) $query->whereDate('fecha_pago', '>=', $this->fechaDesde);
            if (! empty($this->fechaHasta)) $query->whereDate('fecha_pago', '<=', $this->fechaHasta);
        }

        $detalle = $this->pagoDetalleId ? Pago::query()->with([
            'prospecto', 'responsablePago', 'metodoPago', 'confirmedBy', 'cancelledBy',
            'aplicaciones.cargo.conceptoCobro',
        ])->find($this->normalizarId($this->pagoDetalleId)) : null;
        $pagoCancelar = $this->pagoCancelarId ? Pago::query()->with(['prospecto', 'metodoPago'])->find($this->normalizarId($this->pagoCancelarId)) : null;

        return view('livewire.show-pagos', [
            'pagos' => $query->orderByDesc('fecha_pago')->orderByDesc('pago_id')->paginate($porPagina),
            'metodos' => MetodoPago::query()->ordenados()->get(['metodo_pago_id', 'nombre']),
            'detalle' => $detalle,
            'pagoCancelar' => $pagoCancelar,
        ]);
    }

    private function limpiarCancelacion(): void
    {
        $this->olvidarTokenCancelacion();
        $this->pagoCancelarId = null;
        $this->motivoCancelacion = '';
        $this->cancelacionToken = null;
        $this->mostrarModalCancelacion = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function normalizarId($id): ?int
    {
        if ((! is_int($id) && ! is_string($id)) || ! ctype_digit((string) $id) || (int) $id < 1) return null;
        return (int) $id;
    }

    private function fechasValidas(bool $mostrarErrores = false): bool
    {
        $validas = true;
        foreach (['fechaDesde' => 'La fecha desde', 'fechaHasta' => 'La fecha hasta'] as $campo => $etiqueta) {
            $valor = $this->{$campo};
            $fecha = is_string($valor) && preg_match('/^\d{4}-\d{2}-\d{2}$/D', $valor) === 1
                ? \DateTimeImmutable::createFromFormat('!Y-m-d', $valor)
                : false;
            $esValida = $valor === '' || ($fecha !== false && $fecha->format('Y-m-d') === $valor);
            if (! $esValida) {
                $validas = false;
                if ($mostrarErrores) $this->addError($campo, $etiqueta.' debe ser una fecha real con formato AAAA-MM-DD.');
            }
        }
        if ($validas && ! empty($this->fechaDesde) && ! empty($this->fechaHasta) && $this->fechaDesde > $this->fechaHasta) {
            $validas = false;
            if ($mostrarErrores) $this->addError('fechaHasta', 'La fecha hasta debe ser posterior o igual a la fecha desde.');
        }

        return $validas;
    }

    private function validarFechas(): void
    {
        $this->resetErrorBag(['fechaDesde', 'fechaHasta']);
        $this->fechasValidas(true);
    }

    private function crearTokenCancelacion(int $pagoId, int $usuarioId): string
    {
        $contenido = implode('|', [$pagoId, $usuarioId, 'cancelar-pago', now()->timestamp]);
        $contenidoCodificado = rtrim(strtr(base64_encode($contenido), '+/', '-_'), '=');
        $token = $contenidoCodificado.'.'.hash_hmac('sha256', $contenidoCodificado, (string) config('app.key'));
        session()->put($this->claveTokenCancelacion($usuarioId), hash('sha256', $token));

        return $token;
    }

    private function tokenCancelacionValido($token, int $pagoId, int $usuarioId): bool
    {
        if (! is_string($token) || ! str_contains($token, '.')) return false;
        [$contenidoCodificado, $firma] = explode('.', $token, 2);
        if ($contenidoCodificado === '' || ! preg_match('/^[a-f0-9]{64}$/D', $firma)) return false;
        $firmaEsperada = hash_hmac('sha256', $contenidoCodificado, (string) config('app.key'));
        if (! hash_equals($firmaEsperada, $firma)) return false;
        $tokenActivo = session()->get($this->claveTokenCancelacion($usuarioId));
        if (! is_string($tokenActivo) || ! hash_equals($tokenActivo, hash('sha256', $token))) return false;
        $contenido = base64_decode(strtr($contenidoCodificado, '-_', '+/'), true);
        if ($contenido === false) return false;
        $partes = explode('|', $contenido);
        if (count($partes) !== 4 || ! ctype_digit($partes[3])) return false;
        $emitido = (int) $partes[3];

        return hash_equals((string) $pagoId, $partes[0])
            && hash_equals((string) $usuarioId, $partes[1])
            && hash_equals('cancelar-pago', $partes[2])
            && $emitido <= now()->timestamp
            && $emitido >= now()->timestamp - self::CANCELACION_TOKEN_TTL;
    }

    private function olvidarTokenCancelacion(): void
    {
        if (Auth::check()) session()->forget($this->claveTokenCancelacion((int) Auth::id()));
    }

    private function claveTokenCancelacion(int $usuarioId): string
    {
        return 'show-pagos.cancelacion-token.'.$usuarioId;
    }
}
