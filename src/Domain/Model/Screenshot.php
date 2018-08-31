<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

class Screenshot
{
    /**
     * @var string
     */
    protected $mediaType;

    /**
     * @var bool
     */
    protected $isBase64;

    /**
     * @var string
     */
    protected $data;

    public function __construct(string $mediaType, bool $isBase64, string $data)
    {
        $this->mediaType = $mediaType;
        $this->isBase64 = $isBase64;
        $this->data = $data;
    }

    public function getMediaType(): string
    {
        return $this->mediaType;
    }

    public function setMediaType(string $mediaType): self
    {
        $this->mediaType = $mediaType;

        return $this;
    }

    public function getIsBase64(): bool
    {
        return $this->isBase64;
    }

    public function setIsBase64(bool $isBase64): self
    {
        $this->isBase64 = $isBase64;

        return $this;
    }

    public function getData(): string
    {
        return $this->data;
    }

    public function setData(string $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Returns a string compatible with RFC 2397.
     *
     * @return string
     */
    public function getDataUri(): string
    {
        return sprintf(
            'data:%s,%s',
            $this->mediaType.($this->isBase64 ? ';base64' : ''),
            $this->data
        );
    }
}
