<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

class ApplicationCommandCommandTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();

        $replaceInTemplate['application_command_command_construct_class_parameters'] = $this->generateClassConstructParams(
            $this->commandInputContainer->getInput('packageApplicationActionCommandCustomArguments')
        );

        $replaceInTemplate['application_command_command_class_parameters_getters'] = $this->generateClassAttributesGetters(
            $this->commandInputContainer->getInput('packageApplicationActionCommandCustomArguments')
        );

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }
}