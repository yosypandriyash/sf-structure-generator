<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

class ApplicationReadQueryTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();

        $replaceInTemplate['application_read_query_construct_args'] = $this->generateClassConstructParams(
            $this->commandInputContainer->getInput('packageApplicationActionReadQueryParams')
        );

        $replaceInTemplate['application_read_query_getters_body'] = $this->generateClassAttributesGetters(
            $this->commandInputContainer->getInput('packageApplicationActionReadQueryParams')
        );

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }
}