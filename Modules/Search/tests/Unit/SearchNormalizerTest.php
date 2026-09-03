<?php

namespace Modules\Search\Tests\Unit;

use Modules\Search\Support\SearchNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class SearchNormalizerTest extends TestCase
{
    private SearchNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new SearchNormalizer;
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function variantProvider(): array
    {
        return [
            'null-ish empty' => ['   ', ''],
            'collapses whitespace' => ["قطن   أبيض\t\nناعم", 'قطن ابيض ناعم'],
            'alef hamza variants fold' => ['أقمشة إستيراد آمنة', 'اقمشه استيراد امنه'],
            'taa marbuta folds to haa' => ['قطنية', 'قطنيه'],
            'alef maqsura folds to yaa' => ['مصطفى', 'مصطفي'],
            'strips tashkeel' => ['قُطْن', 'قطن'],
            'strips tatweel' => ['قـــطن', 'قطن'],
            'english lowercased' => ['COTTON Fabric', 'cotton fabric'],
            'english synonym coton' => ['coton', 'cotton'],
            'english synonym krep' => ['Krep fabric', 'crepe fabric'],
            'jeans maps to denim' => ['Jeans', 'denim'],
        ];
    }

    #[Test]
    #[DataProvider('variantProvider')]
    public function it_normalizes_to_a_comparable_form(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->normalizer->normalize($input));
    }

    #[Test]
    public function a_variant_query_matches_the_normalized_product_name(): void
    {
        $storedName = $this->normalizer->normalize('قطن أبيض قطنية');
        $variantQuery = $this->normalizer->normalize('قطن ابيض قطنيه');

        $this->assertStringContainsString($variantQuery, $storedName);
    }

    #[Test]
    public function null_normalizes_to_empty_string(): void
    {
        $this->assertSame('', $this->normalizer->normalize(null));
    }
}
