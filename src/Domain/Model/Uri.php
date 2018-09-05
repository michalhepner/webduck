<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

use JsonSerializable;
use League\Uri\Uri as LeagueUri;

class Uri extends LeagueUri implements JsonSerializable
{
    public function jsonSerialize()
    {
        return $this->__toString();
    }

    public static function jsonUnserialize(string $json): self
    {
        return self::createFromString($json);
    }
}
