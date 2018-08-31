<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

class Insight
{
    const MARK_OK = 'ok';
    const MARK_WARNING = 'warning';
    const MARK_ERROR = 'error';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $mark;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var array
     */
    protected $data;

    public function __construct(string $name, string $mark, string $message, array $data = [])
    {
        $this->name = $name;
        $this->mark = $mark;
        $this->message = $message;
        $this->data = $data;
    }

    public static function createOk(string $name, string $message, array $data = []): self
    {
        return new self($name, self::MARK_OK, $message, $data);
    }

    public static function createWarning(string $name, string $message, array $data = []): self
    {
        return new self($name, self::MARK_WARNING, $message, $data);
    }

    public static function createError(string $name, string $message, array $data = []): self
    {
        return new self($name, self::MARK_ERROR, $message, $data);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getMark(): string
    {
        return $this->mark;
    }

    public function isMarkOk(): bool
    {
        return $this->mark === self::MARK_OK;
    }

    public function isMarkWarning(): bool
    {
        return $this->mark === self::MARK_WARNING;
    }

    public function isMarkError(): bool
    {
        return $this->mark === self::MARK_ERROR;
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
