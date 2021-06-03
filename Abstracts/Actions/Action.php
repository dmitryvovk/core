<?php

namespace Apiato\Core\Abstracts\Actions;

use Apiato\Core\Traits\HasRequestCriteriaTrait;

abstract class Action
{
    use HasRequestCriteriaTrait;

    /**
     * Set automatically by the controller after calling an Action.
     * Allows the Action to know which UI invoke it, to modify it's behaviour based on it, when needed.
     */
    protected string $ui;

    public function getUI(): string
    {
        return $this->ui;
    }

    public function setUI(string $interface): self
    {
        $this->ui = $interface;

        return $this;
    }
}
