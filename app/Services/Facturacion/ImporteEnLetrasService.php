<?php

namespace App\Services\Facturacion;

use InvalidArgumentException;

class ImporteEnLetrasService
{
    public function convertir(string $importe, string $moneda = 'MXN'): string
    {
        if (preg_match('/^(0|[1-9]\d{0,12})(?:\.(\d{1,2}))?$/D', $importe, $m) !== 1) {
            throw new InvalidArgumentException('El importe debe ser un decimal no negativo con máximo dos decimales.');
        }
        $enteros = (int) $m[1];
        $centavos = (int) str_pad($m[2] ?? '', 2, '0');
        $numero = $this->apocopar($this->numero($enteros));
        if (strtoupper($moneda) !== 'MXN') {
            return ucfirst($numero).' '.strtoupper($moneda).' con '.sprintf('%02d/100', $centavos);
        }
        $pesos = $enteros === 1 ? 'peso' : 'pesos';
        $cent = $centavos === 1 ? 'centavo' : 'centavos';
        return ucfirst($numero).' '.$pesos.' con '.$this->numero($centavos).' '.$cent;
    }

    private function numero(int $n): string
    {
        if ($n === 0) return 'cero';
        if ($n < 30) return [1=>'uno',2=>'dos',3=>'tres',4=>'cuatro',5=>'cinco',6=>'seis',7=>'siete',8=>'ocho',9=>'nueve',10=>'diez',11=>'once',12=>'doce',13=>'trece',14=>'catorce',15=>'quince',16=>'dieciséis',17=>'diecisiete',18=>'dieciocho',19=>'diecinueve',20=>'veinte',21=>'veintiuno',22=>'veintidós',23=>'veintitrés',24=>'veinticuatro',25=>'veinticinco',26=>'veintiséis',27=>'veintisiete',28=>'veintiocho',29=>'veintinueve'][$n];
        if ($n < 100) { $d=[3=>'treinta',4=>'cuarenta',5=>'cincuenta',6=>'sesenta',7=>'setenta',8=>'ochenta',9=>'noventa']; return $d[intdiv($n,10)].($n%10?' y '.$this->numero($n%10):''); }
        if ($n < 1000) { if ($n===100) return 'cien'; $c=[1=>'ciento',2=>'doscientos',3=>'trescientos',4=>'cuatrocientos',5=>'quinientos',6=>'seiscientos',7=>'setecientos',8=>'ochocientos',9=>'novecientos']; return $c[intdiv($n,100)].($n%100?' '.$this->numero($n%100):''); }
        if ($n < 1000000) { $m=intdiv($n,1000); return ($m===1?'mil':$this->apocopar($this->numero($m)).' mil').($n%1000?' '.$this->numero($n%1000):''); }
        if ($n < 1000000000000) { $m=intdiv($n,1000000); return ($m===1?'un millón':$this->apocopar($this->numero($m)).' millones').($n%1000000?' '.$this->numero($n%1000000):''); }
        $b=intdiv($n,1000000000000); return ($b===1?'un billón':$this->apocopar($this->numero($b)).' billones').($n%1000000000000?' '.$this->numero($n%1000000000000):'');
    }

    private function apocopar(string $texto): string
    {
        return preg_replace(['/veintiuno$/u','/ y uno$/u','/uno$/u'], ['veintiún',' y un','un'], $texto);
    }
}
