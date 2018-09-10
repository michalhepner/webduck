<?php

declare(strict_types = 1);

namespace Webduck\Domain\Audit;

use Webduck\Domain\Collection\InsightCollection;
use Webduck\Domain\Model\Browse;
use Webduck\Domain\Model\BrowseEvent;
use Webduck\Domain\Model\Insight;

class NetworkAudit implements AuditInterface
{
    const NAME = 'Network';
    const CALL_TYPE_DOCUMENT = 'Document';
    const CALL_TYPE_STYLESHEET = 'Stylesheet';
    const CALL_TYPE_SCRIPT = 'Script';
    const CALL_TYPE_IMAGE = 'Image';
    const CALL_TYPE_FONT = 'Font';

    /**
     * @var int
     */
    protected $callThreshold;

    /**
     * @var int
     */
    protected $imageCallThreshold;

    /**
     * @var int
     */
    protected $scriptCallThreshold;

    /**
     * @var int
     */
    protected $stylesheetCallThreshold;

    /**
     * @var int
     */
    protected $fontCallThreshold;

    public function __construct(
        int $callThreshold,
        int $imageCallThreshold,
        int $scriptCallThreshold,
        int $stylesheetCallThreshold,
        int $fontCallThreshold
    ) {
        $this->callThreshold = $callThreshold;
        $this->imageCallThreshold = $imageCallThreshold;
        $this->scriptCallThreshold = $scriptCallThreshold;
        $this->stylesheetCallThreshold = $stylesheetCallThreshold;
        $this->fontCallThreshold = $fontCallThreshold;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function execute(Browse $urlData): InsightCollection
    {
        $callsWithoutType = [];
        $callsByType = [];

        $urlData->getEvents()->map(function (BrowseEvent $event) use (&$callsWithoutType, &$callsByType) {
            if ($event->getName() === 'Network.requestWillBeSent') {
                if (array_key_exists('type', $event->getData())) {
                    !array_key_exists($event->getData()['type'], $callsByType) && $callsByType[$event->getData()['type']] = [];
                    $callsByType[$event->getData()['type']][] = $event->getData()['request']['url'];
                } else {
                    $callsWithoutType[] = $event->getData()['request']['url'];
                }
            }
        });

        $insights = new InsightCollection();
        $overallCount = array_sum(array_map('count', $callsByType)) + count($callsWithoutType);

        $overallCount > $this->callThreshold && $insights->add(Insight::createWarning(
            self::NAME,
            sprintf('Page ran %d requests when loading', $overallCount),
            [
                'requests_by_type' => $callsByType,
                'requests_without_type' => $callsWithoutType,
            ]
        ));

        $auditType = function (string $type, int $threshold, callable $messageCallback) use ($callsByType, $insights) {
            if (array_key_exists($type, $callsByType) && count($callsByType[$type]) > $threshold) {
                $insights->add(Insight::createWarning(
                    self::NAME,
                    $messageCallback(count($callsByType[$type]), $threshold),
                    $callsByType[$type]
                ));
            }
        };

        $auditType(self::CALL_TYPE_STYLESHEET, $this->stylesheetCallThreshold, function (int $count, int $threshold) {
            return sprintf('Page ran %d requests loading stylesheets. Acceptable threshold was set to %d.', $count, $threshold);
        });
        $auditType(self::CALL_TYPE_SCRIPT, $this->scriptCallThreshold, function (int $count, int $threshold) {
            return sprintf('Page ran %d requests loading scripts. Acceptable threshold was set to %d.', $count, $threshold);
        });
        $auditType(self::CALL_TYPE_IMAGE, $this->imageCallThreshold, function (int $count, int $threshold) {
            return sprintf('Page ran %d requests loading images. Acceptable threshold was set to %d.', $count, $threshold);
        });
        $auditType(self::CALL_TYPE_FONT, $this->fontCallThreshold, function (int $count, int $threshold) {
            return sprintf('Page ran %d requests loading fonts. Acceptable threshold was set to %d.', $count, $threshold);
        });

        return $insights;
    }

    public function serialize()
    {
        return serialize([
            'call_threshold' => $this->callThreshold,
            'image_call_threshold' => $this->imageCallThreshold,
            'script_call_threshold' => $this->scriptCallThreshold,
            'stylesheet_call_threshold' => $this->stylesheetCallThreshold,
            'font_call_threshold' => $this->fontCallThreshold,
        ]);
    }

    public function unserialize($serialized)
    {
        $unserialized = unserialize($serialized);

        $this->callThreshold = $unserialized['call_threshold'];
        $this->imageCallThreshold = $unserialized['image_call_threshold'];
        $this->scriptCallThreshold = $unserialized['script_call_threshold'];
        $this->stylesheetCallThreshold = $unserialized['stylesheet_call_threshold'];
        $this->fontCallThreshold = $unserialized['font_call_threshold'];
    }
}
