<?php declare(strict_types=1);

namespace Santafatex\Brands\Twig;

use Doctrine\DBAL\Connection;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class BrandDataExtension extends AbstractExtension
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getBrandData', [$this, 'getBrandData']),
            new TwigFunction('getBrandByName', [$this, 'getBrandByName']),
            new TwigFunction('getAllActiveBrands', [$this, 'getAllActiveBrands']),
        ];
    }

    /**
     * Get brand data by name
     */
    public function getBrandByName(string $brandName): ?array
    {
        try {
            $brandName = trim($brandName);

            $sql = 'SELECT * FROM santafatex_brand 
                    WHERE TRIM(name) = :name 
                    AND active = 1 
                    LIMIT 1';
            
            $stmt = $this->connection->prepare($sql);
            $stmt->bindValue(':name', $brandName, \Doctrine\DBAL\ParameterType::STRING);
            $result = $stmt->executeQuery();
            $row = $result->fetchAssociative();
            
            // Prepend base path to size_chart_path if it exists
            if ($row && !empty($row['size_chart_path'])) {
                // If the path doesn't start with http/https, prepend a forward slash
                if (!preg_match('/^https?:\/\//', $row['size_chart_path'])) {
                    $row['size_chart_path'] = '/' . ltrim($row['size_chart_path'], '/');
                }
            }
            
            // Do the same for catalog_pdf_path
            if ($row && !empty($row['catalog_pdf_path'])) {
                if (!preg_match('/^https?:\/\//', $row['catalog_pdf_path'])) {
                    $row['catalog_pdf_path'] = '/' . ltrim($row['catalog_pdf_path'], '/');
                }
            }
            
            return $row ?: null;
        } catch (\Exception $e) {
            error_log($e->getMessage());
            return null;
        }
    }


    /**
     * Get all active brands
     */
    public function getAllActiveBrands(): array
    {
        try {
            $sql = 'SELECT * FROM santafatex_brand 
                    WHERE active = 1 
                    ORDER BY display_order ASC, name ASC';
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->executeQuery();
            
            return $result->fetchAllAssociative();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Generic function to get brand data (alias for getBrandByName)
     */
    public function getBrandData(string $brandName): ?array
    {
        $brandNameNew  = trim($brandName);
        return $this->getBrandByName($brandName);
    }
}
