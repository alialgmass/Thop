<?php

namespace Modules\Catalog\Support;

use Modules\Catalog\Http\Requests\StoreProductImportRequest;
use RuntimeException;

/**
 * Reads an uploaded bulk-import CSV (Phase 3.3). Native fgetcsv only — no
 * spreadsheet dependency (CLAUDE.md: dependencies need approval). XLSX support
 * is a later follow-up once a reader library is signed off on the issue.
 *
 * The file is parsed eagerly on construction so a malformed file (not a CSV, no
 * matching header row) fails fast with a {@see RuntimeException} — the caller
 * can mark the batch failed without wrapping the row loop in a broad catch.
 * The 2 MB upload cap ({@see StoreProductImportRequest})
 * bounds the memory this holds.
 */
class ProductCsvReader
{
    /**
     * @var list<array{line: int, values: array<string, string>}>
     */
    private array $records = [];

    /**
     * @throws RuntimeException when the file cannot be read or its header row does not match the template
     */
    public function __construct(string $absolutePath)
    {
        $handle = @fopen($absolutePath, 'r');

        if ($handle === false) {
            throw new RuntimeException('The import file could not be opened.');
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false || $header === null) {
                throw new RuntimeException('The import file is empty.');
            }

            $header = array_map(
                static fn ($value): string => strtolower(trim(self::stripBom((string) $value))),
                $header,
            );

            if (array_intersect($header, ProductCsvTemplate::HEADERS) === []) {
                throw new RuntimeException('The import file header row does not match the template.');
            }

            $line = 1;

            while (($record = fgetcsv($handle)) !== false) {
                $line++;

                if (self::isBlank($record)) {
                    continue;
                }

                $values = [];
                foreach ($header as $index => $column) {
                    if ($column === '') {
                        continue;
                    }
                    $values[$column] = isset($record[$index]) ? trim((string) $record[$index]) : '';
                }

                $this->records[] = ['line' => $line, 'values' => $values];
            }
        } finally {
            fclose($handle);
        }
    }

    /**
     * Data rows keyed by their 1-based line number in the file (the header is
     * line 1), so a row number in the result report points at the same line the
     * seller sees in their spreadsheet.
     *
     * @return array<int, array<string, string>>
     */
    public function rows(): array
    {
        return array_column($this->records, 'values', 'line');
    }

    /**
     * @param  list<string|null>  $record
     */
    private static function isBlank(array $record): bool
    {
        foreach ($record as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private static function stripBom(string $value): string
    {
        return str_starts_with($value, "\xEF\xBB\xBF") ? substr($value, 3) : $value;
    }
}
