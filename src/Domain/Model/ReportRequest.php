<?php

declare(strict_types = 1);

namespace Webduck\Domain\Model;

use InvalidArgumentException;

class ReportRequest
{
    const STATUS_PENDING = 0;
    const STATUS_RUNNING = 1;
    const STATUS_FINISHED = 2;
    const STATUS_ERROR = 3;

    const STATUS_STRING_PENDING = 'pending';
    const STATUS_STRING_RUNNING = 'running';
    const STATUS_STRING_FINISHED = 'finished';
    const STATUS_STRING_ERROR = 'error';

    /**
     * @var string
     */
    protected $reportUuid;

    /**
     * @var int
     */
    protected $status;

    /**
     * @var float
     */
    protected $progress;

    public function __construct(string $reportUuid, int $status, float $progress)
    {
        $this->reportUuid = $reportUuid;
        $this->setStatus($status);
        $this->setProgress($progress);
    }

    public static function create(string $reportUuid)
    {
        return new static($reportUuid, static::STATUS_PENDING, 0.0);
    }

    public function getReportUuid(): string
    {
        return $this->reportUuid;
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function setStatus(int $status): ReportRequest
    {
        if (!in_array($status, [self::STATUS_PENDING, self::STATUS_RUNNING, self::STATUS_FINISHED, self::STATUS_ERROR], true)) {
            throw new InvalidArgumentException('Invalid status provided');
        }

        $this->status = $status;

        return $this;
    }

    public function getStatusString(): string
    {
        $map = [
            self::STATUS_PENDING => self::STATUS_STRING_PENDING,
            self::STATUS_RUNNING => self::STATUS_STRING_RUNNING,
            self::STATUS_FINISHED => self::STATUS_STRING_FINISHED,
            self::STATUS_ERROR => self::STATUS_STRING_ERROR,
        ];

        return $map[$this->status];
    }

    public function getProgress(): float
    {
        return $this->progress;
    }

    public function setProgress(float $progress): ReportRequest
    {
        if ($progress < 0 || $progress > 1) {
            throw new InvalidArgumentException('Invalid progress provided %s', $progress);
        }

        $this->progress = $progress;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'report_uuid' => $this->reportUuid,
            'status' => $this->status,
            'progress' => $this->progress,
        ];
    }

    public static function fromArray(array $arr): self
    {
        return new static($arr['report_uuid'], $arr['status'], $arr['progress']);
    }
}
