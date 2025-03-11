<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class InitGeneratorStructure extends Command
{
    protected $signature = 'make:generator-init 
                           {--force : Memaksa pembuatan file/direktori meskipun sudah ada}';

    protected $description = 'Inisialisasi struktur direktori dan file dasar untuk generator';

    private $baseDirectories = [
        'app/DTO',
        'app/Services/DB',
        'app/Http/Controllers/API',
        'app/Http/Controllers/DB',
        'app/Http/Requests/API',
        'app/Http/Requests/Traits',
        'app/Http/Resources/API',
        'app/Services/Traits',
    ];

    private $baseFiles = [
        'app/DTO/BaseDto.php' => 'BaseDto.php.stub',
        'app/Services/DB/BaseService.php' => 'BaseService.php.stub',
        'app/Services/DB/BaseServiceInterface.php' => 'BaseServiceInterface.php.stub',
        'app/Http/Controllers/ApiController.php' => 'ApiController.php.stub',
        'app/Services/Traits/HandlesFileUploads.php' => 'HandlesFileUploads.php.stub',
        'app/Services/Traits/WithPagination.php' => 'WithPagination.php.stub',
    ];

    public function handle()
    {
        $this->info('Menginisialisasi struktur generator...');

        // 1. Buat direktori
        $this->createDirectories();

        // 2. Buat file base
        $this->createBaseFiles();

        // 3. Publikasikan stub files
        $this->publishStubs();

        $this->info('Inisialisasi struktur generator selesai!');
        $this->info('Anda sekarang dapat menggunakan semua perintah generator.');

        return Command::SUCCESS;
    }

    private function createDirectories()
    {
        $this->info('Membuat struktur direktori...');

        foreach ($this->baseDirectories as $directory) {
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
                $this->line("  <info>✓</info> Direktori dibuat: $directory");
            } else {
                $this->line("  <comment>→</comment> Direktori sudah ada: $directory");
            }
        }
    }

    private function createBaseFiles()
    {
        $this->info('Membuat file dasar...');

        foreach ($this->baseFiles as $destination => $stub) {
            if (!File::exists($destination) || $this->option('force')) {
                $stubPath = $this->getStubPath($stub);

                if (File::exists($stubPath)) {
                    $content = File::get($stubPath);
                    // Ganti namespace jika diperlukan
                    $content = $this->replaceNamespaces($content);

                    File::put($destination, $content);
                    $this->line("  <info>✓</info> File dibuat: $destination");
                } else {
                    $this->error("  <error>✗</error> Stub tidak ditemukan: $stubPath");
                }
            } else {
                $this->line("  <comment>→</comment> File sudah ada: $destination (gunakan --force untuk menimpa)");
            }
        }
    }

    private function publishStubs()
    {
        $stubsDirectory = app_path('Console/Commands/stubs/generator');

        if (!File::exists($stubsDirectory)) {
            File::makeDirectory($stubsDirectory, 0755, true);
            $this->line("  <info>✓</info> Direktori stubs dibuat: $stubsDirectory");
        }

        // Publikasikan generator commands
        $generatorCommands = [
            'GenerateDtoFromModel.php',
            'GenerateApiClassesFromDto.php',
            'GenerateApiController.php',
            'GenerateFullModuleFromModel.php',
            'GenerateServiceFromModel.php',
            'GenerateDbControllerFromService.php',
            'GenerateWebControllerFromDb.php',
            'GenerateRequestTraitFromDto.php',
            'GenerateBaseResourceFromDto.php',
            'GenerateRepositoryFromModel.php',
        ];

        foreach ($generatorCommands as $command) {
            $source = app_path("Console/Commands/$command");
            $destination = app_path("Console/Commands/stubs/generator/$command.stub");

            if (File::exists($source)) {
                File::copy($source, $destination);
                $this->line("  <info>✓</info> Generator stub disimpan: $destination");
            } else {
                $this->error("  <error>✗</error> Generator command tidak ditemukan: $source");
            }
        }
    }

    private function getStubPath($stub)
    {
        $stubsPath = app_path('Console/Commands/stubs/generator');
        return "$stubsPath/$stub";
    }

    private function replaceNamespaces($content)
    {
        // Sesuaikan namespace jika nama paket/app berbeda
        $appNamespace = $this->laravel->getNamespace();
        $content = str_replace('App\\', $appNamespace, $content);

        return $content;
    }
}
