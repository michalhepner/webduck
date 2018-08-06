<?php

declare(strict_types = 1);

namespace Webduck\Audit;

use Webduck\Provider\UrlData;

interface AuditInterface
{
    public function getName(): string;
    public function execute(UrlData $urlData): AuditResultCollection;
}
