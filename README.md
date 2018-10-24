# phpcs-teamcity-report
A phpcs reporter for teamcity using the [inspection service messages](https://confluence.jetbrains.com/display/TCD18/Build+Script+Interaction+with+TeamCity#BuildScriptInteractionwithTeamCity-ReportingInspections)

# Usage
Note that you have to install the report script the same way as PHP_CodeSniffer.
At the moment we only support composer.

So use:
```
composer global require "setasign/phpcs-teamcity-report=*"
```

Or:
```
{
    "require-dev": {
        "squizlabs/php_codesniffer": "^3.0",
        "setasign/phpcs-teamcity-report": "^1.0"
    }
}
```



To use the report just add the following parameter to your code sniffer call:

```
--report=\setasign\PhpcsTeamcityReport\TeamcityReport
```

# Drawbacks
At the moment all inspections are in the category "CodeSniffer" without a description or a 'readable' name.
Due to a missing easy way to extract these from CodeSniffer.