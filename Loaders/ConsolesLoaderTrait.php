<?php

namespace Apiato\Core\Loaders;

use Apiato\Core\Foundation\Facades\Apiato;
use Illuminate\Support\Facades\File;

trait ConsolesLoaderTrait
{
    public function loadConsolesFromContainers(string $containerPath): void
    {
        $containerCommandsDirectory = $containerPath . '/UI/CLI/Commands';

        $this->loadTheConsoles($containerCommandsDirectory);
    }

    public function loadConsolesFromShip(): void
    {
        $commandsFoldersPaths = [
            // Ship commands
            base_path('app/Ship/Commands'),
            // Core commands
            __DIR__ . '/../Commands',
        ];

        foreach ($commandsFoldersPaths as $folderPath) {
            $this->loadTheConsoles($folderPath);
        }
    }

    private function loadTheConsoles($directory): void
    {
        if (File::isDirectory($directory)) {
            $files = File::allFiles($directory);

            foreach ($files as $consoleFile) {
                // Do not load route files
                if (!$this->isRouteFile($consoleFile)) {
                    $consoleClass = Apiato::getClassFullNameFromFile($consoleFile->getPathname());
                    // When user from the Main Service Provider, which extends Laravel
                    // service provider you get access to `$this->commands`
                    $this->commands([$consoleClass]);
                }
            }
        }
    }

    private function isRouteFile($consoleFile): bool
    {
        return $consoleFile->getFilename() === 'Routes.php';
    }
}
