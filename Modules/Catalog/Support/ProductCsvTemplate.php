<?php

namespace Modules\Catalog\Support;

/**
 * The column contract for the bulk product import CSV (Phase 3.3). The same
 * header list drives the template download and the reader, so the two cannot
 * drift apart.
 *
 * Compound columns:
 *  - `color_ids`     — taxonomy colour ids separated by `;`   e.g. "3;7;12"
 *  - `price_tiers`   — `min_qty:unit_price` pairs separated by `;`  e.g. "50:41.00;200:38.50"
 *  - `price_on_contact` — "1"/"0" (or "true"/"false"); leave `price` empty when "1"
 */
class ProductCsvTemplate
{
    /**
     * @var list<string>
     */
    public const HEADERS = [
        'name_ar',
        'name_en',
        'description',
        'fabric_type_id',
        'material_id',
        'governorate_id',
        'width_cm',
        'weight_gsm',
        'unit',
        'moq',
        'quantity_available',
        'price',
        'price_on_contact',
        'color_ids',
        'price_tiers',
    ];

    /**
     * A single illustrative data row (ids are placeholders the seller replaces).
     *
     * @var list<string>
     */
    public const EXAMPLE_ROW = [
        'قطن مصري 100%',
        'Egyptian cotton 100%',
        'Soft combed cotton, pre-shrunk',
        '1',
        '1',
        '1',
        '150',
        '180',
        'per_meter',
        '50',
        '2000',
        '42.50',
        '0',
        '3;7',
        '50:41.00;200:38.50',
    ];

    /**
     * The template file body (header row + one example row), CRLF-terminated and
     * UTF-8 BOM-prefixed so Excel opens the Arabic columns correctly.
     */
    public static function toCsv(): string
    {
        $lines = [
            self::encodeRow(self::HEADERS),
            self::encodeRow(self::EXAMPLE_ROW),
        ];

        return "\xEF\xBB\xBF".implode("\r\n", $lines)."\r\n";
    }

    /**
     * Parse the `color_ids` column — taxonomy colour ids separated by `;`.
     *
     * @return list<int>
     */
    public static function parseColorIds(string $cell): array
    {
        $ids = array_filter(array_map('trim', explode(';', $cell)), static fn (string $id): bool => $id !== '');

        return array_values(array_map('intval', $ids));
    }

    /**
     * Parse the `price_tiers` column — `min_qty:unit_price` pairs separated by
     * `;`. A malformed pair is passed through with its raw parts so row
     * validation reports it rather than the parser swallowing it.
     *
     * @return list<array{min_qty: int|string, unit_price: float|string}>
     */
    public static function parsePriceTiers(string $cell): array
    {
        $tiers = [];

        foreach (array_filter(array_map('trim', explode(';', $cell))) as $pair) {
            [$minQty, $unitPrice] = array_pad(explode(':', $pair, 2), 2, '');

            $tiers[] = [
                'min_qty' => is_numeric($minQty) ? (int) $minQty : $minQty,
                'unit_price' => is_numeric($unitPrice) ? (float) $unitPrice : $unitPrice,
            ];
        }

        return $tiers;
    }

    /**
     * @param  list<string>  $values
     */
    private static function encodeRow(array $values): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $values);
        rewind($handle);
        $line = rtrim((string) stream_get_contents($handle), "\r\n");
        fclose($handle);

        return $line;
    }
}
