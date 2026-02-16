<?php

namespace App\Services;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ExcelReaderService
{
    protected ?Spreadsheet $spreadsheet = null;

    public function load(string $path): self
    {
        $this->spreadsheet = IOFactory::load($path);

        return $this;
    }

    /**
     * @return array<int, string> 0-based index => sheet name
     */
    public function getSheetNames(): array
    {
        if (! $this->spreadsheet) {
            return [];
        }
        $names = [];
        foreach ($this->spreadsheet->getAllSheets() as $index => $sheet) {
            $names[$index] = $sheet->getTitle();
        }

        return $names;
    }

    /**
     * Get total row count (highest row with any data) on the sheet.
     */
    public function getRowCount(int $sheetIndex): int
    {
        if (! $this->spreadsheet) {
            return 0;
        }
        $sheet = $this->spreadsheet->getSheet($sheetIndex);

        return $sheet->getHighestRow();
    }

    /**
     * Get rows from sheet. Header row is 1-based (first row = 1).
     * Returns rows from headerRow to end (inclusive), each row as array of cell values by column index (0-based).
     *
     * @return array<int, array<int, mixed>>
     */
    public function getRows(int $sheetIndex, int $headerRow, ?int $limit = null): array
    {
        if (! $this->spreadsheet) {
            return [];
        }
        $sheet = $this->spreadsheet->getSheet($sheetIndex);
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestDataColumn();
        $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $rows = [];
        $count = 0;
        for ($r = $headerRow; $r <= $highestRow; $r++) {
            $row = [];
            for ($c = 1; $c <= $maxColIndex; $c++) {
                $row[$c - 1] = $this->cellToScalar($sheet->getCellByColumnAndRow($c, $r)->getValue());
            }
            $rows[] = $row;
            $count++;
            if ($limit !== null && $count >= $limit) {
                break;
            }
        }

        return $rows;
    }

    /**
     * Get a single row (1-based) as array of values by column index (0-based).
     *
     * @return array<int, mixed>
     */
    public function getRow(int $sheetIndex, int $rowNumber): array
    {
        if (! $this->spreadsheet) {
            return [];
        }
        $sheet = $this->spreadsheet->getSheet($sheetIndex);
        $highestCol = $sheet->getHighestDataColumn();
        $maxColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);
        $row = [];
        for ($c = 1; $c <= $maxColIndex; $c++) {
            $row[$c - 1] = $this->cellToScalar($sheet->getCellByColumnAndRow($c, $rowNumber)->getValue());
        }

        return $row;
    }

    /**
     * Ensure cell value is scalar (no DateTime, RichText, etc.) so it's safe for JSON/Livewire.
     */
    private function cellToScalar(mixed $value): string|int|float|bool|null
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_object($value) && method_exists($value, '__toString')) {
            return (string) $value;
        }

        return (string) $value;
    }

    /**
     * Column index to Excel column letter (0 => A, 1 => B, ...).
     */
    public static function columnLetter(int $index): string
    {
        return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
    }
}
