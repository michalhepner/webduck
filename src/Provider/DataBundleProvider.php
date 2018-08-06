<?php

declare(strict_types = 1);

namespace Webduck\Provider;

use Heo\ParallelProcess\AsyncRunner;
use Heo\ParallelProcess\EmitterRunner;
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
            $process = new Process(sprintf('%s provide %s', $this->bin, $url));
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
            $process = new Process(sprintf('%s provide %s', $this->bin, $url));
            $process->setTimeout(0);

            $processes[$url] = $process;
        }

        $runner = new EmitterRunner($processes);
        $runner->setPoolSize($this->poolSize);
        $runner->run(function (Process $process) use ($onDataBundle) {
            $onDataBundle($this->getDataBundle($process));
        });
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

        if ($providerData === false) {
            throw new RuntimeException('Failed to parse provider process output');
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
}
