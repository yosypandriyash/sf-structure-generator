<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

class ApplicationReadResponseTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();

        $replaceInTemplate['application_read_response_constructor_args'] = $this->generateClassConstructParams(
            $this->commandInputContainer->getInput('packageDomainEntityAttributes')
        );

        $replaceInTemplate['application_read_response_getters_body'] = $this->generateClassAttributesGetters(
            $this->commandInputContainer->getInput('packageDomainEntityAttributes')
        );

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }
}