<?php

namespace Apiato\Core\Generator\Commands;

use Apiato\Core\Generator\GeneratorCommand;
use Apiato\Core\Generator\Interfaces\ComponentsGenerator;
use Symfony\Component\Console\Input\InputOption;

class EventHandlerGenerator extends GeneratorCommand implements ComponentsGenerator
{
    /**
     * User required/optional inputs expected to be passed while calling the command.
     * This is a replacement of the `getArguments` function "which reads whenever it's called".
     */
    public array $inputs = [
        ['event', null, InputOption::VALUE_OPTIONAL, 'The Event to generate this Handler for'],
    ];

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'apiato:generate:eventhandler';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a new EventHandler class';

    /**
     * The type of class being generated.
     */
    protected string $fileType = 'EventHandler';

    /**
     * The structure of the file path.
     */
    protected string $pathStructure = '{section-name}/{container-name}/Events/Handlers/*';

    /**
     * The structure of the file name.
     */
    protected string $nameStructure = '{file-name}';

    /**
     * The name of the stub file.
     */
    protected string $stubName = 'events/eventhandler.stub';

    public function getUserInputs(): array
    {
        $event = $this->checkParameterOrAsk('event', 'Enter the name of the Event to generate this Handler for');

        $this->printInfoMessage('!!! Do not forget to register the Event and/or EventHandler !!!');

        return [
            'path-parameters' => [
                'section-name'   => $this->sectionName,
                'container-name' => $this->containerName,
            ],
            'stub-parameters' => [
                'section-name'   => $this->sectionName,
                'container-name' => $this->containerName,
                'class-name'     => $this->fileName,
                'model'          => $event,
            ],
            'file-parameters' => [
                'file-name' => $this->fileName,
            ],
        ];
    }
}
