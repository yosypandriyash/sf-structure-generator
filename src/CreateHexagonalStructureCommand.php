<?php

namespace YosypAndriyash\SfStructureGenerator;

use YosypAndriyash\SfStructureGenerator\Trait\FileTrait;
use YosypAndriyash\SfStructureGenerator\Trait\IOTrait;
use Exception;
use RuntimeException;
use YosypAndriyash\SfStructureGenerator\Trait\CommandBodyStepsTrait;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'app:create-hex-structure')]

class CreateHexagonalStructureCommand extends Command
{
    use IOTrait;
    use FileTrait;
    use CommandBodyStepsTrait;

    protected static $defaultName = 'app:create-hex-structure';
    protected static $defaultDescription = 'Creates a new hexagonal structure';

    private const DEBUG_MAGIC_WORD = 'test';
    private const DEBUG_PACKAGE_SUBCONTEXT_NAME = 'test subcontext';
    private const DEBUG_PACKAGE_ENTITY_NAME = 'test entity name';
    private const DEBUG_PACKAGE_ACTION_NAME = 'get test package action';
    private const DEBUG_PACKAGE_COMMAND_CUSTOM_RESPONSE_ENTITY_NAME = 'command response custom entity name';

    public const IC_KEY_APP_CONTEXT_NAME = '_ContextName_';
    public const IC_KEY_APP_SUBCONTEXT_NAME = '_SubContextName_';
    public const IC_KEY_APP_DOMAIN_ENTITY_NAME = '_DomainEntityName_';
    public const IC_KEY_APP_USE_CASE_NAME = '_UseCaseName_';

    public const IC_KEY_PROJECT_PATH = '_projectPath_';

    private const PACKAGE_ACTION_TYPE_READ = 1;
    private const PACKAGE_ACTION_TYPE_COMMAND = 2;
    private const PACKAGE_ACTION_TYPE_READ_AND_COMMAND = 3;

    private const APPLICATION_RESPONSE_IS_DATA_COLLECTION = 1;
    private const APPLICATION_RESPONSE_IS_NOT_DATA_COLLECTION = 2;

    private const PACKAGE_DATA_TYPE_INT = 'int';
    private const PACKAGE_DATA_TYPE_STRING = 'string';
    private const PACKAGE_DATA_TYPE_BOOL = 'bool';
    private const PACKAGE_DATA_TYPE_FLOAT = 'float';
    private const PACKAGE_DATA_TYPE_OTHER = 'empty';

    private const PACKAGE_DATA_TYPE_DEFAULT = self::PACKAGE_DATA_TYPE_OTHER;

    /** @var $input \Symfony\Component\Console\Input\InputInterface */
    private $input = null;

    /** @var $output \Symfony\Component\Console\Output\OutputInterface */
    private $output = null;

    /** @var $helper QuestionHelper */
    private $helper = null;

    /** @var $commandInputContainer CommandInputContainer  */
    protected CommandInputContainer $commandInputContainer;

    private $projectPath = null;
    private $commandPath = null;
    private $configurationFile = 'command_configuration.yaml';
    private array $configurationData;

    protected function configure(): void
    {
        // --help
        $this->setHelp('This command allows you to implement in couple of seconds a new hexagonal structure for your vendor sub-project...');

        // Arguments for the command:
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->input = $input;
        $this->output = $output;
        $this->helper = $this->getHelper('question');
        $this->commandInputContainer = new CommandInputContainer();

        $this->projectPath = realpath(dirname(__DIR__).'/../../..') . DIRECTORY_SEPARATOR;
        $this->commandPath = realpath(__DIR__) . DIRECTORY_SEPARATOR;

        $commandStartedAt = microtime(true);

        $this->writeLine();
        $this->writeTitle([
            '',
            'Welcome to "PhpSkeletonCreator"',
            'v.2.0.1 - Release "2022_NOV"',
            '',
            $this->repeatChar(64, '-'),
            '',
            'Support contact:',
            '',
            '[  dev-team-sushi@digimobil.es  ]',
            '',
        ]);

        // Command (BODY)
        try {

            // Body
            $this->commandBody();
            $output = Command::SUCCESS;

        } catch (RuntimeException $exception) {

            $this->writeError($exception->getMessage());
            $output = Command::FAILURE;
        }

        $this->writeLine();
        $this->writeTitle([
            '',
            'Command execution finished... ( ~' . round(number_format(microtime(true) - $commandStartedAt, 4)) . 's)',
            '',
            $this->repeatChar(64, '-'),
            '',
            'Have a nice day :)',
            ''
        ]);

        return $output;
    }

    /**
     * @return void
     * @throws Exception
     */
    private function commandBody(): void
    {
        /**
         * Body was divided in command steps,
         * Each step was independent function
         * ALL COMMAND STEPS MUST BE CALLED HERE
         */
        $this->loadYamlCommandConfigurationStep();
        $selectedGeneration = $this->showGenerativeOptionsMenu();

        if (!isset($selectedGeneration)) {
            throw new Exception('');
        }

        $selectedGeneration = array_keys($this->configurationData['generativeOptions'])[$selectedGeneration] ?? [];
        $steps = $this->configurationData['generativeOptions'][$selectedGeneration]['steps'] ?? [];
        $pathConfig = $this->configurationData['generativeOptions'][$selectedGeneration]['pathConfig'] ?? [];

        foreach ($steps as $step) {
            $response = $this->executeYamlConfigStep($step, $pathConfig);
            if (!$response) {
                continue;
            }

            $this->commandInputContainer->addInput(
                $step['stepOutputVarName'],
                $response
            );
        }

        $this->createPackageFilesStep($selectedGeneration, $pathConfig);
    }

    // Steps definitions
    // ------------------------------------

    private function ensureUserInputResponseIsValid($userResponseString, $currentResponsesList = []): void
    {
        // Validate param
        if (trim($userResponseString) === '' || strlen($userResponseString) > 32) {
            throw new RuntimeException('Invalid argument length, must be in 2 - 32 chars');
        }

        // Validate was not numeric
        if (!preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]/', $userResponseString)) {
            throw new RuntimeException('Invalid name, use letters and numbers commencing by letter');
        }

        // check if repeated
        if (in_array($userResponseString, $currentResponsesList, true)) {
            throw new RuntimeException('Repeated argument');
        }
    }

    /**
     * @throws RuntimeException
     */
    private function loadYaml($filePath)
    {
        try {
            return YamlConfigurationHelper::loadConfiguration($filePath, 'commandConfiguration');
        } catch (ParseException $exception) {
            throw new RuntimeException('Unable to parse the YAML string: ' .  $exception->getMessage());
        }
    }

    private function formatToLowerCamelCase($string)
    {
        return StringHelper::formatToLowerCamelCase($string);
    }

    private function showGenerativeOptionsMenu(): ?int
    {
        $generativeOptions = $this->configurationData['generativeOptions'] ?? [];
        if (count($generativeOptions) > 0) {
            $this->writeUnderlinedTitle('Select generation layout');
            return $this->getUserResponseByAllowedOptions(array_keys($generativeOptions));
        }

        return null;
    }
}
