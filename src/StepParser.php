<?php

namespace YosypAndriyash\SfStructureGenerator;

class StepParser {

    public const OUTPUT_TYPE_INTEGER = 'int';
    public const OUTPUT_TYPE_STRING = 'string';
    public const OUTPUT_TYPE_BOOLEAN = 'boolean';
    public const OUTPUT_TYPE_FLOAT = 'float';

    public const REGEX_VALIDATION_TYPES = [
        'className' => '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]/'
    ];

    private string|array $executeConditions;
    private string $stepTitle;
    private string|array $stepDescription;
    private string $stepExtendedDescription;
    private string $stepOutputVarType;
    private string $stepOutputVarName;
    private ?array $stepOutputAllowedOptions;
    private string $stepOutputVarRegex;
    private string|array $stepOnInputEndCallback;
    private ?array $stepOnBegin;
    private ?array $stepOnEnd;
    private ?array $stepOnDebug;

    public function __construct(array $stepData = [])
    {
        $this->executeConditions = $stepData['executeConditions'] ?? '';
        $this->stepTitle = $stepData['stepTitle'] ?? '';
        $this->stepDescription = $stepData['stepDescription'] ?? '';
        $this->stepExtendedDescription = $stepData['stepExtendedDescription'] ?? '';
        $this->stepOutputVarType = $stepData['stepOutputVarType'] ?? '';
        $this->stepOutputVarName = $stepData['stepOutputVarName'] ?? '';
        $this->stepOutputAllowedOptions = $stepData['stepOutputAllowedOptions'] ?? [];
        $this->stepOutputVarRegex = $stepData['stepOutputVarRegex'] ?? '';
        $this->stepOnInputEndCallback = $stepData['stepOnInputEndCallback'] ?? '';
        $this->stepOnBegin = $stepData['stepOnBegin'] ?? [];
        $this->stepOnEnd = $stepData['stepOnEnd'] ?? [];
        $this->stepOnDebug = $stepData['stepOnDebug'] ?? [];
    }


    /**
     * @return array|string
     */
    public function getExecuteConditions(): array|string
    {
        return $this->executeConditions;
    }

    /**
     * @return string
     */
    public function getStepTitle(): string
    {
        return $this->stepTitle;
    }

    /**
     * @return string
     */
    public function getStepDescription(): string
    {
        if (is_array($this->stepDescription)) {
            $output = "";
            foreach ($this->stepDescription as $line) {
                $output .= $line .PHP_EOL;
            }

            return $output;
        }

        return $this->stepDescription;
    }

    /**
     * @return string
     */
    public function getStepExtendedDescription(): string
    {
        return $this->stepExtendedDescription;
    }

    /**
     * @return string
     */
    public function getStepOutputVarType(): string
    {
        return $this->stepOutputVarType;
    }

    /**
     * @return string
     */
    public function getStepOutputVarName(): string
    {
        return $this->stepOutputVarName;
    }

    /**
     * @return array|null
     */
    public function getStepOutputAllowedOptions(): ?array
    {
        return $this->stepOutputAllowedOptions;
    }

    /**
     * @return string|null
     */
    public function getStepOutputVarRegex(): ?string
    {
        return $this->stepOutputVarRegex;
    }

    /**
     * @return string|array
     */
    public function getStepOnInputEndCallback(): string|array
    {
        return $this->stepOnInputEndCallback;
    }

    /**
     * @return array|null
     */
    public function getStepOnBegin(): ?array
    {
        return $this->stepOnBegin;
    }

    /**
     * @return array|null
     */
    public function getStepOnEnd(): ?array
    {
        return $this->stepOnEnd;
    }

    /**
     * @return array|null
     */
    public function getStepOnDebug(): ?array
    {
        return $this->stepOnDebug;
    }

    /**
     * @return mixed
     */
    public function getStepOnDebugDefaultValue(): mixed
    {
        if (isset($this->stepOnDebug['value']) && !empty($this->stepOnDebug['value'])) {
            return $this->stepOnDebug['value'];
        };

        return null;
    }
}