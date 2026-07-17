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
    /** Directory holding the pre-built template cache (overridable for tests). */
    public static function cacheDir(): string
    {
        return config('import.template_cache_dir');
    }

    /**
     * Absolute path where the cached template for $filename lives (whether or not
     * it exists yet).
     */
    public static function cachedPath(string $filename): string
    {
        return self::cacheDir().'/'.$filename;
    }

    /**
     * Atomically publish a freshly-built temp file into the cache under $filename
     * and return its final absolute path.
     *
     * The build lands in the system temp dir, which may be a different filesystem
     * than the cache, so we cannot rename() it straight into place (EXDEV). Instead
     * we copy() it to a sibling of the destination (same filesystem) and rename()
     * that into place — an atomic swap: a concurrent download always sees either the
     * old file or the new one, never a missing file mid-publish.
     */
    public static function persist(string $filename, string $tempPath): string
    {
        $destination = self::cachedPath($filename);
        File::ensureDirectoryExists(dirname($destination));

        $staged = $destination.'.'.getmypid().'.tmp';
        if (! @copy($tempPath, $staged)) {
            throw new \RuntimeException("Failed to stage import template at {$staged}.");
        }
        File::delete($tempPath);

        // Windows rename() cannot overwrite an existing file; POSIX rename() is an
        // atomic replace, so only clear the destination first on Windows.
        if (PHP_OS_FAMILY === 'Windows') {
            File::delete($destination);
        }

        if (! @rename($staged, $destination)) {
            File::delete($staged);
            throw new \RuntimeException("Failed to publish import template to {$destination}.");
        }

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
