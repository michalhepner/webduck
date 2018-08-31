<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

use InvalidArgumentException;
use Webduck\Domain\Collection\InsightCollection;

class ReportPage
{
    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var InsightCollection
     */
    protected $insights;

    /**
     * @var Screenshot|null
     */
    protected $screenshot;

    /**
     * @param string|Uri                  $uri
     * @param InsightCollection|Insight[] $insights
     * @param null|Screenshot             $screenshot
     */
    public function __construct($uri, $insights, ?Screenshot $screenshot)
    {
        $this->setUri($uri);
        $this->setInsights($insights);
        $this->screenshot = $screenshot;
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * @param string|Uri $uri
     *
     * @return ReportPage
     */
    public function setUri($uri): self
    {
        if (is_string($uri)) {
            $uri = Uri::createFromString($uri);
        } elseif (!$uri instanceof Uri) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be a string or instance of %s',
                __METHOD__,
                Uri::class
            ));
        }

        $this->uri = $uri;

        return $this;
    }

    public function getInsights(): InsightCollection
    {
        return $this->insights;
    }

    /**
     * @param Insight[]|InsightCollection $insights
     * @return ReportPage
     */
    public function setInsights($insights): self
    {
        if (is_array($insights)) {
            $insights = new InsightCollection($insights);
        } elseif (!$insights instanceof InsightCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be a string or instance of %s',
                __METHOD__,
                InsightCollection::class
            ));
        }

        $this->insights = $insights;

        return $this;
    }

    public function getScreenshot(): ?Screenshot
    {
        return $this->screenshot;
    }

    public function setScreenshot(?Screenshot $screenshot): self
    {
        $this->screenshot = $screenshot;

        return $this;
    }
}
