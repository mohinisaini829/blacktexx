<?php

declare(strict_types=1);

namespace salty\ColorVariants\Structs;

use Shopware\Core\Framework\Struct\Struct;

class StorefrontListingData extends Struct
{
    /**
     * @phpstan-var array<string, mixed>
     */
    protected array $config = [];

    /**
     * @phpstan-var array<string, mixed>
     */
    private array $data;

    /**
     * @phpstan-param array<string, mixed> $data
     * @phpstan-param array<string, mixed> $pluginConfig
     */
    public function __construct(array $data, array $pluginConfig)
    {
        $this->data = $data;
        $this->config = $pluginConfig;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @phpstan-param array<string, mixed> $config
     */
    public function setConfig(array $config): void
    {
        $this->config = $config;
    }

    /**
     * @phpstan-return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @phpstan-param array<string, mixed> $data
     */
    public function setData(array $data): void
    {
        $this->data = $data;
    }
}
