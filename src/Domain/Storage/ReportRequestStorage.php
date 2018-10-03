<?php

declare(strict_types = 1);

namespace Webduck\Domain\Storage;

use FilesystemIterator;
use SplFileInfo;
use Webduck\Domain\Collection\ReportRequestCollection;
use Webduck\Domain\Model\ReportRequest;

class ReportRequestStorage
{
    /**
     * @var SplFileInfo
     */
    protected $dir;

    public function __construct(string $dir)
    {
        $this->dir = new SplFileInfo($dir);
    }

    public function exists(string $reportUuid): bool
    {
        $this->ensureDirExists();

        return file_exists($this->getFilename($reportUuid));
    }

    public function get(string $reportUuid): ?ReportRequest
    {
        $this->ensureDirExists();

        $filename = $this->getFilename($reportUuid);

        return file_exists($filename) ?
            ReportRequest::fromArray(json_decode(file_get_contents($filename), true)) :
            null
        ;
    }

    public function index(): ReportRequestCollection
    {
        $this->ensureDirExists();

        $collection = new ReportRequestCollection();

        /** @var SplFileInfo $file */
        foreach (new FilesystemIterator($this->dir->getPathname()) as $file) {
            if (preg_match('/\.json$/', $file->getFilename())) {
                $uuid = preg_replace('/\.json$/', '', $file->getFilename());
                $collection->add($this->get($uuid));
            }
        }

        return $collection;
    }

    public function store(ReportRequest $reportRequest): void
    {
        $this->ensureDirExists();

        $filename = $this->getFilename($reportRequest->getReportUuid());
        file_put_contents($filename, json_encode($reportRequest->toArray()));
    }

    protected function ensureDirExists(): void
    {
        !$this->dir->isDir() && mkdir($this->dir->getPathname(), 0777, true);
    }

    protected function getFilename(string $reportUuid): string
    {
        return $this->dir->getPathname().DIRECTORY_SEPARATOR.$reportUuid.'.json';
    }
}
