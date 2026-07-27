<?php

namespace Tests\Unit;

use App\Support\XlsxWorkbookReader;
use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_that_true_is_true(): void
    {
        $this->assertTrue(true);
    }

    public function test_reader_handles_namespaced_excel_cell_values(): void
    {
        $reader = new XlsxWorkbookReader();
        $sheets = $reader->read(dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'F137.xlsx');

        $this->assertNotEmpty($sheets);
        $this->assertNotEmpty($sheets[0]['rows']);
        $this->assertNotEmpty($sheets[0]['rows'][0]['cells']);
    }
}
