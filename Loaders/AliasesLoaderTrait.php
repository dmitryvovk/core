<?php

namespace Apiato\Core\Loaders;

use Illuminate\Foundation\AliasLoader;

trait AliasesLoaderTrait
{
    public function loadAliases(): void
    {
        // `$this->aliases` is declared on each Container's Main Service Provider
        foreach ($this->aliases ?? [] as $aliasKey => $aliasValue) {
            if (class_exists($aliasValue)) {
                $this->loadAlias($aliasKey, $aliasValue);
            }
        }
    }

    /**
     * @param $aliasKey
     * @param $aliasValue
     *
     * @return void
     */
    private function loadAlias($aliasKey, $aliasValue)
    {
        AliasLoader::getInstance()->alias($aliasKey, $aliasValue);
    }
}
