<?php

namespace Heo\ParallelProcess;

use ArrayAccess;
use ArrayIterator;
use IteratorAggregate;
use Symfony\Component\Process\Process;

class AsyncRunner implements ArrayAccess, IteratorAggregate
{
    /**
     * @var Process[]
     */
    protected $processes = [];

    /**
     * @var string[]
     */
    protected $pending = [];

    /**
     * @var string[]
     */
    protected $running = [];

    /**
     * @var int
     */
    protected $poolSize = 5;

    public function __construct(array $processes = [], $poolSize = null)
    {
        foreach ($processes as $key => $process) {
            $this->set($key, $process);
        }
    }

    public function run(): array
    {
        foreach ($this->processes as $key => $process) {
            $this->pending[] = $key;
        }

        while (count($this->pending) || count($this->running)) {
            foreach ($this->running as $index => $key) {
                if (!$this->processes[$key]->isRunning()) {
                    unset($this->running[$index]);
                }
            }

            while (count($this->pending) && ($this->poolSize < 1 || count($this->running) < $this->poolSize)) {
                $key = array_shift($this->pending);
                $this->processes[$key]->start();
                $this->running[] = $key;
            }


            $output = [];
            foreach ($this->processes as $key => $process) {
                $output[$key] = $process->getStatus();
            }

            usleep(10000);
        }

        return $this->processes;
    }

    public function set(string $key, Process $process): self
    {
        $this->processes[$key] = $process;

        return $this;
    }

    public function remove(string $key): self
    {
        unset($this->processes[$key]);

        return $this;
    }

    public function get(string $key): Process
    {
        return $this->processes[$key];
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->processes);
    }

    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    public function offsetUnset($offset)
    {
        $this->remove($offset);
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

    public function getIterator()
    {
        return new ArrayIterator($this->processes);
    }
}
