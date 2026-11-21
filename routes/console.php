<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('lpa:demo-seed {--force : Allow creating isolated fictional demo records in production}', function (): int {
    if (app()->environment('production') && ! $this->option('force')) {
        $this->error('Production requires the explicit --force option. Review your database configuration first.');
        return 1;
    }

    $this->call('db:seed', [
        '--class' => \Database\Seeders\LpaPublicDemoSeeder::class,
        '--force' => true,
    ]);

    $this->info('Fictional records ready in DEMO-PUBLIC. Public display remains controlled by LPA_PUBLIC_DEMO_ENABLED.');
    return 0;
})->purpose('Create isolated fictional LPA public demo records without deleting existing data');
