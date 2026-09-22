<x-app-layout><x-slot name="header"><h2 class="font-semibold text-xl">Recibo interno {{ $comprobante->folio }}</h2></x-slot>
<div class="max-w-3xl mx-auto py-10 px-4"><div class="bg-white shadow rounded p-6 space-y-3">
<p><strong>Recibo:</strong> {{ $comprobante->folio }}</p>
<p><strong>Pago:</strong> {{ $pago->folio }}</p><p><strong>Importe:</strong> {{ $pago->moneda }} ${{ number_format($pago->monto, 2, '.', ',') }}</p>
<p><strong>Generado:</strong> {{ $comprobante->generado_en }}</p><p class="font-mono text-xs break-all"><strong>SHA-256:</strong> {{ $comprobante->hash_sha256 }}</p>
<a class="inline-block rounded bg-indigo-600 px-4 py-2 text-white" href="{{ route('facturacion.comprobantes.descargar', ['pago'=>$pago, 'comprobante'=>$comprobante]) }}">Descargar recibo</a>
</div></div></x-app-layout>
