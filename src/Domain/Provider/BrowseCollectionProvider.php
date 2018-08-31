<?php

declare(strict_types = 1);

namespace Webduck\Domain\Provider;

use InvalidArgumentException;
use NunuSoftware\ParallelProcess\AsyncRunner;
use NunuSoftware\ParallelProcess\EmitterRunner;
use RuntimeException;
use Symfony\Component\Process\Process;
use Webduck\Domain\Collection\BrowseCollection;
use Webduck\Domain\Collection\UriCollection;
use Webduck\Domain\Model\Uri;

class BrowseCollectionProvider
{
    /**
     * @var string
     */
    protected $bin;

    /**
     * @var int
     */
    protected $poolSize = 5;

    public function __construct(string $bin, ?int $poolSize = null)
    {
        $this->bin = $bin;
        $poolSize !== null && $this->poolSize = $poolSize;
    }

    /**
     * @param string[]|UriCollection $uris
     * @param array                  $options
     *
     * @return BrowseCollection
     */
    public function provide($uris, array $options = []): BrowseCollection
    {
        if (is_array($uris)) {
            $uris = new UriCollection($uris);
        } elseif (!$uris instanceof UriCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be an array or an instance of %s',
                __METHOD__,
                UriCollection::class
            ));
        }

        $processes = $uris->map(function (Uri $uri) use ($options) {
            return new Process($this->getProcessCommandLine($uri, $options), null, null, null, 0.0);
        });

        $runner = new AsyncRunner($processes);
        $runner->setPoolSize($this->poolSize);
        $processes = $runner->run();

        return BrowseCollection::merge(...(array_map(
            function (Process $process) {
                return $this->getBrowseCollection($process);
            },
            $processes
        )));
    }

    public function emit($uris, callable $onBrowse, array $options = []): void
    {
        if (is_array($uris)) {
            $uris = new UriCollection($uris);
        } elseif (!$uris instanceof UriCollection) {
            throw new InvalidArgumentException(sprintf(
                'Argument 0 passed to %s must be an array or an instance of %s',
                __METHOD__,
                UriCollection::class
            ));
        }

        $processes = $uris->map(function (Uri $uri) use ($options) {
            return new Process($this->getProcessCommandLine($uri, $options), null, null, null, 0.0);
        });

        $runner = new EmitterRunner($processes);
        $runner->setPoolSize($this->poolSize);
        $runner->run(function (Process $process) use ($onBrowse) {
            $onBrowse($this->getBrowseCollection($process));
        });
    }

    protected function getProcessCommandLine(Uri $uri, array $options): string
    {
        return implode(' ', array_filter([
            $this->bin,
            'provide',
            array_key_exists('username', $options) && !empty($options['username']) ? '--username='.escapeshellarg($options['username']) : null,
            array_key_exists('password', $options) && !empty($options['password'])  ? '--password='.escapeshellarg($options['password']) : null,
            escapeshellarg($uri->__toString())
        ]));
    }

    protected function getBrowseCollection(Process $process): BrowseCollection
    {
        if (!$process->isSuccessful()) {
            throw new RuntimeException(sprintf(
                'Provider process exited with non-zero code %d',
                $process->getExitCode()
            ));
        }

        $providerData = json_decode($process->getOutput(), true);

        if ($providerData === false || $providerData === null) {
            throw new RuntimeException(sprintf(
                'Failed to parse provider process output for command "%s"',
                $process->getCommandLine()
            ));
        }

        return BrowseCollection::createFromArray($providerData);
    }

    public function getPoolSize(): int
    {
        return $this->poolSize;
    }

    public function setPoolSize(int $poolSize): self
    {
        $this->poolSize = $poolSize;

        return $this;
    }
}
