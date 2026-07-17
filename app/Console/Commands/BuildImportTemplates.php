<?php

namespace App\Console\Commands;

use App\Services\Import\ProductImportService;
use App\Services\Import\VehicleImportService;
use Illuminate\Console\Command;

class BuildImportTemplates extends Command
{
    protected $signature = 'import:build-templates';

    protected $description = 'Pre-build the vehicle & product bulk-import .xlsx templates into storage, so the download route serves a static file instead of running openspout per request';

    public function handle(VehicleImportService $vehicles, ProductImportService $products): int
    {
        $this->components->task('Building vehicle import template', fn () => $vehicles->rebuildTemplate());
        $this->components->task('Building product import template', fn () => $products->rebuildTemplate());

        $this->info('Import templates rebuilt.');

        return self::SUCCESS;
    }
}
