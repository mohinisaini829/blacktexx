<?php

declare(strict_types=1);

namespace SantaFeTexTheme\Twig\Extensions;

use Doctrine\DBAL\Connection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrandExtension extends AbstractExtension
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getBrandSizeChart', [$this, 'getBrandSizeChart']),
        ];
    }

    public function getBrandSizeChart(string $manufacturerName): ?string
    {
        try {
            // Trim and normalize the manufacturer name for comparison
            $manufacturerName = trim($manufacturerName);
            
            $sql = 'SELECT size_chart_path FROM santafatex_brand 
                    WHERE TRIM(name) = :name 
                    AND active = 1 
                    AND size_chart_path IS NOT NULL 
                    AND size_chart_path != ""
                    LIMIT 1';
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery(['name' => $manufacturerName]);
            $row = $result->fetchAssociative();
            
            return $row ? ($row['size_chart_path'] ?? null) : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
