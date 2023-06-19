<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

use YosypAndriyash\SfStructureGenerator\CreateHexagonalStructureCommand;

class DomainEntityServiceTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $dependencyMatch = 'DomainEntityTemplate';
        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();

        if ($this->commandInputContainer->existsInput('packageApplicationActionReadResponseIsCollection')) {
            $dependencyMatch = (
                $this->commandInputContainer->getInput('packageApplicationActionReadResponseIsCollection') === 1
            ) ? 'DomainEntityCollectionTemplate' : 'DomainEntityTemplate';
        }

        /** @var BasePhpClassTemplate $domainEntityDependency */
        $domainEntityDependency = $this->dependencies[$dependencyMatch] ?? null;
        $domainEntityDependencyUseStatement = $domainEntityDependency->getClassNamespace() . '\\' . $domainEntityDependency->getClassName();

        /** @var BasePhpClassTemplate $domainRepositoryDependency */
        $domainRepositoryDependency = $this->dependencies['DomainEntityRepositoryTemplate'] ?? null;
        $domainRepositoryDependencyUseStatement =
            $domainRepositoryDependency->getClassNamespace() . '\\' . $domainRepositoryDependency->getClassName();

        $replaceInTemplate['use_definitions'] = $this->generateUseDefinitions(
            $this->useDefinitions + [$domainRepositoryDependencyUseStatement, $domainEntityDependencyUseStatement]
        );

        $replaceInTemplate['domain_service_class_construct_args'] = $this->generateClassConstructParams([
            ['type' => $domainRepositoryDependency->getClassName(), 'name' => lcfirst($domainRepositoryDependency->getClassName())]
        ]);

        $responseDefinitionType = $this->commandInputContainer->getInput('packageApplicationActionType');
        $responseDefinitionType =
            ($responseDefinitionType === 2 || $responseDefinitionType === 3)
                ? 'packageApplicationActionCommandCustomArguments'
                : 'packageApplicationActionReadQueryParams';

        $replaceInTemplate['domain_service_execute_args'] = $this->generateMethodConstructParams(
            $this->commandInputContainer->getInput($responseDefinitionType)
        );

        $replaceInTemplate['domain_service_execute_response_type'] = ucfirst($domainEntityDependency->getClassName());

        $repositoryMethodArguments = $this->generateMethodArguments(
            $this->commandInputContainer->getInput($responseDefinitionType)
        );

        $replaceInTemplate['domain_service_execute_body'] = $this->generateDomainServiceExecuteMethodBody(
            $this->commandInputContainer->getInput(CreateHexagonalStructureCommand::IC_KEY_APP_USE_CASE_NAME),
            lcfirst($domainRepositoryDependency->getClassName()),
            $repositoryMethodArguments
        );

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }

    private function generateMethodArguments($array = []): array
    {
        $output = [];
        foreach ($array as $item) {
            $output[] = '$' . $item['name'];
        }

        return $output;
    }

    private function generateDomainServiceExecuteMethodBody(
        $repositoryMethodName,
        $repositoryMethodResponseType,
        $repositoryMethodArguments
    ): string
    {
        $body =
            PHP_EOL .
            $this->drawTabSpace(2) . '// domain service logic here... ' .
            PHP_EOL .
            PHP_EOL;

        return $body . $this->drawTabSpace(2) . 'return $this->' . $repositoryMethodResponseType .
            '->' . $repositoryMethodName . '(' . implode(', ', $repositoryMethodArguments) . ');';
    }
}