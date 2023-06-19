<?php

namespace YosypAndriyash\SfStructureGenerator\Trait;

use YosypAndriyash\SfStructureGenerator\PathHelper;
use RuntimeException;

trait FileTrait {

    // Centralize all dirs-creation calls here to allow add or remove output comments (or logs)
    private function createDir($dir): void
    {
        // With this class method we can ask user if he want to overwrite directory or something else...
        if (PathHelper::dirExists($dir)) {
            // $this->writeComment($dir . ' directory already exists');
        } else {
            PathHelper::createDir($dir);
            $this->writeLine();
            $this->writeSuccess($dir . ' path created');
        }
    }

    private function createFile($fileName, $content = '',  $forceReplace = false): bool|string
    {
        try {

            // Create path for file if not exists
            $pathInfo = pathinfo($fileName);
            $path = $pathInfo['dirname'] ?? null;

            if (!$path) {
                return false;
            }

            $this->createDir($path);

            if (!file_exists($fileName)) {
                return $this->touchFile($fileName, $content);
            }

            if (file_exists($fileName) && $forceReplace === false) {
                $shortFile = '...' . PathHelper::getLastDirFromPath($fileName, 7);
                $this->writeError('File ' . $shortFile . ' already exists, do you want to replace it? (This will erase current file content)');
                $this->writeLine();
                $this->writeLine('Select option:');
                $this->writeLine('y: Yes, replace it');
                $this->writeLine('n: No, keep this file and continue');

                $createFile = (string) strtolower($this->getUserInputRequest());

                if (!in_array($createFile, ['y', 'n'])) {
                    throw new RuntimeException();
                }

                if ($createFile === 'y') {
                    return $this->createFile($fileName, $content, true);
                }
            }

            if (file_exists($fileName) && $forceReplace === true) {
                return $this->touchFile($fileName, $content);
            }

        } catch (RuntimeException) {
            $this->writeError('Invalid response:');
            $this->createFile($fileName, $content);
        }

        return $content;
    }

    private function touchFile($fileName, $content = ''): bool
    {
        $operation = file_exists($fileName) ? ' overwritten' : ' created';

        try {
            $fileStream = fopen($fileName, 'w+');

            fwrite($fileStream, $content);
            fclose($fileStream);

            $this->writeSuccess('file ...' . PathHelper::getLastDirFromPath($fileName, 5) . $operation);
        } catch (RuntimeException $exception) {

            $this->writeError('file ...' . PathHelper::getLastDirFromPath($fileName, 5) . ' writing error');
        }


        return is_file($fileName);
    }

    private function getTemplateContent($templatePath): string
    {
        return file_get_contents($templatePath);
    }
}