<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;

final class SimpleXlsx
{
    /** @param iterable<array<array-key, scalar|null>> $rows */
    public static function create(iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'reporte-xlsx-');
        $sheet = tempnam(sys_get_temp_dir(), 'reporte-sheet-');
        if ($path === false || $sheet === false) {
            if (is_string($path)) @unlink($path);
            if (is_string($sheet)) @unlink($sheet);
            throw new RuntimeException('No fue posible crear el archivo temporal XLSX.');
        }

        $stream = fopen($sheet, 'wb');
        $zip = new ZipArchive();
        if ($stream === false) { @unlink($path); @unlink($sheet); throw new RuntimeException('No fue posible escribir el archivo temporal XLSX.'); }

        try {
            if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) throw new RuntimeException('No fue posible crear el archivo XLSX.');
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Reporte" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            fwrite($stream, '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>');
            $ri = 0;
            foreach ($rows as $row) {
                $ri++;
                fwrite($stream, '<row r="'.$ri.'">');
                foreach (array_values($row) as $ci => $value) {
                    $ref = self::column($ci).$ri;
                    $safe = self::safe((string) $value);
                    fwrite($stream, '<c r="'.$ref.'" t="inlineStr"><is><t xml:space="preserve">'.htmlspecialchars($safe, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>');
                }
                fwrite($stream, '</row>');
            }
            fwrite($stream, '</sheetData></worksheet>');
            fclose($stream); $stream = null;
            if (! $zip->addFile($sheet, 'xl/worksheets/sheet1.xml') || ! $zip->close()) throw new RuntimeException('No fue posible finalizar el archivo XLSX.');
            return $path;
        } catch (\Throwable $e) {
            if ($stream !== null) fclose($stream);
            $zip->close(); @unlink($path);
            throw $e;
        } finally {
            @unlink($sheet);
        }
    }

    public static function safe(string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/u', $value) ? "'".$value : $value;
    }

    private static function column(int $i): string
    {
        $s = '';
        do { $s = chr(65 + ($i % 26)).$s; $i = intdiv($i, 26) - 1; } while ($i >= 0);
        return $s;
    }
}
