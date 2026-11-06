<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

class LoanExcelExporter
{
    /**
     * Hasilkan binary file Microsoft Excel (.xlsx) untuk Laporan Peminjaman Barang.
     *
     * @param  Collection<int, \App\Models\Loan>  $loans
     */
    public static function generate(Collection $loans, string $search = '', string $status = '', ?Carbon $printedAt = null): string
    {
        $printedAt ??= now();

        $files = [
            '[Content_Types].xml' => self::contentTypesXml(),
            '_rels/.rels' => self::rootRelsXml(),
            'xl/workbook.xml' => self::workbookXml(),
            'xl/_rels/workbook.xml.rels' => self::workbookRelsXml(),
            'xl/styles.xml' => self::stylesXml(),
            'xl/worksheets/sheet1.xml' => self::sheetXml($loans, $search, $status, $printedAt),
        ];

        return self::buildZip($files);
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Laporan Peminjaman" sheetId="1" r:id="rId1"/>'
            .'</sheets>'
            .'</workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="4">'
            // 0: Normal data font
            .'<font><sz val="10"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            // 1: Title font (14pt Bold)
            .'<font><b/><sz val="14"/><color rgb="FF0F172A"/><name val="Calibri"/></font>'
            // 2: Subtitle font (10pt muted)
            .'<font><sz val="10"/><color rgb="FF475569"/><name val="Calibri"/></font>'
            // 3: Table Header font (10pt Bold White)
            .'<font><b/><sz val="10"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font>'
            .'</fonts>'
            .'<fills count="4">'
            .'<fill><patternFill patternType="none"/></fill>'
            .'<fill><patternFill patternType="gray125"/></fill>'
            // 2: Header Blue (#1E40AF)
            .'<fill><patternFill patternType="solid"><fgColor rgb="FF1E40AF"/><bgColor indexed="64"/></patternFill></fill>'
            // 3: Alternate Row (#F8FAFC)
            .'<fill><patternFill patternType="solid"><fgColor rgb="FFF8FAFC"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="2">'
            .'<border><left/><right/><top/><bottom/><diagonal/></border>'
            // 1: Thin gray border (#CBD5E1)
            .'<border>'
            .'<left style="thin"><color rgb="FFCBD5E1"/></left>'
            .'<right style="thin"><color rgb="FFCBD5E1"/></right>'
            .'<top style="thin"><color rgb="FFCBD5E1"/></top>'
            .'<bottom style="thin"><color rgb="FFCBD5E1"/></bottom>'
            .'<diagonal/>'
            .'</border>'
            .'</borders>'
            .'<cellStyleXfs count="1">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0"/>'
            .'</cellStyleXfs>'
            .'<cellXfs count="10">'
            // 0: Default
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            // 1: Title (14pt Bold)
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="left" vertical="center"/>'
            .'</xf>'
            // 2: Subtitle
            .'<xf numFmtId="0" fontId="2" fillId="0" borderId="0" xfId="0" applyFont="1" applyAlignment="1">'
            .'<alignment horizontal="left" vertical="center"/>'
            .'</xf>'
            // 3: Table Header (Blue Fill, White Bold, Center)
            .'<xf numFmtId="0" fontId="3" fillId="2" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center" wrapText="1"/>'
            .'</xf>'
            // 4: Data Left (Even row)
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="left" vertical="center" wrapText="1"/>'
            .'</xf>'
            // 5: Data Center (Even row)
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="1" xfId="0" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            // 6: Data Number Center (Even row)
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            // 7: Data Left (Odd zebra row)
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="left" vertical="center" wrapText="1"/>'
            .'</xf>'
            // 8: Data Center (Odd zebra row)
            .'<xf numFmtId="0" fontId="0" fillId="3" borderId="1" xfId="0" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            // 9: Data Number Center (Odd zebra row)
            .'<xf numFmtId="3" fontId="0" fillId="3" borderId="1" xfId="0" applyNumberFormat="1" applyFont="1" applyFill="1" applyBorder="1" applyAlignment="1">'
            .'<alignment horizontal="center" vertical="center"/>'
            .'</xf>'
            .'</cellXfs>'
            .'<cellStyles count="1">'
            .'<cellStyle name="Normal" xfId="0" builtinId="0"/>'
            .'</cellStyles>'
            .'</styleSheet>';
    }

    /**
     * @param  Collection<int, \App\Models\Loan>  $loans
     */
    private static function sheetXml(Collection $loans, string $search, string $status, Carbon $printedAt): string
    {
        $headers = [
            'No',
            'Peminjam',
            'Email Peminjam',
            'Kode Barang',
            'Nama Barang',
            'Kategori',
            'Jumlah (Unit)',
            'Tanggal Pinjam',
            'Jatuh Tempo',
            'Status',
            'Diverifikasi Oleh',
            'Tanggal Dikembalikan',
            'Kondisi Saat Kembali',
            'Catatan',
        ];

        $minWidths = [6, 20, 26, 14, 24, 18, 14, 16, 16, 15, 20, 20, 20, 32];
        $maxLengths = $minWidths;

        $rowsData = [];
        foreach ($loans as $index => $loan) {
            $row = [
                $index + 1,
                $loan->user?->name ?? '-',
                $loan->user?->email ?? '-',
                $loan->item?->code ?? '-',
                $loan->item?->name ?? '-',
                $loan->item?->category?->name ?? '-',
                (int) $loan->quantity,
                $loan->loan_date?->translatedFormat('d M Y') ?? '-',
                $loan->due_date?->translatedFormat('d M Y') ?? '-',
                ucfirst((string) $loan->status),
                $loan->approver?->name ?? '-',
                $loan->returnLog?->returned_at?->translatedFormat('d M Y') ?? '-',
                $loan->returnLog ? ucwords((string) $loan->returnLog->condition_on_return) : '-',
                preg_replace('/\s+/', ' ', trim((string) ($loan->notes ?? '-'))) ?: '-',
            ];

            foreach ($row as $colIdx => $val) {
                $len = mb_strlen((string) $val) + 3;
                if ($len > $maxLengths[$colIdx]) {
                    $maxLengths[$colIdx] = min(48, $len);
                }
            }

            $rowsData[] = $row;
        }

        $colsXml = '<cols>';
        foreach ($maxLengths as $idx => $width) {
            $colNum = $idx + 1;
            $colsXml .= '<col min="'.$colNum.'" max="'.$colNum.'" width="'.$width.'" customWidth="1"/>';
        }
        $colsXml .= '</cols>';

        $statusLabel = $status !== '' ? ucfirst($status) : 'Semua Status';
        $subtitle = 'Dicetak pada: '.$printedAt->translatedFormat('d F Y, H:i').' WIB | Filter Status: '.$statusLabel;
        if ($search !== '') {
            $subtitle .= ' | Pencarian: "'.$search.'"';
        }

        $sheetData = '<sheetData>';

        // Baris 1: Judul Laporan
        $sheetData .= '<row r="1" ht="24" customHeight="1">'
            .self::inlineStrCell('A1', 'LAPORAN REKAPITULASI PEMINJAMAN & PENGEMBALIAN BARANG SEKOLAH', 1)
            .'</row>';

        // Baris 2: Subjudul Metadata
        $sheetData .= '<row r="2" ht="18" customHeight="1">'
            .self::inlineStrCell('A2', $subtitle, 2)
            .'</row>';

        // Baris 3: Kosong (Spasi)
        $sheetData .= '<row r="3" ht="10" customHeight="1"/>';

        // Baris 4: Header Tabel
        $sheetData .= '<row r="4" ht="26" customHeight="1">';
        foreach ($headers as $colIdx => $headerText) {
            $cellRef = self::colLetter($colIdx).'4';
            $sheetData .= self::inlineStrCell($cellRef, $headerText, 3);
        }
        $sheetData .= '</row>';

        // Baris 5+: Data Peminjaman
        $centerCols = [0, 3, 6, 7, 8, 9, 11, 12];
        $numberCols = [0, 6];

        foreach ($rowsData as $rowIndex => $rowValues) {
            $excelRow = $rowIndex + 5;
            $isOdd = ($rowIndex % 2) === 1;
            $sheetData .= '<row r="'.$excelRow.'" ht="21" customHeight="1">';

            foreach ($rowValues as $colIdx => $value) {
                $cellRef = self::colLetter($colIdx).$excelRow;

                if (in_array($colIdx, $numberCols, true)) {
                    $style = $isOdd ? 9 : 6;
                    $sheetData .= '<c r="'.$cellRef.'" s="'.$style.'"><v>'.(int) $value.'</v></c>';
                } else {
                    $isCentered = in_array($colIdx, $centerCols, true);
                    $style = $isCentered ? ($isOdd ? 8 : 5) : ($isOdd ? 7 : 4);
                    $sheetData .= self::inlineStrCell($cellRef, (string) $value, $style);
                }
            }

            $sheetData .= '</row>';
        }

        $sheetData .= '</sheetData>';

        $mergeCells = '<mergeCells count="2">'
            .'<mergeCell ref="A1:N1"/>'
            .'<mergeCell ref="A2:N2"/>'
            .'</mergeCells>';

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="4" topLeftCell="A5" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .$colsXml
            .$sheetData
            .$mergeCells
            .'</worksheet>';
    }

    private static function inlineStrCell(string $ref, string $text, int $styleIndex): string
    {
        $escaped = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="'.$ref.'" s="'.$styleIndex.'" t="inlineStr"><is><t xml:space="preserve">'.$escaped.'</t></is></c>';
    }

    private static function colLetter(int $index): string
    {
        $letter = '';
        $n = $index;
        while ($n >= 0) {
            $letter = chr(65 + ($n % 26)).$letter;
            $n = intdiv($n, 26) - 1;
        }

        return $letter;
    }

    /**
     * @param  array<string, string>  $files
     */
    private static function buildZip(array $files): string
    {
        $localParts = [];
        $centralParts = [];
        $offset = 0;

        foreach ($files as $name => $content) {
            $size = strlen($content);
            $crc = crc32($content);

            $localHeader = pack(
                'VvvvvvVVVvv',
                0x04034B50,
                20,
                0,
                0,
                0,
                0x2100,
                $crc,
                $size,
                $size,
                strlen($name),
                0
            ).$name;

            $localEntry = $localHeader.$content;
            $localParts[] = $localEntry;

            $centralParts[] = pack(
                'VvvvvvvVVVvvvvvVV',
                0x02014B50,
                20,
                20,
                0,
                0,
                0,
                0x2100,
                $crc,
                $size,
                $size,
                strlen($name),
                0,
                0,
                0,
                0,
                32,
                $offset
            ).$name;

            $offset += strlen($localEntry);
        }

        $centralDir = implode('', $centralParts);
        $count = count($files);

        $eocd = pack(
            'VvvvvVVv',
            0x06054B50,
            0,
            0,
            $count,
            $count,
            strlen($centralDir),
            $offset,
            0
        );

        return implode('', $localParts).$centralDir.$eocd;
    }
}
