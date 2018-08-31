<?php

declare(strict_types = 1);

namespace Webduck\Bus\Command;

use InvalidArgumentException;
use Ramsey\Uuid\Uuid;
use Webduck\Bus\AbstractCommand;
use Webduck\Domain\Audit\AuditCollection;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\UriFilter;

class AuditPageCommand extends AbstractCommand
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
}
