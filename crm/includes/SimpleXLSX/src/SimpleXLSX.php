<?php
/**
 * Simple XLSX file parser
 * @author Sergey Shuchkin
 * @link https://github.com/shuchkin/simplexlsx
 * Simplified and modified for Angel Stones CRM
 */

class SimpleXLSX {
    private $sheets = [];
    private $data = [];
    private $filename;
    private $valid = false;

    public function __construct($filename) {
        $this->filename = $filename;
        $this->_parse();
    }

    private function _parse() {
        if (!file_exists($this->filename)) {
            return false;
        }

        $zip = new ZipArchive;
        if ($zip->open($this->filename) === true) {
            // Read workbook.xml
            if (($workbook = $zip->getFromName('xl/workbook.xml')) !== false) {
                $xml = simplexml_load_string($workbook);
                foreach ($xml->sheets->sheet as $sheet) {
                    $this->sheets[] = (string) $sheet['name'];
                }
            }

            // Read sheet1.xml (we'll focus on first sheet for simplicity)
            if (($content = $zip->getFromName('xl/worksheets/sheet1.xml')) !== false) {
                $xml = simplexml_load_string($content);
                $rows = [];
                foreach ($xml->sheetData->row as $row) {
                    $cells = [];
                    foreach ($row->c as $cell) {
                        $value = (string) $cell->v;
                        // Handle shared strings if needed
                        if (isset($cell['t']) && $cell['t'] == 's') {
                            if (($shared = $zip->getFromName('xl/sharedStrings.xml')) !== false) {
                                $strings = simplexml_load_string($shared);
                                $value = (string) $strings->si[(int)$value]->t;
                            }
                        }
                        $cells[] = $value;
                    }
                    $rows[] = $cells;
                }
                $this->data = $rows;
            }
            $zip->close();
            $this->valid = true;
            return true;
        }
        return false;
    }

    public function rows() {
        return $this->data;
    }

    public function isValid() {
        return $this->valid;
    }

    public static function parse($filename) {
        $xlsx = new self($filename);
        return $xlsx->isValid() ? $xlsx : null;
    }
}
