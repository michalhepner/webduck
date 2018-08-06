<?php

declare(strict_types = 1);

namespace Webduck\Audit;

class AuditResult
{
    const RESOLUTION_OK = 'ok';
    const RESOLUTION_WARNING = 'warning';
    const RESOLUTION_ERROR = 'error';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $resolution;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $data;

    public function __construct(string $name, string $resolution, string $message, array $data = [])
    {
        $this->name = $name;
        $this->resolution = $resolution;
        $this->message = $message;
        $this->data = $data;
    }

    public static function createOk(string $name, string $message, array $data = []): self
    {
        return new self($name, self::RESOLUTION_OK, $message, $data);
    }

    public static function createWarning(string $name, string $message, array $data = []): self
    {
        return new self($name, self::RESOLUTION_WARNING, $message, $data);
    }

    public static function createError(string $name, string $message, array $data = []): self
    {
        return new self($name, self::RESOLUTION_ERROR, $message, $data);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getResolution(): string
    {
        return $this->resolution;
    }

    public function isResolutionOk(): bool
    {
        return $this->resolution === self::RESOLUTION_OK;
    }

    public function isResolutionWarning(): bool
    {
        return $this->resolution === self::RESOLUTION_WARNING;
    }

    public function isResolutionError(): bool
    {
        return $this->resolution === self::RESOLUTION_ERROR;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getData(): array
    {
        return $this->data;
    }
}
