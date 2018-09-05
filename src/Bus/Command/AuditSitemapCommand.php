<?php

declare(strict_types = 1);

namespace Webduck\Bus\Command;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Webduck\Bus\AbstractCommand;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Collection\UriFilterCollection;
use Webduck\Domain\Model\Uri;
use Webduck\Domain\Model\UriFilter;

class AuditSitemapCommand extends AbstractCommand
{
    const NAME = 'audit.sitemap';

    /**
     * @var Uri
     */
    protected $uri;

    /**
     * @var AuditCollection
     */
    protected $audits;

    /**
     * @var UriFilterCollection
     */
    protected $uriFilters;

    /**
     * @var string|null
     */
    protected $username;

    /**
     * @var string|null
     */
    protected $password;

    /**
     * @var bool
     */
    protected $shouldGenerateScreenshot = false;

    /**
     * @param string          $uuid
     * @param string|Uri      $uri
     * @param AuditCollection $audits
     */
    public function __construct(string $uuid, $uri, AuditCollection $audits)
    {
        parent::__construct($uuid);

        $this->setUri($uri);
        $this->audits = $audits;
        $this->uriFilters = new UriFilterCollection();
    }

    /**
     * @param string|Uri $uri
     * @param AuditCollection $audits
     *
     * @return self
     */
    public static function create($uri, AuditCollection $audits): self
    {
        return new static(Uuid::uuid4()->toString(), $uri, $audits);
    }

    public function getUri(): Uri
    {
        return $this->uri;
    }

    /**
     * @param string|Uri $uri
     *
     * @return self
     */
    public function setUri($uri): self
    {
        if (is_string($uri)) {
            $uri = Uri::createFromString($uri);
        } elseif (!$uri instanceof Uri) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be either an array or instance of %s',
                __METHOD__,
                Uri::class
            ));
        }

        $this->uri = $uri;

        return $this;
    }

    public function getAudits(): AuditCollection
    {
        return $this->audits;
    }

    public function setAudits(AuditCollection $audits): self
    {
        $this->audits = $audits;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(?string $username): self
    {
        $this->username = empty($username) ? null : $username;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = empty($password) ? null : $password;

        return $this;
    }

    public function hasUserInfo(): bool
    {
        return $this->username && $this->password;
    }

    public function getUriFilters(): UriFilterCollection
    {
        return $this->uriFilters;
    }

    /**
     * @param string[]|UriFilterCollection $uriFilters
     *
     * @return self
     */
    public function setUriFilters($uriFilters): self
    {
        if (is_array($uriFilters)) {
            $uriFilters = new UriFilterCollection($uriFilters);
        } elseif (!$uriFilters instanceof UriFilterCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be either an array or instance of %s',
                __METHOD__,
                UriFilterCollection::class
            ));
        }

        $this->uriFilters = $uriFilters;

        return $this;
    }

    /**
     * @param string|UriFilter $uriFilter
     *
     * @return self
     */
    public function addUriFilter($uriFilter): self
    {
        $this->uriFilters->add($uriFilter);

        return $this;
    }

    public function getShouldGenerateScreenshot(): bool
    {
        return $this->shouldGenerateScreenshot;
    }

    public function setShouldGenerateScreenshot(bool $shouldGenerateScreenshot): self
    {
        $this->shouldGenerateScreenshot = $shouldGenerateScreenshot;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'uuid' => $this->uuid,
            'uri' => $this->uri->__toString(),
            'audits' => serialize($this->audits),
            'uri_filters' => $this->uriFilters->toArray(),
            'username' => $this->username,
            'password' => $this->password,
            'should_generate_screenshot' => $this->shouldGenerateScreenshot,
        ];
    }

    public static function fromArray(array $arr): self
    {
        $obj = new static($arr['uuid'], Uri::createFromString($arr['uri']), unserialize($arr['audits']));

        $obj->setUsername($arr['username']);
        $obj->setPassword($arr['password']);
        $obj->setUriFilters(UriFilterCollection::fromArray($arr['uri_filters']));
        $obj->setShouldGenerateScreenshot($arr['should_generate_screenshot']);

        return $obj;
    }
}
