<?php

namespace Tests\Feature;

use App\Models\ComprobantePago;
use App\Models\Pago;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ComprobantePagoMigrationTest extends PagosTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        (require database_path('migrations/2026_09_21_000001_create_comprobantes_pago_tables.php'))->up();
    }

    public function test_esquema_modelo_relaciones_y_unicidad_por_pago(): void
    {
        $this->assertTrue(Schema::hasColumns('comprobantes_pago', ['comprobante_pago_id','pago_id','folio','anio_folio','secuencia_folio','disco','ruta_pdf','hash_sha256','mime_type','tamano_bytes','generado_en','generado_por']));
        $pago = Pago::create($this->paymentAttributes());
        $datos = ['pago_id'=>$pago->getKey(),'folio'=>'REC-2026-000001','anio_folio'=>2026,'secuencia_folio'=>1,'disco'=>'local','ruta_pdf'=>'comprobantes_pago/2026/01/a.pdf','hash_sha256'=>str_repeat('a',64),'mime_type'=>'application/pdf','tamano_bytes'=>100,'generado_en'=>now()];
        DB::table('comprobantes_pago')->insert($datos);
        $this->expectException(QueryException::class);
        DB::table('comprobantes_pago')->insert(array_merge($datos, ['folio'=>'REC-2026-000002','secuencia_folio'=>2,'ruta_pdf'=>'comprobantes_pago/2026/01/b.pdf']));
    }

    public function test_configuracion_y_relacion_del_modelo(): void
    {
        $modelo = new ComprobantePago();
        $this->assertSame('comprobantes_pago', $modelo->getTable());
        $this->assertSame('comprobante_pago_id', $modelo->getKeyName());
        $this->assertSame('datetime', $modelo->getCasts()['generado_en']);
        $this->assertSame('integer', $modelo->getCasts()['tamano_bytes']);
        $this->assertSame(ComprobantePago::class, (new Pago())->comprobantePago()->getRelated()::class);
    }
}
