<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Webduck\Domain\Model\Insight;
use Webduck\Domain\Model\Report;
use Webduck\Domain\Model\ReportPage;
use Webduck\Domain\Model\Screenshot;

class JsonToReportTransformer
{
    public function transform(string $json): Report
    {
        $data = json_decode($json, true);

        return new Report(
            $data['uuid'],
            $data['name'],
            array_map(
                function (array $arr) {
                    return new ReportPage(
                        $arr['uri'],
                        array_map(
                            function (array $insight) {
                                return new Insight($insight['name'], $insight['mark'], $insight['message'], $insight['data']);
                            },
                            $arr['insights']
                        ),
                        $arr['screenshot'] !== null ? new Screenshot($arr['screenshot']['media_type'], $arr['screenshot']['is_base_64'], $arr['screenshot']['data']) : null
                    );
                },
                $data['pages']
            )
        );
    }
}
