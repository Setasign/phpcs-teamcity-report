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

    public function generateFileReport($report, File $phpcsFile, $showSources = false, $width = 80)
    {
        $filename = $phpcsFile->getFilename();
        $warningCount = $phpcsFile->getWarningCount();
        $errorCount = $phpcsFile->getErrorCount();
        if ($warningCount === 0 && $errorCount === 0) {
            // Nothing to print.
            return false;
        }

        foreach ($report['messages'] as $line => $lineErrors) {
            foreach ($lineErrors as $column => $colErrors) {
                foreach ($colErrors as $error) {
                    if (!\array_key_exists($error['source'], $this->inspectionTypes)) {
                        $this->inspectionTypes[$error['source']] = $this->createTeamCityLine('inspectionType', [
                            'id' => $error['source'],
                            'name' => $error['source'],
                            'category' => 'CodeSniffer',
                            'description' => '',
                        ]);
                    }

                    if ($phpcsFile->config->encoding !== 'utf-8') {
                        $error['message'] = \mb_convert_encoding(
                            $error['message'],
                            'utf-8',
                            $phpcsFile->config->encoding
                        );
                    }

                    echo $this->createTeamCityLine('inspection', [
                        'typeId' => $error['source'],
                        'file' => $filename,
                        'line' => $line,
                        'message' => $error['message'],
                        'SEVERITY' => $error['type'],
                        'fixable' => $error['fixable']
                    ]);
                }
            }
        }

        return true;
    }

    public function generate(
        $cachedData,
        $totalFiles,
        $totalErrors,
        $totalWarnings,
        $totalFixable,
        $showSources = false,
        $width = 80,
        $interactive = false,
        $toScreen = true
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
     * @param mixed[] $keyValuePairs The key=>value pairs
     * @return string The TeamCity report line
     */
    private function createTeamCityLine($messageName, array $keyValuePairs): string
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
