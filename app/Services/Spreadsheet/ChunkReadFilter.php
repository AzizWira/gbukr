<?php

namespace App\Services\Spreadsheet;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    public function __construct(
        private readonly int $startRow,
        private readonly int $endRow,
        private readonly int $headerRows = 20,
        private readonly ?string $worksheetName = null,
    ) {
    }

    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        if ($this->worksheetName !== null && $worksheetName !== '' && $worksheetName !== $this->worksheetName) {
            return false;
        }

        return $row <= $this->headerRows || ($row >= $this->startRow && $row <= $this->endRow);
    }
}
