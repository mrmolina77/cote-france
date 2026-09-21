<?php

namespace Tests\Feature;

use App\Services\Facturacion\GeneradorFolioComprobanteService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class GeneradorFolioComprobanteServiceTest extends PagosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_21_000001_create_comprobantes_pago_tables.php'))->up();
    }

    public function test_secuencia_anual_es_atomica_y_se_reinicia(): void
    {
        config()->set('app.timezone', 'America/Mexico_City');
        $servicio = new GeneradorFolioComprobanteService();
        $this->assertSame(['folio'=>'REC-2026-000001','anio'=>2026,'secuencia'=>1], $servicio->generar(CarbonImmutable::parse('2026-01-01', 'America/Mexico_City')));
        $this->assertSame('REC-2026-000002', $servicio->generar(CarbonImmutable::parse('2026-12-01'))['folio']);
        $this->assertSame('REC-2027-000001', $servicio->generar(CarbonImmutable::parse('2027-01-01'))['folio']);
        $this->assertSame(2, DB::table('consecutivos_comprobante_pago')->where('anio', 2026)->value('ultimo_consecutivo'));
    }
}
