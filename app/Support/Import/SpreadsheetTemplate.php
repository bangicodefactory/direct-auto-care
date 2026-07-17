<?php

namespace App\Support\Import;

use Illuminate\Support\Facades\File;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Builds a downloadable .xlsx import template: a "Données" sheet with the header
 * row and one example row, plus a "Valeurs autorisées" sheet listing the allowed
 * values / rules.
 *
 * {@see build()} writes a fresh file to a temp path. Because generating one runs
 * openspout (zip compression) — a per-request memory spike that can trip shared-
 * host resource limits — the templates are instead pre-built once (at deploy time,
 * via `import:build-templates`) and cached under storage; the download route then
 * serves the cached file. {@see persist()} / {@see cachedPath()} manage that cache.
 */
class SpreadsheetTemplate
{
    /** Directory (under storage) holding the pre-built template cache. */
    private const CACHE_DIR = 'app/import-templates';

    /**
     * Absolute path where the cached template for $filename lives (whether or not
     * it exists yet).
     */
    public static function cachedPath(string $filename): string
    {
        return storage_path(self::CACHE_DIR.'/'.$filename);
    }

    /**
     * Move a freshly-built temp file into the cache under $filename (overwriting
     * any previous copy) and return its final absolute path.
     */
    public static function persist(string $filename, string $tempPath): string
    {
        $destination = self::cachedPath($filename);
        File::ensureDirectoryExists(dirname($destination));
        // rename() won't overwrite on Windows; drop any stale copy first.
        File::delete($destination);
        File::move($tempPath, $destination);

        return $destination;
    }

    /**
     * @param  list<string>  $headers
     * @param  list<string|int|null>  $example
     * @param  list<string>  $notes
     */
    public static function build(array $headers, array $example, array $notes): string
    {
        $path = tempnam(sys_get_temp_dir(), 'import_tpl_');

        $writer = new Writer();
        $writer->openToFile($path);

        $writer->getCurrentSheet()->setName('Données');
        $writer->addRow(Row::fromValues($headers));
        $writer->addRow(Row::fromValues($example));

        $notesSheet = $writer->addNewSheetAndMakeItCurrent();
        $notesSheet->setName('Valeurs autorisées');
        foreach ($notes as $line) {
            $writer->addRow(Row::fromValues([$line]));
        }

        $writer->close();

        return $path;
    }
}
