<?php

namespace Tests\Unit;

use App\Support\Import\SpreadsheetReader;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

class SpreadsheetReaderTest extends TestCase
{
    /**
     * Write the given rows to a temp .xlsx and return its path.
     *
     * @param  list<array<int, string|int|null>>  $rows
     */
    private function xlsx(array $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'reader_test_');

        $writer = new Writer();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return $path;
    }

    public function test_maps_rows_to_normalised_headers(): void
    {
        $path = $this->xlsx([
            [' Name ', 'SKU'],
            ['Widget', 'W-1'],
        ]);

        $result = SpreadsheetReader::read($path, 100);
        @unlink($path);

        $this->assertFalse($result['truncated']);
        $this->assertCount(1, $result['rows']);
        $this->assertSame(2, $result['rows'][0]['row']);
        $this->assertSame(['name' => 'Widget', 'sku' => 'W-1'], $result['rows'][0]['data']);
    }

    public function test_skips_blank_rows(): void
    {
        $path = $this->xlsx([
            ['name'],
            ['A'],
            [''],   // fully-blank row — must not produce a data entry
            ['B'],
        ]);

        $result = SpreadsheetReader::read($path, 100);
        @unlink($path);

        $this->assertCount(2, $result['rows']);
        $this->assertSame(['A', 'B'], array_map(static fn ($r) => $r['data']['name'], $result['rows']));
    }

    public function test_caps_rows_at_the_limit_and_flags_truncation(): void
    {
        $path = $this->xlsx([
            ['name'],
            ['A'], ['B'], ['C'],
        ]);

        $result = SpreadsheetReader::read($path, 2);
        @unlink($path);

        $this->assertTrue($result['truncated']);
        $this->assertCount(2, $result['rows']);
        $this->assertSame(['A', 'B'], array_map(static fn ($r) => $r['data']['name'], $result['rows']));
    }

    public function test_duplicate_headers_keep_the_first_column(): void
    {
        $path = $this->xlsx([
            ['sku', 'sku'],
            ['FIRST', 'SECOND'],
        ]);

        $result = SpreadsheetReader::read($path, 100);
        @unlink($path);

        $this->assertSame('FIRST', $result['rows'][0]['data']['sku']);
    }
}
