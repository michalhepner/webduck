<?php

declare(strict_types = 1);

namespace Webduck\Bus\Exception;

use Exception;
use Throwable;
use Webduck\Bus\CommandInterface;

class CommandValidationException extends Exception
{
    /**
     * @var CommandInterface
     */
    protected $command;

    public function __construct(CommandInterface $command, string $message = '', int $code = 0, Throwable $previous = null)
    {
        $this->command = $command;

        parent::__construct($message, $code, $previous);
    }

    public function getCommand(): CommandInterface
    {
        return $this->command;
    }
}
