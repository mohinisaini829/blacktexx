<?php declare(strict_types = 1);

namespace StudioSolid\AdvancedSliderElements\Core\Framework\Adapter\Twig\Extension;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class TwigExtensions extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('hexOpacity', [$this, 'hexOpacity']),
            new TwigFunction('hexDarken', [$this, 'hexDarken']),
            new TwigFunction('hexContrast', [$this, 'hexContrast'])
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('camelCaseToKebabCase', [$this, 'camelCaseToKebabCase'])
        ];
    }

    public function camelCaseToKebabCase(String $input)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $input));
    }

    public function hexOpacity(String $input, int $opacity)
    {
        if ($input[0] !== '#' || !isset($opacity) || $opacity > 255) {
            return false;
        }

        $hexOpacity = str_pad(dechex(intval($opacity)), 2, '0', STR_PAD_LEFT);

        if (strlen($input) == 9) {
            return substr($input, 0, -2) . $hexOpacity;
        } elseif (strlen($input) == 7) {
            return $input . $hexOpacity;
        } elseif (strlen($input) == 4) {
            return '#' . $input[1] . $input[1] . $input[2] . $input[2] . $input[3] . $input[3] . $hexOpacity;
        }

        return false;
    }

    public function hexDarken($input, $percentage)
    {
        if ($input[0] !== '#' || !isset($percentage)) {
            return false;
        }

        $percentageNumber = str_replace('%', '', $percentage);

        if (!is_numeric($percentageNumber)) {
            return false;
        }

        $hexRgb = null;

        if (strlen($input) == 9) {
            $hexRgb = str_replace('#', '', substr($input, 0, -2));
        } elseif (strlen($input) == 7) {
            $hexRgb = str_replace('#', '', $input);
        } elseif (strlen($input) == 4) {
            $hexRgb = $input[1] . $input[1] . $input[2] . $input[2] . $input[3] . $input[3];
        }

        if ($hexRgb !== null) {
            list($r, $g, $b) = str_split($hexRgb, 2);

            $r = str_pad(dechex(intval(hexdec($r) * (100 - $percentageNumber) / 100)), 2, '0', STR_PAD_LEFT);
            $g = str_pad(dechex(intval(hexdec($g) * (100 - $percentageNumber) / 100)), 2, '0', STR_PAD_LEFT);
            $b = str_pad(dechex(intval(hexdec($b) * (100 - $percentageNumber) / 100)), 2, '0', STR_PAD_LEFT);

            if (strlen($input) === 9) {
                return '#' . $r . $g . $b . $input[7] . $input[8];
            } else {
                return '#' . $r . $g . $b;
            }
        }
    }

    public function hexContrast($input)
    {
        if ($input[0] !== '#') {
            return false;
        }

        $hexRgb = null;

        if (strlen($input) == 9) {
            $hexRgb = str_replace('#', '', substr($input, 0, -2));
        } elseif (strlen($input) == 7) {
            $hexRgb = str_replace('#', '', $input);
        } elseif (strlen($input) == 4) {
            $hexRgb = $input[1] . $input[1] . $input[2] . $input[2] . $input[3] . $input[3];
        }

        if ($hexRgb !== null) {
            list($r, $g, $b) = str_split($hexRgb, 2);

            $luminosity1 = 0.2126 * pow(hexdec($r) / 255, 2.2) +
        0.7152 * pow(hexdec($g) / 255, 2.2) +
        0.0722 * pow(hexdec($b) / 255, 2.2);

            $luminosity2 = 0.2126 * pow(0 / 255, 2.2) +
        0.7152 * pow(0 / 255, 2.2) +
        0.0722 * pow(0 / 255, 2.2);

            $contrastRatio = 0;

            if ($luminosity1 > $luminosity2) {
                $contrastRatio = (int)(($luminosity1 + 0.05) / ($luminosity2 + 0.05));
            } else {
                $contrastRatio = (int)(($luminosity2 + 0.05) / ($luminosity1 + 0.05));
            }

            if ($contrastRatio > 5) {
                return '#000000';
            } else {
                return '#ffffff';
            }
        }
    }
}
