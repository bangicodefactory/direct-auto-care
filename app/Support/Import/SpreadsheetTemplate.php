<?php

namespace App\Support\Import;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Builds a downloadable .xlsx import template: a "Données" sheet with the header
 * row and one example row, plus a "Valeurs autorisées" sheet listing the allowed
 * values / rules. Written to a temp file whose path is returned — the caller
 * streams it and is responsible for deleting it (e.g.
 * `response()->download($path, ...)->deleteFileAfterSend()`).
 */
class SpreadsheetTemplate
{
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
