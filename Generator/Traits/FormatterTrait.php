<?php

namespace Apiato\Core\Generator\Traits;

trait FormatterTrait
{
    /** @deprecated */
    public function capitalize(string $word): string
    {
        return ucfirst($word);
    }

    protected function trimString(string $string): string
    {
        return trim($string);
    }
}
