<?php
function col_letra($i) {
    $s = '';
    $i = (int)$i + 1;
    while ($i > 0) {
        $m = ($i - 1) % 26;
        $s = chr(65 + $m) . $s;
        $i = (int)(($i - $m) / 26);
    }
    return $s;
}

function xml_texto($t) {
    $t = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', (string)$t);
    return htmlspecialchars($t, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

function exportar_excel($nombre, $titulos, $filas) {
    $base = preg_replace('/[^A-Za-z0-9_-]/', '_', $nombre) . '_' . date('Ymd');
    if (class_exists('ZipArchive')) {
        $cols = count($titulos);
        $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><cols><col min="1" max="' . max(1, $cols) . '" width="24" customWidth="1"/></cols><sheetData>';
        $hoja .= '<row r="1">';
        foreach ($titulos as $i => $t) {
            $hoja .= '<c r="' . col_letra($i) . '1" s="1" t="inlineStr"><is><t xml:space="preserve">' . xml_texto($t) . '</t></is></c>';
        }
        $hoja .= '</row>';
        $n = 2;
        foreach ($filas as $fila) {
            $hoja .= '<row r="' . $n . '">';
            $i = 0;
            foreach ($fila as $v) {
                $ref = col_letra($i) . $n;
                if ($v !== null && $v !== '' && preg_match('/^(0|[1-9]\d{0,11})(\.\d{1,4})?$/', (string)$v)) {
                    $hoja .= '<c r="' . $ref . '"><v>' . $v . '</v></c>';
                } else {
                    $hoja .= '<c r="' . $ref . '" t="inlineStr"><is><t xml:space="preserve">' . xml_texto($v) . '</t></is></c>';
                }
                $i++;
            }
            $hoja .= '</row>';
            $n++;
        }
        $hoja .= '</sheetData></worksheet>';
        $tipos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>';
        $rels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>';
        $libro = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Informe" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $librorels = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>';
        $estilos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF1F5F46"/><bgColor indexed="64"/></patternFill></fill></fills><borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs><cellXfs count="2"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
        $tmp = tempnam(sys_get_temp_dir(), 'xls');
        $zip = new ZipArchive();
        if ($zip->open($tmp, ZipArchive::OVERWRITE) === true) {
            $zip->addFromString('[Content_Types].xml', $tipos);
            $zip->addFromString('_rels/.rels', $rels);
            $zip->addFromString('xl/workbook.xml', $libro);
            $zip->addFromString('xl/_rels/workbook.xml.rels', $librorels);
            $zip->addFromString('xl/styles.xml', $estilos);
            $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
            $zip->close();
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $base . '.xlsx"');
            header('Content-Length: ' . filesize($tmp));
            readfile($tmp);
            @unlink($tmp);
            exit;
        }
        @unlink($tmp);
    }
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $base . '.xls"');
    echo "\xEF\xBB\xBF" . '<html><head><meta charset="UTF-8"></head><body><table border="1"><tr>';
    foreach ($titulos as $t) { echo '<th style="background:#1f5f46;color:#fff">' . htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8') . '</th>'; }
    echo '</tr>';
    foreach ($filas as $fila) {
        echo '<tr>';
        foreach ($fila as $v) { echo '<td>' . htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8') . '</td>'; }
        echo '</tr>';
    }
    echo '</table></body></html>';
    exit;
}
