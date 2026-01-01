<?php

declare(strict_types=1);

namespace SantaFeTexTheme\Twig\Extensions;


use Symfony\Component\DependencyInjection\ContainerInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class Bundles extends AbstractExtension
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function getFunctions(): array
    {
        return array(
            new TwigFunction('bundleExists', [$this, 'bundleExists']),
        );
    }

    public function bundleExists($bundle): bool
    {
        return array_key_exists(
            $bundle,
            $this->container->getParameter('kernel.bundles')
        );
    }
}
