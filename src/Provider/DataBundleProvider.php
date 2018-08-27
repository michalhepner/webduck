<?php

declare(strict_types = 1);

namespace Webduck\Provider;

use NunuSoftware\ParallelProcess\AsyncRunner;
use NunuSoftware\ParallelProcess\EmitterRunner;
use RuntimeException;
use Symfony\Component\Process\Process;

class DataBundleProvider
{
    /**
     * @var string
     */
    protected $bin;

    /**
     * @var string[]
     */
    protected $urls;

    /**
     * @var int
     */
    protected $poolSize = 5;

    /**
     * @var string|null
     */
    protected $user;

    /**
     * @var string|null
     */
    protected $password;

    public function __construct(string $bin, array $urls, ?int $poolSize = null)
    {
        $this->bin = $bin;
        $this->urls = $urls;
        $poolSize !== null && $this->poolSize = $poolSize;
    }

    public function provide(): DataBundle
    {
        $processes = [];
        foreach ($this->urls as $url) {
            $process = new Process($this->getProcessCommandLine($url));
            $process->setTimeout(0);

            $processes[$url] = $process;
        }

        $runner = new AsyncRunner($processes);
        $runner->setPoolSize($this->poolSize);
        $processes = $runner->run();

        $urlDataCollection = new UrlDataCollection();
        foreach ($processes as $url => $process) {
            $urlDataCollection->add($this->getDataBundle($process)->getUrlDataCollection()->first());
        }

        return new DataBundle($urlDataCollection);
    }

    public function emit(callable $onDataBundle): void
    {
        $processes = [];
        foreach ($this->urls as $url) {
            $process = new Process($this->getProcessCommandLine($url));
            $process->setTimeout(0);

            $processes[$url] = $process;
        }

        $runner = new EmitterRunner($processes);
        $runner->setPoolSize($this->poolSize);
        $runner->run(function (Process $process) use ($onDataBundle) {
            $onDataBundle($this->getDataBundle($process));
        });
    }

    protected function getProcessCommandLine(string $url): string
    {
        return implode(' ', array_filter([
            $this->bin,
            'provide',
            $this->user ? '--username='.escapeshellarg($this->user) : null,
            $this->password ? '--password='.escapeshellarg($this->password) : null,
            escapeshellarg($url)
        ]));
    }

    protected function getDataBundle(Process $process): DataBundle
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

        return new DataBundle(UrlDataCollection::createFromArray($providerData));
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

    public function getUser(): ?string
    {
        return $this->user;
    }

    public function setUser(?string $user): self
    {
        $this->user = trim((string) $user) !== '' ? $user : null;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = trim((string) $password) !== '' ? $password : null;

        return $this;
    }
}
