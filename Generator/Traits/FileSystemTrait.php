<?php

namespace Apiato\Core\Generator\Traits;

use Exception;

trait FileSystemTrait
{
    /**
     * Determine if the file already exists.
     */
    protected function alreadyExists(string $path): bool
    {
        return $this->fileSystem->exists($path);
    }

    public function generateFile(string $filePath, string $stubContent): int | bool
    {
        return $this->fileSystem->put($filePath, $stubContent);
    }

    /**
     * If path is for a directory, create it otherwise do nothing.
     */
    public function createDirectory(string $path): void
    {
        if ($this->alreadyExists($path)) {
            $this->printErrorMessage($this->fileType . ' already exists');

            // the file does exist - return but NOT exit
            return;
        }

        try {
            if (!$this->fileSystem->isDirectory(dirname($path))) {
                $this->fileSystem->makeDirectory(dirname($path), 0777, true, true);
            }
        } catch (Exception) {
            $this->printErrorMessage("Could not create $path");
        }
    }
}
