<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

class UriFilter
{
    /**
     * @var string
     */
    protected $regex;

    public function __construct($regex)
    {
        $this->regex = $regex;
    }

    public function getRegex(): string
    {
        return $this->regex;
    }

    public function __toString()
    {
        return $this->regex;
    }
}
