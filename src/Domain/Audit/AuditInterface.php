<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Serializable;
use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;

interface AuditInterface extends Serializable
{
    public function getName(): string;
    public function execute(Browse $urlData): InsightCollection;
}
