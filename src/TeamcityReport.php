<?php

declare(strict_types=1);

namespace setasign\PhpcsTeamcityReport;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Reports\Report;

/**
 * Class TeamcityReport
 */
class TeamcityReport implements Report
{
    /**
     * @var array
     */
    protected $inspectionTypes = [];

    public function generateFileReport(array $report, File $phpcsFile, bool $showSources = false, int $width = 80): bool
    {
        $warningCount = $phpcsFile->getWarningCount();
        $errorCount = $phpcsFile->getErrorCount();
        if ($warningCount === 0 && $errorCount === 0) {
            // Nothing to print.
            return false;
        }

        foreach ($report['messages'] as $line => $lineErrors) {
            foreach ($lineErrors as $colErrors) {
                foreach ($colErrors as $error) {
                    if ($phpcsFile->config->encoding !== 'utf-8') {
                        $error['source'] = \mb_convert_encoding(
                            $error['source'],
                            'utf-8',
                            $phpcsFile->config->encoding
                        );
                        $error['message'] = \mb_convert_encoding(
                            $error['message'],
                            'utf-8',
                            $phpcsFile->config->encoding
                        );
                    }

                    if (\mb_strlen($error['source'], 'utf-8') > 255) {
                        $error['source'] = \mb_substr($error['source'], 0, 255, 'utf-8');
                    }

                    if (!\array_key_exists($error['source'], $this->inspectionTypes)) {
                        $category = 'CodeSniffer';
                        if (\preg_match('~^([^.]+\.[^.]+)\.[^.]+\.[^.]+$~u', $error['source'], $matches) === 1) {
                            $category .= ' ' . $matches[1];
                        }

                        if (\mb_strlen($category, 'utf-8') > 255) {
                            $category = \mb_substr($category, 0, 255, 'utf-8');
                        }

                        $this->inspectionTypes[$error['source']] = $this->createTeamCityLine('inspectionType', [
                            'id' => $error['source'],
                            'name' => $error['source'],
                            'category' => $category,
                            'description' => 'CodeSniffer inspection',
                        ]);
                    }

                    echo $this->createTeamCityLine('inspection', [
                        'typeId' => $error['source'],
                        'file' => $report['filename'],
                        'line' => $line,
                        'message' => $error['message'],
                        'SEVERITY' => $error['type'],
                    ]);
                }
            }
        }

        return true;
    }

    public function generate(
        string $cachedData,
        int $totalFiles,
        int $totalErrors,
        int $totalWarnings,
        int $totalFixable,
        bool $showSources = false,
        int $width = 80,
        bool $interactive = false,
        bool $toScreen = true
    ) {
        foreach ($this->inspectionTypes as $inspectionType) {
            echo $inspectionType;
        }
        echo $cachedData;
    }

    /**
     * Creates a TeamCity report line
     *
     * @param string $messageName The message name
     * @param array $keyValuePairs The key=>value pairs
     * @return string The TeamCity report line
     */
    private function createTeamCityLine(string $messageName, array $keyValuePairs): string
    {
        $string = '##teamcity[' . $messageName;
        foreach ($keyValuePairs as $key => $value) {
            if (\is_string($value)) {
                $value = $this->escape($value);
            }
            $string .= ' ' . $key . '=\'' . $value . '\'';
        }
        return $string . ']' . PHP_EOL;
    }

    /**
     * Escapes the given string for TeamCity output
     *
     * @param $string string The string to escape
     * @return string The escaped string
     */
    private function escape(string $string): string
    {
        $replacements = [
            '~\n~' => '|n',
            '~\r~' => '|r',
            '~([\'\|\[\]])~' => '|$1'
        ];
        return \preg_replace(\array_keys($replacements), \array_values($replacements), $string);
    }
}
