<?php

declare(strict_types = 1);

namespace Webduck\Provider;

class DataBundle
{
    /**
     * @var UrlDataCollection
     */
    protected $urlDataCollection;

    public function __construct(UrlDataCollection $urlDataCollection)
    {
        $this->urlDataCollection = $urlDataCollection;
    }

    public function getUrlDataCollection(): UrlDataCollection
    {
        return $this->urlDataCollection;
    }
}
