<?php

declare(strict_types = 1);

namespace Webduck\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class HashExtension extends AbstractExtension
{
    public function getFilters()
    {
        return array(
            new TwigFilter('md5', array($this, 'md5')),
        );
    }

    public function md5($text)
    {
        return md5($text);
    }
}
