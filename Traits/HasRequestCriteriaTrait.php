<?php

namespace Apiato\Core\Traits;

use Apiato\Core\Abstracts\Criterias\PrettusRequestCriteria as RequestCriteria;
use Apiato\Core\Abstracts\Repositories\Repository;
use Apiato\Core\Exceptions\CoreInternalErrorException;

trait HasRequestCriteriaTrait
{
    public function addRequestCriteria(?Repository $repository = null): self
    {
        $validatedRepository = $this->validateRepository($repository);
        $validatedRepository->pushCriteria(app(RequestCriteria::class));

        return $this;
    }

    public function removeRequestCriteria($repository = null): self
    {
        $validatedRepository = $this->validateRepository($repository);
        $validatedRepository->popCriteria(RequestCriteria::class);

        return $this;
    }

    /**
     * Validates, if the given Repository exists or uses $this->repository on the Task/Action to apply functions.
     *
     * @throws CoreInternalErrorException
     */
    private function validateRepository(?Repository $repository)
    {
        $validatedRepository = $repository;

        // Check if we have a "custom" repository
        if ($repository === null) {
            if (!isset($this->repository)) {
                throw new CoreInternalErrorException('No protected or public accessible repository available');
            }
            $validatedRepository = $this->repository;
        }

        // Check, if the validated repository is null
        if ($validatedRepository === null) {
            throw new CoreInternalErrorException();
        }

        // Check if it is a Repository class
        if (!($validatedRepository instanceof Repository)) {
            throw new CoreInternalErrorException();
        }

        return $validatedRepository;
    }
}
