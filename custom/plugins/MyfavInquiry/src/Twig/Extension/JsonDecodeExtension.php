<?php declare(strict_types=1);

namespace Myfav\Inquiry\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class JsonDecodeExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('json_decode', [$this, 'jsonDecodeFilter']),
        ];
    }

    public function jsonDecodeFilter(string $json): array
    {
        return json_decode($json, true);
    }
}
