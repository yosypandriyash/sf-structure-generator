<?php

namespace YosypAndriyash\SfStructureGenerator\Templates;

class DomainEntityCollectionTemplate extends BasePhpClassTemplate
{
    public function generateParsedTemplateOutput(): string|null
    {
        $replaceInTemplate = $this->beforeGenerateParsedTemplateOutput();

        return $this->parseTemplatePlaceholders($replaceInTemplate);
    }
}