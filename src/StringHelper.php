<?php

namespace YosypAndriyash\SfStructureGenerator;

class StringHelper {

    public const SPACE_DELIMITER = ' ';

    /**
     * @param $string
     * @param string $delimiter
     * @return mixed|string
     */
    public static function toLowerCamelCase($string, string $delimiter = self::SPACE_DELIMITER): mixed
    {
        $stringParts = explode($delimiter, $string);

        if (count($stringParts) < 1) {
            return $string;
        }

        $result = strtolower($stringParts[0]);
        unset($stringParts[0]);

        if(count($stringParts) < 1) {
            return $result;
        }

        foreach ($stringParts as $stringPart) {
            $result .= ucfirst($stringPart);
        }

        return $result;
    }

    public static function formatToLowerCamelCase($string)
    {
        $replacers = ['_', '-', ' '];
        foreach ($replacers as $replacer) {
            if (str_contains($string, $replacer)) {
                return StringHelper::toLowerCamelCase($string, $replacer);
            }
        }

        return $string;
    }
}