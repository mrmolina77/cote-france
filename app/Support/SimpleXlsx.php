<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

final class SimpleXlsx
{
    public static function create(array $rows): string
    {
        $path=tempnam(sys_get_temp_dir(),'reporte-xlsx-'); $zip=new ZipArchive();
        if($path===false || $zip->open($path,ZipArchive::CREATE|ZipArchive::OVERWRITE)!==true) throw new RuntimeException('No fue posible crear el archivo XLSX.');
        $zip->addFromString('[Content_Types].xml','<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml','<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels','<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $xml='<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach($rows as $ri=>$row){$xml.='<row r="'.($ri+1).'">'; foreach(array_values($row) as $ci=>$value){$ref=self::column($ci).($ri+1); $safe=self::safe((string)$value); $xml.='<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars($safe,ENT_XML1|ENT_QUOTES,'UTF-8').'</t></is></c>';}$xml.='</row>';}
        $xml.='</sheetData></worksheet>'; $zip->addFromString('xl/worksheets/sheet1.xml',$xml); $zip->close(); return $path;
    }

    public static function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u',$value) ? "'".$value : $value;
    }

    private static function column(int $i): string { $s=''; do{$s=chr(65+($i%26)).$s;$i=intdiv($i,26)-1;}while($i>=0);return $s; }
}
