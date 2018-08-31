<?php

declare(strict_types = 1);

namespace Webduck\Bus\Command;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Webduck\Bus\AbstractCommand;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Collection\StringCollection;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Collection\UriFilterCollection;
use Webduck\Domain\Model\UriFilter;

class AuditSiteCommand extends AbstractCommand
{
    /**
     * @var UriCollection
     */
    protected $uris;

    /**
     * @var AuditCollection
     */
    protected $audits;

    /**
     * @var StringCollection
     */
    protected $allowedHosts;

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
     * @param string                 $uuid
     * @param string[]|UriCollection $uris
     * @param AuditCollection        $audits
     */
    public function __construct(string $uuid, $uris, AuditCollection $audits)
    {
        parent::__construct($uuid);

        $this->setUris($uris);
        $this->audits = $audits;
        $this->uriFilters = new UriFilterCollection();
    }

    /**
     * @param string[]|UriCollection $uris
     * @param AuditCollection $audits
     *
     * @return self
     */
    public static function create($uris, AuditCollection $audits): self
    {
        return new static(Uuid::uuid4()->toString(), $uris, $audits);
    }

    /**
     * @return UriCollection
     */
    public function getUris(): UriCollection
    {
        return $this->uris;
    }

    /**
     * @param string[]|UriCollection $uris
     *
     * @return self
     */
    public function setUris($uris): self
    {
        if (is_array($uris)) {
            $uris = new UriCollection($uris);
        } elseif (!$uris instanceof UriCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 1 passed to %s must be either an array or instance of %s',
                __METHOD__,
                UriCollection::class
            ));
        }

        $this->uris = $uris;

        return $this;
    }

    /**
     * @param string|UriFilter $uri
     *
     * @return self
     */
    public function addUri($uri): self
    {
        $this->uris->add($uri);

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

    public function getAllowedHosts(): StringCollection
    {
        return $this->allowedHosts;
    }

    public function setAllowedHosts($allowedHosts): self
    {
        if (is_array($allowedHosts)) {
            $allowedHosts = new StringCollection($allowedHosts);
        } elseif (!$allowedHosts instanceof StringCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be an array or instance of %s',
                __METHOD__,
                StringCollection::class
            ));
        }

        $this->allowedHosts = $allowedHosts;

        return $this;
    }

    public function addAllowedHost(string $allowedHost): self
    {
        $this->allowedHosts->add($allowedHost);

        return $this;
    }

    public function addAllowedHosts(array $allowedHosts): self
    {
        foreach ($allowedHosts as $allowedHost) {
            $this->addAllowedHost($allowedHost);
        }

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
}
