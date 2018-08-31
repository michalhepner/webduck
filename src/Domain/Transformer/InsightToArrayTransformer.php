<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Webduck\Domain\Model\Insight;

class InsightToArrayTransformer
{
    public function transform(Insight $insight): array
    {
        return [
            'name' => $insight->getName(),
            'message' => $insight->getMessage(),
            'mark' => $insight->getMark(),
            'data' => $insight->getData(),
        ];
    }
}
