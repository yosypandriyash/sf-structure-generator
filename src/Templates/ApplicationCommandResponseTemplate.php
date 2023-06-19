<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

class ApplicationCommandResponseTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();

        $commandResponseParameters = $this->commandInputContainer->getInput('packageDomainEntityAttributes');

        $replaceInTemplate['application_command_response_construct_class_parameters'] = $this->generateClassConstructParams(
            $commandResponseParameters
        );

        $replaceInTemplate['application_command_response_parameters_getters'] = $this->generateClassAttributesGetters(
            $commandResponseParameters
        );

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }
}