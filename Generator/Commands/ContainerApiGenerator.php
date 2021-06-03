<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputOption;

class ContainerApiGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     */
    public array $inputs = [
        ['docversion', null, InputOption::VALUE_OPTIONAL, 'The version of all endpoints to be generated (1, 2, ...)'],
        ['doctype', null, InputOption::VALUE_OPTIONAL, 'The type of all endpoints to be generated (private, public)'],
        ['url', null, InputOption::VALUE_OPTIONAL, 'The base URI of all endpoints (/stores, /cars, ...)'],
        ['transporters', null, InputOption::VALUE_OPTIONAL, 'Use specific Transporters'],
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:container:api';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a Container for apiato from scratch (API Part)';

    /**
     * The type of class being generated.
     */
    protected string $fileType = 'Container';

    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{section-name}/{container-name}/*';

    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';

    /**
     * The name of the stub file.
     */
    protected string $stubName = 'composer.stub';

    public function getUserInputs(): array
    {
        $ui = 'api';

        $useTransporters = $this->checkParameterOrConfirm('transporters', 'Would you like to use specific Transporters?', true);

        // section name as inputted and lower
        $sectionName  = $this->sectionName;
        $_sectionName = Str::lower($this->sectionName);

        // Container name as inputted and lower
        $containerName  = $this->containerName;
        $_containerName = Str::lower($this->containerName);

        // Name of the model (singular and plural)
        $model  = $this->containerName;
        $models = Pluralizer::plural($model);

        // Add the README file
        $this->printInfoMessage('Generating README File');
        $this->call('apiato:generate:readme', [
            '--section'   => $sectionName,
            '--container' => $containerName,
            '--file'      => 'README',
        ]);

        // Create the configuration file
        $this->printInfoMessage('Generating Configuration File');
        $this->call('apiato:generate:configuration', [
            '--section'   => $sectionName,
            '--container' => $containerName,
            '--file'      => Str::camel($this->sectionName) . '-' . Str::camel($this->containerName),
        ]);

        // Create the MainServiceProvider for the container
        $this->printInfoMessage('Generating MainServiceProvider');
        $this->call('apiato:generate:serviceprovider', [
            '--section'   => $sectionName,
            '--container' => $containerName,
            '--file'      => 'MainServiceProvider',
            '--stub'      => 'mainserviceprovider',
        ]);

        // Create the model and repository for this container
        $this->printInfoMessage('Generating Model and Repository');
        $this->call('apiato:generate:model', [
            '--section'    => $sectionName,
            '--container'  => $containerName,
            '--file'       => $model,
            '--repository' => true,
        ]);

        // Create the migration file for the model
        $this->printInfoMessage('Generating a basic Migration file');
        $this->call('apiato:generate:migration', [
            '--section'   => $sectionName,
            '--container' => $containerName,
            '--file'      => 'create_' . Str::snake($models) . '_table',
            '--tablename' => Str::snake($models),
            '--new'       => true,
        ]);

        // Create a transformer for the model
        $this->printInfoMessage('Generating Transformer for the Model');
        $this->call('apiato:generate:transformer', [
            '--section'   => $sectionName,
            '--container' => $containerName,
            '--file'      => $containerName . 'Transformer',
            '--model'     => $model,
            '--full'      => false,
        ]);

        // Create the default routes for this container
        $this->printInfoMessage('Generating Default Routes');
        $version = $this->checkParameterOrAsk('docversion', 'Enter the version for *all* API endpoints (integer)', 1);
        $doctype = $this->checkParameterOrChoice('doctype', 'Select the type for *all* endpoints', ['private', 'public'], 0);

        // Get the URI and remove the first trailing slash
        $url = Str::lower($this->checkParameterOrAsk('url', 'Enter the base URI for all endpoints (foo/bar)', Str::lower($models)));
        $url = ltrim($url, '/');

        $this->printInfoMessage('Creating Requests for Routes');
        $this->printInfoMessage('Generating Default Actions');
        $this->printInfoMessage('Generating Default Tasks');

        $routes = [
            [
                'stub'        => 'GetAll',
                'name'        => 'GetAll' . $models,
                'operation'   => 'getAll' . $models,
                'verb'        => 'GET',
                'url'         => $url,
                'action'      => 'GetAll' . $models . 'Action',
                'request'     => 'GetAll' . $models . 'Request',
                'task'        => 'GetAll' . $models . 'Task',
                'transporter' => 'GetAll' . $models . 'Transporter',
            ],
            [
                'stub'        => 'Find',
                'name'        => 'Find' . $model . 'ById',
                'operation'   => 'find' . $model . 'ById',
                'verb'        => 'GET',
                'url'         => $url . '/{id}',
                'action'      => 'Find' . $model . 'ById' . 'Action',
                'request'     => 'Find' . $model . 'ById' . 'Request',
                'task'        => 'Find' . $model . 'ById' . 'Task',
                'transporter' => 'Find' . $model . 'ById' . 'Transporter',
            ],
            [
                'stub'        => 'Create',
                'name'        => 'Create' . $model,
                'operation'   => 'create' . $model,
                'verb'        => 'POST',
                'url'         => $url,
                'action'      => 'Create' . $model . 'Action',
                'request'     => 'Create' . $model . 'Request',
                'task'        => 'Create' . $model . 'Task',
                'transporter' => 'Create' . $model . 'Transporter',
            ],
            [
                'stub'        => 'Update',
                'name'        => 'Update' . $model,
                'operation'   => 'update' . $model,
                'verb'        => 'PATCH',
                'url'         => $url . '/{id}',
                'action'      => 'Update' . $model . 'Action',
                'request'     => 'Update' . $model . 'Request',
                'task'        => 'Update' . $model . 'Task',
                'transporter' => 'Update' . $model . 'Transporter',
            ],
            [
                'stub'        => 'Delete',
                'name'        => 'Delete' . $model,
                'operation'   => 'delete' . $model,
                'verb'        => 'DELETE',
                'url'         => $url . '/{id}',
                'action'      => 'Delete' . $model . 'Action',
                'request'     => 'Delete' . $model . 'Request',
                'task'        => 'Delete' . $model . 'Task',
                'transporter' => 'Delete' . $model . 'Transporter',
            ],
        ];

        foreach ($routes as $route) {
            $this->call('apiato:generate:route', [
                '--section'    => $sectionName,
                '--container'  => $containerName,
                '--file'       => $route['name'],
                '--ui'         => $ui,
                '--operation'  => $route['operation'],
                '--doctype'    => $doctype,
                '--docversion' => $version,
                '--url'        => $route['url'],
                '--verb'       => $route['verb'],
            ]);

            $this->call('apiato:generate:request', [
                '--section'         => $sectionName,
                '--container'       => $containerName,
                '--file'            => $route['request'],
                '--ui'              => $ui,
                '--stub'            => $route['stub'],
                '--transporter'     => $useTransporters,
                '--transportername' => $route['transporter'],
            ]);

            $this->call('apiato:generate:action', [
                '--section'   => $sectionName,
                '--container' => $containerName,
                '--file'      => $route['action'],
                '--model'     => $model,
                '--stub'      => $route['stub'],
            ]);

            $this->call('apiato:generate:task', [
                '--section'   => $sectionName,
                '--container' => $containerName,
                '--file'      => $route['task'],
                '--model'     => $model,
                '--stub'      => $route['stub'],
            ]);
        }

        // Finally generate the controller
        $this->printInfoMessage('Generating Controller to wire everything together');
        $this->call('apiato:generate:controller', [
            '--section'   => $sectionName,
            '--container' => $containerName,
            '--file'      => 'Controller',
            '--ui'        => $ui,
            '--stub'      => 'crud.' . $ui,
        ]);

        $this->printInfoMessage('Generating Composer File');

        return [
            'path-parameters' => [
                'section-name'   => $this->sectionName,
                'container-name' => $this->containerName,
            ],
            'stub-parameters' => [
                '_section-name'   => $_sectionName,
                'section-name'    => $this->sectionName,
                '_container-name' => $_containerName,
                'container-name'  => $containerName,
                'class-name'      => $this->fileName,
            ],
            'file-parameters' => [
                'file-name' => $this->fileName,
            ],
        ];
    }

    /**
     * Get the default file name for this component to be generated.
     */
    public function getDefaultFileName(): string
    {
        return 'composer';
    }

    public function getDefaultFileExtension(): string
    {
        return 'json';
    }
}
