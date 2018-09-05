<?php

declare(strict_types = 1);

namespace Webduck\Domain\Storage;

use SplFileInfo;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Transformer\JsonToReportTransformer;
use Webduck\Domain\Transformer\ReportToJsonTransformer;

class ReportStorage
{
    /**
     * @var SplFileInfo
     */
    protected $dir;

    /**
     * @var ReportToJsonTransformer
     */
    protected $reportToJsonTransformer;

    /**
     * @var JsonToReportTransformer
     */
    protected $jsonToReportTransformer;

    public function __construct(string $dir, ReportToJsonTransformer $reportToJsonTransformer, JsonToReportTransformer $jsonToReportTransformer)
    {
        $this->dir = new SplFileInfo($dir);
        $this->reportToJsonTransformer = $reportToJsonTransformer;
        $this->jsonToReportTransformer = $jsonToReportTransformer;
    }

    public function exists(string $uuid): bool
    {
        $this->ensureDirExists();

        return file_exists($this->getFilename($uuid));
    }

    public function get(string $uuid): ?Report
    {
        $this->ensureDirExists();

        $filename = $this->getFilename($uuid);

        return file_exists($filename) ?
            $this->jsonToReportTransformer->transform(gzdecode(file_get_contents($filename))) :
            null
        ;
    }

    public function store(string $uuid, Report $report): void
    {
        $this->ensureDirExists();

        $filename = $this->getFilename($uuid);
        file_put_contents($filename, gzencode($this->reportToJsonTransformer->transform($report)));
    }

    protected function ensureDirExists(): void
    {
        !$this->dir->isDir() && mkdir($this->dir->getPathname());
    }

    protected function getFilename($uuid): string
    {
        return $this->dir->getPathname().DIRECTORY_SEPARATOR.$uuid.'.json.gz';
    }
}
