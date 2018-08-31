<?php

declare(strict_types = 1);

namespace Webduck\Bus;

abstract class AbstractCommand
{
    /**
     * @var string
     */
    protected $uuid;

    public function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getName(): string
    {
        return static::getNameStatic();
    }

    public static function getNameStatic(): string
    {
        return preg_replace('/Command$/', '',
            preg_replace('/^Webduck\\\Bus\\\/', '', static::class)
        );
    }
}
