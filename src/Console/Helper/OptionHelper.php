<?php

declare(strict_types = 1);

namespace Webduck\Console\Helper;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

class OptionHelper
{
    const OPTION_AUDIT_VIOLATIONS = 'audit-violations';
    const OPTION_AUDIT_RESOURCE_LOAD = 'audit-resource-load';
    const OPTION_USERNAME = 'username';
    const OPTION_PASSWORD = 'password';
    const OPTION_OUTPUT = 'output';
    const OPTION_SAVE_HTML = 'save-html';
    const OPTION_SAVE_JSON = 'save-json';
    const OPTION_SAVE_TEXT = 'save-text';
    const OPTION_SAVE_SCREENSHOT = 'screenshot';

    public static function addAllOptions(Command $command)
    {
        $command->addOption(self::OPTION_AUDIT_VIOLATIONS, null, InputOption::VALUE_NONE);
        $command->addOption(self::OPTION_AUDIT_RESOURCE_LOAD, null, InputOption::VALUE_NONE);
        $command->addOption(self::OPTION_USERNAME, 'u', InputOption::VALUE_REQUIRED);
        $command->addOption(self::OPTION_PASSWORD, 'p', InputOption::VALUE_REQUIRED);
        $command->addOption(self::OPTION_OUTPUT, null, InputOption::VALUE_REQUIRED, 'Defines what type of output is expected. Possible values are \'text\' or \'html\'.', ReportOutputHelper::FORMAT_TEXT);
        $command->addOption(self::OPTION_SAVE_HTML, null, InputOption::VALUE_REQUIRED, 'Defines where to save the report in HTML format.');
        $command->addOption(self::OPTION_SAVE_JSON, null, InputOption::VALUE_REQUIRED, 'Defines where to save the report in JSON format.');
        $command->addOption(self::OPTION_SAVE_TEXT, null, InputOption::VALUE_REQUIRED, 'Defines where to save the report in text format.');
        $command->addOption(self::OPTION_SAVE_SCREENSHOT, null, InputOption::VALUE_NONE, 'Should screenshots be generated in the report (works only for HTML and JSON output)?');
    }
}
