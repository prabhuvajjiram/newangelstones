<?php
/**
 * SimpleXLSX class 0.8.15
 *
 * MS Excel 2007 workbooks reader
 *
 * @category   SimpleXLSX
 * @package    SimpleXLSX
 * @copyright  Copyright (c) 2012 - 2020 SimpleXLSX (https://github.com/shuchkin/simplexlsx)
 * @license    MIT
 * @version    0.8.15
 */

/** Examples
 *
 * Example 1:
 * if ( $xlsx = SimpleXLSX::parse('book.xlsx') ) {
 *   print_r( $xlsx->rows() );
 * } else {
 *   echo SimpleXLSX::parseError();
 * }
 *
 * Example 2: html table
 * if ( $xlsx = SimpleXLSX::parse('book.xlsx') ) {
 *   echo '<table>';
 *   foreach( $xlsx->rows() as $r ) {
 *     echo '<tr><td>'.implode('</td><td>', $r ).'</td></tr>';
 *   }
 *   echo '</table>';
 * } else {
 *   echo SimpleXLSX::parseError();
 * }
 *
 * Example 3: rowsEx
 * $xlsx = SimpleXLSX::parse('book.xlsx');
 * print_r( $xlsx->rowsEx() );
 *
 */

class SimpleXLSX {
    // Cell types
    const TYPE_NULL = 0;
    const TYPE_NUMERIC = 1;
    const TYPE_STRING = 2;
    const TYPE_DATE = 3;
    const TYPE_BOOLEAN = 4;
    const TYPE_ERROR = 5;

    /** Directory name of XLSX file */
    public $tempdir;
    protected $numSheets = 0;
    protected $activeSheet = 0;
    protected $workbook;
    protected $sheets;
    protected $styles;
    protected $sharedstrings;
    protected $hyperlinks;
    protected $package;
    protected $datasec;
    protected $sheetInfo;
    protected $error = false;
    protected $debug = false;

    // Parse XLSX file
    public static function parse($filename, $is_data = false, $debug = false) {
        $xlsx = new self();
        $xlsx->debug = $debug;
        
        if ($xlsx->loadFile($filename, $is_data)) {
            return $xlsx;
        }
        
        return false;
    }

    public static function parseFile($filename, $debug = false) {
        return self::parse($filename, false, $debug);
    }
    
    public static function parseData($data, $debug = false) {
        return self::parse($data, true, $debug);
    }
    
    public function loadFile($filename, $is_data = false) {
        $this->datasec = null;
        
        if ($is_data) {
            $this->data = $filename;
            $filename = 'data:/' . uniqid() . '.xlsx';
        }
        
        $this->tempdir = $this->tempFilename();
        $this->package = array(
            "filename" => $filename,
            "mtime" => 0,
            "size" => 0,
            "comment" => "",
            "entries" => array()
        );
        
        if ($is_data) {
            $this->package["data"] = $this->data;
        }
        
        $this->error = false;
        
        $zip = new ZipArchive();
        
        if ($zip->open($filename) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry_info = $zip->statIndex($i);
                $this->package["entries"][] = array(
                    "name" => $entry_info["name"],
                    "is_dir" => substr($entry_info["name"], -1) === "/",
                    "mtime" => $entry_info["mtime"],
                    "size" => $entry_info["size"],
                    "comment" => $entry_info["comment"],
                    "index" => $i,
                    "crc" => $entry_info["crc"],
                    "compr_method" => $entry_info["comp_method"],
                    "compr_size" => $entry_info["comp_size"]
                );
            }
            
            $this->workbook = $this->getEntryData('xl/workbook.xml');
            
            if ($this->workbook) {
                $this->sharedstrings = $this->getEntryData('xl/sharedStrings.xml');
                $this->styles = $this->getEntryData('xl/styles.xml');
                
                // Load sheet info (sheet index and names)
                if (preg_match_all('/(<sheet.*?(name="([^"]*)")?.*?(sheetId="([^"]*)")?.*?(r:id="([^"]*)")?.*?><\/sheet>|<sheet.*?(sheetId="([^"]*)")?.*?(name="([^"]*)")?.*?(r:id="([^"]*)")?.*?\/sheet>)/si', $this->workbook, $matches, PREG_SET_ORDER)) {
                    $this->sheetInfo = [];
                    $index = 0;
                    
                    foreach ($matches as $match) {
                        $name = isset($match[3]) ? $match[3] : (isset($match[11]) ? $match[11] : "");
                        $sheetId = isset($match[5]) ? $match[5] : (isset($match[9]) ? $match[9] : "");
                        $relId = isset($match[7]) ? $match[7] : (isset($match[13]) ? $match[13] : "");
                        
                        $this->sheetInfo[$index] = [
                            'name' => $name,
                            'sheetId' => $sheetId,
                            'relId' => $relId
                        ];
                        
                        $index++;
                    }
                    
                    $this->numSheets = count($this->sheetInfo);
                }
                
                $relationship = $this->getEntryData('xl/_rels/workbook.xml.rels');
                
                // Fill in sheet paths
                if ($this->sheetInfo && $relationship) {
                    $map = [];
                    
                    if (preg_match_all('/<Relationship.*?Id="([^"]*)".*?Target="([^"]*)".*?>/si', $relationship, $matches, PREG_SET_ORDER)) {
                        foreach ($matches as $match) {
                            $map[$match[1]] = $match[2];
                        }
                    }
                    
                    for ($index = 0; $index < $this->numSheets; $index++) {
                        if (isset($map[$this->sheetInfo[$index]['relId']])) {
                            $this->sheetInfo[$index]['path'] = 'xl/' . $map[$this->sheetInfo[$index]['relId']];
                        }
                    }
                }
                
                $zip->close();
                return true;
            }
            
            $zip->close();
            $this->error = 'Workbook not found in XLSX file';
            return false;
        }
        
        $this->error = 'Unable to open XLSX file: ' . $filename;
        return false;
    }
    
    public function rows($worksheet_index = 0) {
        if ($worksheet_index === null) {
            $worksheet_index = $this->activeSheet;
        }
        
        $data = [];
        
        if (isset($this->sheetInfo[$worksheet_index]) && isset($this->sheetInfo[$worksheet_index]['path'])) {
            $sheet = $this->getEntryData($this->sheetInfo[$worksheet_index]['path']);
            
            if ($sheet) {
                // Parse sheet rows
                $rows = $this->parseSheet($sheet);
                
                if ($rows) {
                    return $rows;
                }
            }
        }
        
        return [];
    }
    
    protected function parseSheet($sheet) {
        $rows = [];
        
        // Get row data
        if (preg_match_all('/<row.*?>(.*?)<\/row>/s', $sheet, $rowMatches)) {
            foreach ($rowMatches[1] as $rowIndex => $rowData) {
                $row = [];
                
                // Get cells
                if (preg_match_all('/<c.*?(?:r="([^"]*)")?.*?(?:t="([^"]*)")?.*?(?:s="([^"]*)")?.*?(?:\/>|>(.*?)<\/c>)/s', $rowData, $cellMatches, PREG_SET_ORDER)) {
                    foreach ($cellMatches as $cellMatch) {
                        $cellRef = isset($cellMatch[1]) ? $cellMatch[1] : '';
                        $cellType = isset($cellMatch[2]) ? $cellMatch[2] : '';
                        $styleId = isset($cellMatch[3]) ? intval($cellMatch[3]) : 0;
                        $cellValue = isset($cellMatch[4]) ? $cellMatch[4] : '';
                        
                        // Get column index from cell reference (A1, B5, etc.)
                        $colIndex = 0;
                        if (preg_match('/^([A-Z]+)(\d+)$/', $cellRef, $refMatches)) {
                            $colName = $refMatches[1];
                            $colIndex = $this->getColumnIndexFromName($colName);
                        }
                        
                        // Process cell value based on type
                        if ($cellType === 's' && preg_match('/<v.*?>(.*?)<\/v>/s', $cellValue, $vMatch)) {
                            // Shared string
                            $stringIndex = intval($vMatch[1]);
                            
                            if ($this->sharedstrings) {
                                $stringValues = [];
                                if (preg_match_all('/<si.*?>(.*?)<\/si>/s', $this->sharedstrings, $stringMatches)) {
                                    foreach ($stringMatches[1] as $stringMatch) {
                                        if (preg_match('/<t.*?>(.*?)<\/t>/s', $stringMatch, $textMatch)) {
                                            $stringValues[] = $textMatch[1];
                                        } else {
                                            $stringValues[] = '';
                                        }
                                    }
                                }
                                
                                if (isset($stringValues[$stringIndex])) {
                                    $row[$colIndex] = $stringValues[$stringIndex];
                                } else {
                                    $row[$colIndex] = '';
                                }
                            } else {
                                $row[$colIndex] = '';
                            }
                        } elseif (preg_match('/<v.*?>(.*?)<\/v>/s', $cellValue, $vMatch)) {
                            // Direct value
                            $row[$colIndex] = $vMatch[1];
                        } else {
                            // Empty cell
                            $row[$colIndex] = '';
                        }
                    }
                }
                
                // Ensure row cells are in correct order
                ksort($row);
                $rows[] = array_values($row);
            }
        }
        
        return $rows;
    }
    
    protected function getColumnIndexFromName($name) {
        $index = 0;
        $length = strlen($name);
        
        for ($i = 0; $i < $length; $i++) {
            $index = $index * 26 + (ord($name[$i]) - 64);
        }
        
        return $index - 1;
    }
    
    protected function getEntryData($name) {
        foreach ($this->package["entries"] as $entry) {
            if ($entry["name"] === $name) {
                if (!isset($entry["data"])) {
                    $zip = new ZipArchive();
                    if ($zip->open($this->package["filename"]) === true) {
                        $entry["data"] = $zip->getFromIndex($entry["index"]);
                        $zip->close();
                    } else {
                        $this->error = "Unable to open archive";
                        return false;
                    }
                }
                return $entry["data"];
            }
        }
        return false;
    }
    
    protected function tempFilename() {
        $temp = tempnam(sys_get_temp_dir(), "xls");
        if ($temp !== false) {
            unlink($temp);
            return $temp;
        }
        return "";
    }
    
    public function toHTML($worksheet_index = 0) {
        $html = '<table border="1" cellpadding="3" cellspacing="0">';
        
        $rows = $this->rows($worksheet_index);
        foreach ($rows as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell, ENT_QUOTES) . '</td>';
            }
            $html .= '</tr>';
        }
        
        $html .= '</table>';
        return $html;
    }
    
    public static function parseError() {
        $xlsx = new self();
        return $xlsx->error;
    }
    
    public function sheetNames() {
        $names = [];
        
        for ($i = 0; $i < $this->numSheets; $i++) {
            if (isset($this->sheetInfo[$i]['name'])) {
                $names[$i] = $this->sheetInfo[$i]['name'];
            } else {
                $names[$i] = 'Sheet' . ($i + 1);
            }
        }
        
        return $names;
    }
    
    public function setActiveSheet($index) {
        if ($index >= 0 && $index < $this->numSheets) {
            $this->activeSheet = $index;
            return true;
        }
        
        return false;
    }

    public function getActiveSheet() {
        return $this->activeSheet;
    }
}
