<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Bulk-import template cache
    |--------------------------------------------------------------------------
    | Directory where the pre-built .xlsx import templates are cached. They are
    | warmed at deploy time (`php artisan import:build-templates`) and served by
    | the download route, keeping openspout off the request hot path. Overridable
    | so tests can point at an isolated directory instead of real storage.
    */
    'template_cache_dir' => storage_path('app/import-templates'),
];
