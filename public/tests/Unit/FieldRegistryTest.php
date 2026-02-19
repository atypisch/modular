<?php
/**
 * Unit tests for FieldRegistry and field types (Range, Search, Hidden, Text).
 */

namespace Modular\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Modular\Fields\FieldRegistry;
use Modular\Fields\RangeField;
use Modular\Fields\SearchField;
use Modular\Fields\HiddenField;

class FieldRegistryTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Reset registry so bootDefaultFields() runs
        $ref = new \ReflectionClass(FieldRegistry::class);
        $prop = $ref->getProperty('fields');
        $prop->setAccessible(true);
        $prop->setValue([]);
    }

    public function testBootDefaultFieldsRegistersRangeSearchHidden(): void
    {
        FieldRegistry::bootDefaultFields();

        $this->assertNotNull(FieldRegistry::get('range'), 'range type should be registered');
        $this->assertNotNull(FieldRegistry::get('search'), 'search type should be registered');
        $this->assertNotNull(FieldRegistry::get('hidden'), 'hidden type should be registered');
    }

    public function testRangeFieldSanitizeAndNormalize(): void
    {
        FieldRegistry::bootDefaultFields();
        $field = FieldRegistry::get('range');
        $this->assertInstanceOf(RangeField::class, $field);

        $schema = ['min' => 0, 'max' => 100, 'default_value' => 50];
        $sanitized = $field->sanitize('75', $schema);
        $this->assertSame(75.0, $sanitized);

        $normalized = $field->normalize(75, $schema);
        $this->assertArrayHasKey('value', $normalized);
        $this->assertSame(75.0, $normalized['value']);

        $frontend = $field->frontend($normalized);
        $this->assertSame(75.0, $frontend['value']);
    }

    public function testSearchFieldSanitizeAndNormalize(): void
    {
        FieldRegistry::bootDefaultFields();
        $field = FieldRegistry::get('search');
        $this->assertInstanceOf(SearchField::class, $field);

        $schema = ['default_value' => ''];
        $sanitized = $field->sanitize('  query  ', $schema);
        $this->assertSame('query', $sanitized);

        $normalized = $field->normalize('query', $schema);
        $this->assertSame('query', $normalized['value']);
    }

    public function testHiddenFieldSanitizeAndNormalize(): void
    {
        FieldRegistry::bootDefaultFields();
        $field = FieldRegistry::get('hidden');
        $this->assertInstanceOf(HiddenField::class, $field);

        $schema = ['default_value' => 'secret'];
        $sanitized = $field->sanitize(null, $schema);
        $this->assertSame('secret', $sanitized);

        $normalized = $field->normalize('stored', $schema);
        $this->assertSame('stored', $normalized['value']);
    }

    public function testListForAdminIncludesNewTypes(): void
    {
        FieldRegistry::bootDefaultFields();
        $list = FieldRegistry::listForAdmin();
        $types = array_column($list, 'type');

        $this->assertContains('range', $types);
        $this->assertContains('search', $types);
        $this->assertContains('hidden', $types);
    }

    public function testHiddenIsNotUtilityType(): void
    {
        $this->assertFalse(FieldRegistry::isUtilityType('hidden'));
    }
}
