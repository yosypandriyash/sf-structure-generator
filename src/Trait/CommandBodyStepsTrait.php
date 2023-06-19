<?php

namespace YosypAndriyash\SfStructureGenerator\Trait;

use YosypAndriyash\SfStructureGenerator\CommandInputContainer;
use YosypAndriyash\SfStructureGenerator\CreateHexagonalStructureCommand;
use YosypAndriyash\SfStructureGenerator\PathHelper;
use YosypAndriyash\SfStructureGenerator\StepParser;
use YosypAndriyash\SfStructureGenerator\StringHelper;
use YosypAndriyash\SfStructureGenerator\Templates\BasePhpClassTemplate;
use Exception;
use RuntimeException;

trait CommandBodyStepsTrait {

    /** @var $commandInputContainer CommandInputContainer  */
    protected CommandInputContainer $commandInputContainer;

    protected bool $isDebuggingMode = false;

    protected bool $logModeEnabled = false;

    private function loadYamlCommandConfigurationStep(): void
    {
        $title = '> Loading command configuration file...';

        $this->writeLine($this->repeatChar(strlen($title), '-'));
        $this->writeLine($title);
        $this->writeLine();

        // Check if project has self "command_configuration.yaml" in  src/Command/HexagonalStructureCommand/Configuration path
        $customConfiguration = [];
        $selfConfigurationFilePath =
            $this->projectPath . 'src' . DIRECTORY_SEPARATOR . 'Command' . DIRECTORY_SEPARATOR . 'HexagonalStructureCommand' .
            DIRECTORY_SEPARATOR . 'Configuration' . DIRECTORY_SEPARATOR;

        if (!is_dir($selfConfigurationFilePath)) {
            PathHelper::createDir($selfConfigurationFilePath);
        }

        $selfConfigurationFile = $selfConfigurationFilePath . $this->configurationFile;

        // if not exist file on these path, show advertisement to user
        // Copy default file from vendor to app path

        // if not exist App\Templates path show advertisement to user
        // Copy it from vendor to app path (empty?)

        // if not exist App\UserMethods class file, show advertisement to user
        // Copy it from vendor to app path

        // check if works this
        // if works: Ok!

        if (!file_exists($selfConfigurationFile)) {

            $this->writeLine();
            $this->writeComment($this->repeatChar(64, '-'));
            $this->writeComment('Self application command configuration not found (looking for ' . $selfConfigurationFile . ')');
            $this->writeComment('Please create this file to store the ideal configuration for your project to avoid possible future errors.');
            $this->writeComment('Default configuration may vary throughout the development of the command');
            $this->writeLine();
            $this->writeComment('Tip: you can copy the default command-config file into ' . $selfConfigurationFilePath . ' path');
            $this->writeComment($this->repeatChar(64, '-'));
            $this->writeLine();

        } else {
            $customConfiguration = $this->loadYaml($selfConfigurationFile);
        }
        
        // if exist, compare two existing files and join/merge config (self-project config file will be first)

        // default configuration file
        $configuration = $this->loadYaml($this->commandPath . 'Configuration' . DIRECTORY_SEPARATOR . $this->configurationFile);

        if (!$configuration) {
            throw new RuntimeException('Invalid YAML configuration');
        }

        // merge config files into single config array
        foreach ($configuration as $key => $configItem) {
            if (isset($customConfiguration[$key])) {
                $configuration[$key] = $customConfiguration[$key];
                $this->writeSuccess('Import ' . $key . ' configuration from custom file done');
            }
        }

        $this->configurationData = $configuration;

        $this->writeLine();
        $this->writeLine();
        $this->writeSuccess('> Command configuration successfully loaded...');
        $this->writeLine($this->repeatChar(strlen($title), '-'));
        $this->writeLine();
        $this->writeLine();
        $this->writeLine();
    }

    private function executeYamlConfigStep(array $step = null, array $pathConfig = null): mixed
    {

        if (!$step || !$pathConfig) {
            return null;
        }

        // Step init
        $stepData = $step;
        $stepParser = new StepParser($stepData);
        $userResponse = null;

        // check executeConditions
        $preExecutionConditions = $stepParser->getExecuteConditions() ?? null;

        if (!empty($preExecutionConditions)) {
            if (!is_array($preExecutionConditions)) {
                $preExecutionConditions = [$preExecutionConditions];
            }

            foreach ($preExecutionConditions as $condition) {
                if (!$this->evalYamlCondition($condition)) {
                    return null;
                }
            }
        }

        try {
            // Step on debug:
            if ($this->isDebuggingMode === true) {
                if (empty($stepParser->getStepOnDebugDefaultValue())) {
                    throw new Exception('Missing "On debug default value". Please enter that value manually...');
                } else {
                    return $stepParser->getStepOnDebugDefaultValue();
                }
            }

            // Show step title
            if (!empty(trim($stepParser->getStepTitle()))) {
                $this->writeLine();
                $title = $this->parseStringWithYamlPlaceholders($stepParser->getStepTitle());
                $this->writeUnderlinedTitle($title);
            }

            // Show description
            $description = $this->parseStringWithYamlPlaceholders($stepParser->getStepDescription());
            $this->writeLine($description);

            // onStepBeginCallback
            $onBegin = $stepParser->getStepOnBegin() ?? null;
            if ($onBegin !== null) {
                $onBeginCallbackFunction = $onBegin['callback'] ?? null;

                if (is_string($onBeginCallbackFunction) && trim($onBeginCallbackFunction) !== '') {
                    $onBeginCallbackFunction = [$onBeginCallbackFunction];
                }

                if (is_array($onBeginCallbackFunction)) {
                    foreach ($onBeginCallbackFunction as $callbackFunction) {
                        try {
                            $userResponse = $this->executeCallback($callbackFunction, $pathConfig, $userResponse);
                        } catch (Exception $exception) {
                            $this->writeError($exception->getMessage());
                            continue;
                        }
                    }
                }

                $onBeginCallbackReturnOnEnd = $onBegin['returnOnEnd'] ?? false;
                if ($onBeginCallbackReturnOnEnd === true) {
                    return $userResponse;
                }
            }

            $allowedOptions = $stepParser->getStepOutputAllowedOptions() ?? [];

            // Get user response
            if ($this->isDebuggingMode === true && $stepParser->getStepOnDebugDefaultValue() !== null) {
                $userResponse = trim($stepParser->getStepOnDebugDefaultValue());
            } else {
                if (is_array($allowedOptions) && !empty($allowedOptions)) {
                    $userResponse = $this->getUserResponseByAllowedOptions($allowedOptions);

                } else {
                    $userResponse = trim($this->getUserInputRequest());
                }
            }

            // Validate user response
            $outputType = $stepParser->getStepOutputVarType() ?? null;
            if ($outputType !== null) {
                // Must check if user response is valid
                switch ($outputType) {
                    case StepParser::OUTPUT_TYPE_INTEGER:
                        $userResponse = (int) $userResponse;
                        break;

                    case StepParser::OUTPUT_TYPE_STRING:
                        $userResponse = (string) $userResponse;
                        break;

                    case StepParser::OUTPUT_TYPE_BOOLEAN:
                        $userResponse = (bool) $userResponse;
                        break;

                    case StepParser::OUTPUT_TYPE_FLOAT:
                        $userResponse = (float) $userResponse;
                        break;
                }
            }

            // Output regex validation:
            $regexValidationType = $stepParser->getStepOutputVarRegex();
            if ($regexValidationType !== null) {
                if (in_array($regexValidationType, array_keys(StepParser::REGEX_VALIDATION_TYPES))) {
                    $regexValidationType = StepParser::REGEX_VALIDATION_TYPES[$regexValidationType];
                }

                if (!preg_match($regexValidationType, $userResponse)) {
                    throw new RuntimeException('Your response doesnt match with defined regex: ("' . $regexValidationType . '") Fix it');
                }
            }

            // At this point all validations have been passed

            // inputEndCallBackFunction
            $inputCallbackFunction = $stepParser->getStepOnInputEndCallback() ?? null;
            if ($inputCallbackFunction !== null) {

                if (is_string($inputCallbackFunction) && trim($inputCallbackFunction) !== '') {
                    $inputCallbackFunction = [$inputCallbackFunction];
                }

                if (is_array($inputCallbackFunction)) {
                    foreach ($inputCallbackFunction as $callbackFunction) {
                        try {
                            $userResponse = $this->executeCallback($callbackFunction, $pathConfig, $userResponse);
                        } catch (Exception $exception) {
                            $this->writeError($exception->getMessage());
                            continue;
                        }
                    }
                }
            }

            //$packageSubContextNameUserResponse = ucfirst($this->formatToLowerCamelCase($packageSubContextNameUserResponse));
            //$this->ensureUserInputResponseIsValid($packageSubContextNameUserResponse);

        } catch (RuntimeException $exception) {
            $this->writeError($exception->getMessage());
            return $this->executeYamlConfigStep($step, $pathConfig);
        }

        return $userResponse;
    }

    private function executeCallback($callbackFunction, $pathConfig, $callbackInput = ""): mixed
    {
        $arguments = null;
        if (stripos($callbackFunction, '(') !== false) {
            $callbackFunctionPrev = $callbackFunction;
            $callbackFunction = substr($callbackFunction, 0, stripos($callbackFunction, '('));

            $argumentsString = substr($callbackFunctionPrev, stripos($callbackFunctionPrev, '('));
            $argumentsString = str_replace(['(', ')'],'', $argumentsString);
            $argumentsString = explode(',', $argumentsString);

            $arguments = [];
            foreach ($argumentsString as $item) {
                if ($this->commandInputContainer->existsInput($item)) {
                    $arguments[] = $this->commandInputContainer->getInput($item);
                } else {
                    $arguments[] = $item;
                }
            }

            if (count($arguments) === 1) {
                $arguments = $arguments[0];
            }
        }

        $customMethodsClass = $pathConfig['customMethodsClass'] ?? null;
        $existsCustomMethodsClass = null;

        if ($customMethodsClass && class_exists($customMethodsClass)) {
            try {
                $existsCustomMethodsClass = new $customMethodsClass ?? null;
            } catch (Exception $exception) {
            }
        }

        if (
            (!is_object($existsCustomMethodsClass) || !method_exists($existsCustomMethodsClass, $callbackFunction)) &&
            !method_exists($this, $callbackFunction) &&
            !function_exists($callbackFunction)
        ) {
            throw new Exception('Callback method "' . $callbackFunction . '" not applied. Reason: not found.');
        }

        $prev = $callbackInput;
        if (is_array($arguments)) {
            if (!empty($callbackInput)) {
                $callbackInput = array_merge($arguments, [$callbackInput]);
            } else {
                $callbackInput = $arguments;
            }
        }

        if (is_object($existsCustomMethodsClass) && method_exists($existsCustomMethodsClass, $callbackFunction)) {
            $callbackInput = call_user_func([$existsCustomMethodsClass, $callbackFunction], $callbackInput);

        } else if (method_exists($this, $callbackFunction)) {
            $callbackInput = call_user_func([$this, $callbackFunction], $callbackInput);

        } else if (function_exists($callbackFunction)){
            $callbackInput = call_user_func($callbackFunction, $callbackInput);
        }

        if (!is_array($prev) && !is_array($callbackInput)) {
            $this->writeOnLogMode(
                'Callback method "' . $callbackFunction . '" applied. Input: "' . $prev. '" Output: "' . $callbackInput . '"'
            );
        }

        return $callbackInput;
    }

    private function createPackageFilesStep(string $selectedGeneration, array $pathConfig): void
    {
        $baseFilePath = ($this->projectPath . ($pathConfig['outputPath'])) ?? null;
        $this->configurationData['namespaceConfig'] = $pathConfig ?? [];
        $templateDispatcherNamespace = $pathConfig['templateDispatcherNamespace'] ?? null;
        $templatesFilesPath = $pathConfig['templatesFilesPath'] ?? null;

        $templatesConfig = $this->configurationData['generativeOptions'][$selectedGeneration]['templates'];
        $classFilesToParse = [];

        foreach ($templatesConfig as $key => $value) {

            $conditionPassed = true;
            if (isset($value['parseConditions']) && !empty($value['parseConditions'])) {
                if (!is_array($value['parseConditions'])) {
                    $value['parseConditions'] = [$value['parseConditions']];
                }

                foreach ($value['parseConditions'] as $condition) {
                    $conditionPassed = $this->evalYamlCondition($condition);
                    if (!$conditionPassed) {
                        break;
                    }
                }
            }

            if (!$conditionPassed) {
                continue;
            }

            $className = $this->parseStringWithYamlPlaceholders($value['className']) . ($value['suffix'] ?? '');
            $fileName = $className . '.php';
            $filePath = $value['outputPath'] . $fileName;

            $classFilesToParse[] = array_merge($value, [
                'filePath' => $filePath,
                'className' => $className
            ]);
        }

        $classFilesToParse = BasePhpClassTemplate::sortByDependencies($classFilesToParse);
        $createdInstances = [];

        $this->writeLine();
        $this->writeSuccess($this->repeatChar(64, '-'));
        $this->writeSuccess('Creating paths & files:');
        $this->writeSuccess($this->repeatChar(64, '-'));

        foreach ($classFilesToParse as $item) {
            $item['filePath'] = $baseFilePath . $this->parseFilePathWithContainerParams($item['filePath']);

            if (!empty($templateDispatcherNamespace)) {
                $templateDispatcher = $templateDispatcherNamespace;
            } else {
                $templateDispatcher = BasePhpClassTemplate::getNamespace();
            }

            $templateDispatcher = $templateDispatcher . '\\' . $item['templateClassDispatcher'];

            if (!class_exists($templateDispatcher)) {
                $this->writeComment('Template parser for class template ' . $item['templateClassDispatcher'] . ' not found, please create this file! [continue...]');
                continue;
            }

            $templateSource = $this->commandPath . 'Templates/Files/' . $item['template'];
            if (!empty($templatesFilesPath)) {
                $templateSource = $templatesFilesPath . '/' . $item['template'];
            }

            try {
                /** @var BasePhpClassTemplate $templateDispatcher */
                $instance = new $templateDispatcher(
                    $this->configurationData,
                    $this->commandInputContainer,
                    $this->getTemplateContent($templateSource),
                    $item,
                    $createdInstances
                );

                $createdInstances[$item['templateClassDispatcher']] = $instance;
                $parsedTemplate = $instance->generateParsedTemplateOutput();

                $successClassFileCreated = $this->createFile($item['filePath'], $parsedTemplate);
            } catch (RuntimeException $exception) {

            }
        }
    }

    private function getUserResponseByAllowedOptions(array $options = [], string $description = ''): mixed
    {
        try {
            // Show output message
            $this->writeLine($description);
            $this->writeLine('Select option of above:');
            $allowedValues = [];

            foreach ($options as $key => $value) {
                $this->writeLine($key . ': ' . $value);
                $allowedValues[] = $key;
            }

            $userResponse = strtolower(trim($this->getUserInputRequest()));

            if (!in_array($userResponse, $allowedValues)) {
                throw new RuntimeException('Invalid response');
            }

            return $userResponse;

        } catch (RuntimeException) {
            $this->writeError('Respuesta inválida');
            return $this->getUserResponseByAllowedOptions($options);
        }
    }

    private function getBoolUserResponseByYesOrNot(): int
    {
        try {
            $userResponse = strtolower(trim($this->getUserInputRequest()));

            if (!in_array($userResponse, ['y', 'n'])) {
                throw new RuntimeException('Invalid response');
            }

            return $userResponse === 'y' ? 1 : 0;

        } catch (RuntimeException) {
            $this->writeError('Invalid answer, type one: "y" or "n"');
            return $this->getBoolUserResponseByYesOrNot();
        }
    }

    private function getBoolUserResponseByYesOrNotStep($entityProperties): array
    {
        $queryParams = [];
        foreach ($entityProperties as $entityProperty) {
            $this->writeLine('Do you want to use "' . $entityProperty['name'] . '" as use-case param?');
            $this->writeComment('Select option: "y" or "n"');

            $userResponse = $this->getBoolUserResponseByYesOrNot();
            if ($userResponse === 1) {
                $queryParams[] = $entityProperty;
            }
        }

        return $queryParams;
    }

    private function parseFilePathWithContainerParams($filePath): string
    {
        $parts = explode('/', $filePath);
        foreach ($parts as $index => $part) {
            $firstLetter = substr($part,0,1);
            $lastLetter = substr($part,strlen($part) - 1,1);
            if ('%' === $lastLetter && '%' === $firstLetter) {
                $key = substr($part, 1, strlen($part) - 2);
                if ($this->commandInputContainer->existsInput($key)) {
                    $parts[$index] = $this->commandInputContainer->getInput($key);
                } else {
                    $parts[$index] = $key;
                }
            }
        }

        return implode('/', $parts);
    }

    private function parseStringWithYamlPlaceholders($input): string
    {
        $output = $input;
        if (stripos($input, '%') !== false) {
            $output = [];
            foreach (explode(' ', $input) as $word) {
                if (stripos($word, '%') !== false) {
                    if ($this->commandInputContainer->existsInput(str_replace('%', '', $word))) {
                        $word = $this->commandInputContainer->getInput(str_replace('%', '', $word));
                    }
                }
                $output[] = $word;
            }
            $output = implode(' ', $output);
        }

        return $output;
    }

    private function evalYamlCondition($string): bool
    {
        $conditionsPassed = true;
        $condition = $this->parseStringWithYamlPlaceholders($string);
        // Critical: eval can execute any php sentences (must add some regex to filter any risk)
        eval('$conditionsPassed=(' . $condition . ');');
        return $conditionsPassed;
    }

    // Callback methods definition (These methods wast called via YAML configuration file)
    private function toLowerCamelCase(string $input): string
    {
        return StringHelper::formatToLowerCamelCase($input);
    }

    private function checkDebugKeyWord($input): string
    {
        if ($input === self::DEBUG_MAGIC_WORD) {
            $this->writeOnLogMode('Debug/test mode enabled successfully');
            $this->isDebuggingMode = true;
            $input .= time();
        }

        return $input;
    }

    private function checkIssetGetSetOnUseCaseName($array): string
    {
        $domainName = $array[0] ?? '';
        $userValue = $array[1] ?? '';

        if (in_array($userValue, ['get', 'set'])) {
            return $userValue . ucfirst($domainName);
        }

        return $userValue;
    }

    private function classPropertiesNamesWizardCallback($definedParams = null): array
    {
        if (!$definedParams) {
            $definedParams = [];
        }

        // Do question
        $this->writeLine('Press "Enter" to add or "0" (zero number) to finish');
        $newParam = trim($this->getUserInputRequest());

        if ($newParam !== '0') {
            try {
                $newParam = $this->formatToLowerCamelCase($newParam);

                // Throws exception if response is not valid
                $this->ensureUserInputResponseIsValid($newParam, $definedParams);

                // If response is not valid next step never occurs
                $definedParams[] = ['name' => $newParam, 'type' => ''];

            } catch (RuntimeException $exception) {
                $this->writeError($exception->getMessage());
            }

            // Self call
            $definedParams = $this->classPropertiesNamesWizardCallback($definedParams);
        }

        return $definedParams;
    }

    private function classPropertiesTypesWizardCallback($definedParams = null, $index = 0): array
    {
        if (!$definedParams) {
            $definedParams = [];
        }

        if ($index < count($definedParams)) {
            $currentParam = $definedParams[$index]['name'] ?? null;

            $this->writeLine('Define param "' . $currentParam . '" type (int, string, bool, float, other) (Default ' . self::PACKAGE_DATA_TYPE_DEFAULT . ')');
            $currentParamType = trim($this->getUserInputRequest());

            // Validate...
            if (!in_array($currentParamType, [
                self::PACKAGE_DATA_TYPE_INT,
                self::PACKAGE_DATA_TYPE_STRING,
                self::PACKAGE_DATA_TYPE_BOOL,
                self::PACKAGE_DATA_TYPE_FLOAT,
                self::PACKAGE_DATA_TYPE_OTHER
            ])) {
                $currentParamType = self::PACKAGE_DATA_TYPE_DEFAULT;
            }

            $definedParams[$index]['type'] = ($currentParamType === self::PACKAGE_DATA_TYPE_OTHER) ? '' : $currentParamType;
            $index++;
            [$definedParams, $index] = $this->classPropertiesTypesWizardCallback($definedParams, $index);
        }

        // pensar en una manera para que este metodo solo dvuelva definedParams
        return [$definedParams, $index];
    }

    private function cleanReturnIndexParam(array  $input): array
    {
        return $input[0];
    }
}