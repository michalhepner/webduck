<?php

declare(strict_types = 1);

namespace Webduck\Domain\Transformer;

use Twig\Environment;
use Webduck\Domain\Model\Report;

class ReportToHtmlTransformer
{
    /**
     * @var Environment
     */
    protected $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    public function transform(Report $report): string
    {
        return $this->twig->render('report.html.twig', ['report' => $report]);
    }
}
