<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

use YosypAndriyash\SfStructureGenerator\CreateHexagonalStructureCommand;

class DomainEntityRepositoryTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $dependencyMatch = 'DomainEntityTemplate';

        if ($this->commandInputContainer->existsInput('packageApplicationActionReadResponseIsCollection')) {
            $dependencyMatch = (
                $this->commandInputContainer->getInput('packageApplicationActionReadResponseIsCollection') === 1
            ) ? 'DomainEntityCollectionTemplate' : 'DomainEntityTemplate';
        }

        /** @var BasePhpClassTemplate $domainEntityDependency */
        $domainEntityDependency = $this->dependencies[$dependencyMatch] ?? null;
        $domainEntityDependencyUseStatement = $domainEntityDependency->getClassNamespace() . '\\' . $domainEntityDependency->getClassName();

        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();
        $replaceInTemplate['use_definitions'] = $this->generateUseDefinitions(
            $this->useDefinitions + [$domainEntityDependencyUseStatement]
        );

        $responseDefinitionType = $this->commandInputContainer->getInput('packageApplicationActionType');
        $responseDefinitionType =
            ($responseDefinitionType === 2 || $responseDefinitionType === 3)
                ? 'packageApplicationActionCommandCustomArguments'
                : 'packageApplicationActionReadQueryParams';

        $replaceInTemplate['domain_entity_repository_methods_body'] = $this->generateEntityRepositoryMethods(
            [
                [
                    'action' => $this->commandInputContainer->getInput(CreateHexagonalStructureCommand::IC_KEY_APP_USE_CASE_NAME),
                    'arguments' => $this->commandInputContainer->getInput($responseDefinitionType),
                    'outputType' => $domainEntityDependency->getClassName()
                ]
            ]
        );

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }

    private function generateEntityRepositoryMethods(array $methods = [], string $output = ''): string
    {
        foreach ($methods as $method) {
            $methodArguments = [];
            foreach ($method['arguments'] as $argument) {
                $methodArguments[] = $argument['type'] . ' $' . $argument['name'];
            }

            $output .= $this->drawTabSpace(1) . 'public function ' . $method['action']
                . '(' . implode(', ', $methodArguments) . '): ' . $method['outputType'] . ';' . PHP_EOL;
            $output .= PHP_EOL;
        }

        return $output;
    }
}