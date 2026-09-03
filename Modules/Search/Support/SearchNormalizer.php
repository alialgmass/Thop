<?php

namespace Modules\Search\Support;

/**
 * Normalizes free-text so that common Arabic and English fabric-name spelling
 * variants collapse to one comparable form (US-SRC-01, SRC-FR-01). The same
 * transform is applied to the stored searchable text ({@see ProductSearchIndexer})
 * and to every incoming query term, so matching is driver-independent — the
 * MySQL FULLTEXT path and the SQLite LIKE fallback both compare normalized text.
 */
class SearchNormalizer
{
    /**
     * Arabic diacritics (tashkeel) and the tatweel elongation mark — dropped.
     */
    private const ARABIC_MARKS = "\u{0610}-\u{061A}\u{064B}-\u{065F}\u{0670}\u{06D6}-\u{06DC}\u{06DF}-\u{06E8}\u{06EA}-\u{06ED}\u{0640}";

    /**
     * Letter folds applied to Arabic text: alef variants → bare alef,
     * taa-marbuta → haa, alef-maqsura → yaa, hamza carriers → plain hamza seat.
     *
     * @var array<string, string>
     */
    private const ARABIC_FOLDS = [
        'أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا',
        'ة' => 'ه',
        'ى' => 'ي',
        'ؤ' => 'و',
        'ئ' => 'ي',
        'ء' => '',
    ];

    /**
     * Small curated list of common English transliterations / alternate
     * spellings of fabric names seen in the Egyptian market. Seeded minimal
     * and intentionally conservative; Admin-editable expansion is a later
     * concern (MNT-NFR-02).
     *
     * @var array<string, string>
     */
    private const SYNONYMS = [
        'coton' => 'cotton',
        'katan' => 'linen',
        'kattan' => 'linen',
        'kotton' => 'cotton',
        'satin' => 'satin',
        'saten' => 'satin',
        'crepe' => 'crepe',
        'crape' => 'crepe',
        'krep' => 'crepe',
        'denim' => 'denim',
        'jeans' => 'denim',
        'lycra' => 'lycra',
        'licra' => 'lycra',
        'chiffon' => 'chiffon',
        'shiffon' => 'chiffon',
        'sheffon' => 'chiffon',
    ];

    public function normalize(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        $value = trim($value);

        if ($value === '') {
            return '';
        }

        // Strip Arabic diacritics and tatweel.
        $value = preg_replace('/['.self::ARABIC_MARKS.']/u', '', $value) ?? $value;

        // Apply Arabic letter folds.
        $value = strtr($value, self::ARABIC_FOLDS);

        // Lowercase (affects Latin; harmless for Arabic).
        $value = mb_strtolower($value, 'UTF-8');

        // Collapse any run of whitespace to a single space.
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;

        // Apply the English synonym map token by token.
        $tokens = array_map(
            fn (string $token): string => self::SYNONYMS[$token] ?? $token,
            explode(' ', $value),
        );

        return trim(implode(' ', $tokens));
    }
}
