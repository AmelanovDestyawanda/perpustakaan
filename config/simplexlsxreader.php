<?php
// ============================================================
//  config/SimpleXlsxReader.php
//
//  Pembaca file .xlsx super ringan TANPA library/komposer
//  pihak ketiga. File .xlsx pada dasarnya adalah ZIP yang
//  berisi beberapa XML, jadi kita cukup memakai ekstensi PHP
//  bawaan: ZipArchive + SimpleXML.
//
//  Hanya membaca sheet PERTAMA dan mengembalikan array baris
//  (tiap baris = array kolom berurutan A, B, C, ...).
//  Cukup untuk kebutuhan import data sederhana seperti import
//  buku di halaman admin.
// ============================================================

class SimpleXlsxReader
{
    /**
     * Baca file .xlsx dan kembalikan array of rows.
     * Setiap row adalah array asosiatif index numerik (0-based)
     * sesuai urutan kolom di sheet.
     *
     * @param string $filePath Path ke file .xlsx
     * @return array
     * @throws Exception
     */
    public static function read(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception('Ekstensi PHP "zip" tidak aktif di server. Tidak bisa membaca file .xlsx.');
        }

        $zip = new ZipArchive();
        if ($zip->open($filePath) !== true) {
            throw new Exception('File .xlsx tidak valid atau rusak.');
        }

        // 1) Baca shared strings (string yang dipakai berulang di sheet)
        $sharedStrings = [];
        $sharedXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedXml !== false) {
            $sharedStrings = self::parseSharedStrings($sharedXml);
        }

        // 2) Cari sheet pertama. Biasanya xl/worksheets/sheet1.xml
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // fallback: cari file sheet apapun di folder worksheets
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#^xl/worksheets/sheet\d+\.xml$#', $name)) {
                    $sheetXml = $zip->getFromName($name);
                    break;
                }
            }
        }
        $zip->close();

        if ($sheetXml === false || $sheetXml === null) {
            throw new Exception('Tidak menemukan data sheet di dalam file .xlsx.');
        }

        return self::parseSheet($sheetXml, $sharedStrings);
    }

    private static function parseSharedStrings(string $xml): array
    {
        $result = [];
        $sxml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($sxml === false) return $result;

        foreach ($sxml->si as $si) {
            if (isset($si->t)) {
                $result[] = (string)$si->t;
            } else {
                // rich text terdiri dari beberapa <r><t>..</t></r>
                $text = '';
                foreach ($si->r as $r) {
                    $text .= (string)$r->t;
                }
                $result[] = $text;
            }
        }
        return $result;
    }

    private static function parseSheet(string $xml, array $sharedStrings): array
    {
        $sxml = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($sxml === false) {
            throw new Exception('Gagal membaca struktur XML sheet.');
        }

        $rows = [];
        foreach ($sxml->sheetData->row as $row) {
            $rowData = [];
            foreach ($row->c as $cell) {
                $ref = (string)$cell['r']; // contoh: "A1", "B1", dst
                $colIndex = self::columnLetterToIndex(preg_replace('/[0-9]/', '', $ref));
                $type = (string)$cell['t'];

                $value = '';
                if ($type === 's') {
                    // shared string -> index ke tabel sharedStrings
                    $idx = (int)$cell->v;
                    $value = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $value = (string)$cell->is->t;
                } elseif ($type === 'str') {
                    $value = (string)$cell->v;
                } else {
                    // numeric / default
                    $value = (string)$cell->v;
                }

                $rowData[$colIndex] = $value;
            }

            // isi gap kolom yang kosong agar index berurutan
            if (!empty($rowData)) {
                $maxIdx = max(array_keys($rowData));
                $ordered = [];
                for ($i = 0; $i <= $maxIdx; $i++) {
                    $ordered[$i] = $rowData[$i] ?? '';
                }
                $rows[] = $ordered;
            } else {
                $rows[] = [];
            }
        }

        return $rows;
    }

    /**
     * Ubah huruf kolom Excel (A, B, ..., Z, AA, AB, ...) ke index 0-based
     */
    private static function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;
        $len = strlen($letters);
        for ($i = 0; $i < $len; $i++) {
            $index = $index * 26 + (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}