<?php

declare(strict_types = 1);

namespace Webduck\Console\Helper;

use Symfony\Component\Console\Output\OutputInterface;
use Webduck\Audit\Audit;
use Webduck\Audit\AuditResultCollection;

class AuditResultHelper
{
    const FORMAT_TEXT = 'text';
    const FORMAT_JSON = 'json';

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var string
     */
    protected $url;

    /**
     * @var AuditResultCollection
     */
    protected $audits;

    /**
     * @var string
     */
    protected $format = self::FORMAT_TEXT;

    public function __construct(OutputInterface $output, string $url, AuditResultCollection $audits, string $format = null)
    {
        $this->output = $output;
        $this->url = $url;
        $this->audits = $audits;
        $format !== null && $this->format = $format;
    }

    public function getOutput(): OutputInterface
    {
        return $this->output;
    }

    public function getAudits(): AuditResultCollection
    {
        return $this->audits;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function setFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    public function render(): void
    {
        $this->format === self::FORMAT_JSON ? $this->renderJson() : $this->renderText();
    }

    protected function renderText(): void
    {
        $lines = [];
        /** @var Audit $auditResult */
        foreach ($this->audits as $auditResult) {
            switch ($auditResult->getResolution()) {
                case Audit::RESOLUTION_ERROR:
                    $color = 'red';
                    break;
                case Audit::RESOLUTION_WARNING:
                    $color = 'yellow';
                    break;
                default:
                    $color = 'default';
            }

            $line = sprintf('- <fg=%s;options=bold>%s:</> %s', $color, $auditResult->getName(), $auditResult->getMessage());
            $line .= $this->output->isVerbose() ? sprintf(' <fg=blue>%s</>', json_encode($auditResult->getData())) : '';
            $lines[] = $line;
        }

        $lines = array_unique($lines);

        if (count($lines)) {
            sort($lines);
            array_map([$this->output, 'writeln'], $lines);
        } else {
            $this->output->writeln('<info>No problems found</info>');
        }
    }

    protected function renderJson(): void
    {
        throw new \Exception('Not implemented');
    }
}
