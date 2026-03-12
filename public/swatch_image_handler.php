<?php
/**
 * Swatch Image Handler - Dedicated Handler for Swatch Processing
 * 
 * Processes swatch images from CSV and assigns them to product color options
 * Integrates with product image import workflow
 * 
 * Usage:
 * - Called from create_product_media.php after CSV generation
 * - Processes: vendor_color_swatches_DDMMYY.csv
 * - Creates media files and assigns to color property options
 */

ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '600');
set_time_limit(600);

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');

// Check if called from import processor
if (!defined('IMPORT_PROCESSOR_MODE') && php_sapi_name() !== 'cli') {
    // Not called from import, exit silently
    exit('Swatch handler must be called from import processor.');
}

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;

require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

$classLoader = require __DIR__ . '/../vendor/autoload.php';
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$debug = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

$pluginLoader = null;
if (EnvironmentHelper::getVariable('COMPOSER_PLUGIN_LOADER', false)) {
    $pluginLoader = new ComposerPluginLoader($classLoader, null);
}

$kernel = KernelFactory::create(
    environment: $appEnv,
    debug: $debug,
    classLoader: $classLoader,
    pluginLoader: $pluginLoader
);

$kernel->boot();
$container = $kernel->getContainer();
$connection = $container->get(Connection::class);

class SwatchImageHandler {
    private $container;
    private $connection;
    private $context;
    private $mediaRepository;
    private $propertyRepository;
    private $mediaFolderId;
    
    // Color property group ID
    const COLOR_GROUP_ID = '0198135f7a2f7600a44ed9ab388d112a';
    const CURRENCY_ID = '2fbb5fe2e29a4d70aa5854ce7ce3e20b';
    
    public function __construct($container, $connection) {
        $this->container = $container;
        $this->connection = $connection;
        $this->context = Context::createDefaultContext();
        $this->mediaRepository = $container->get('media.repository');
        $this->propertyRepository = $container->get('property_group_option.repository');
        
        // Get or create media folder for swatches
        $this->mediaFolderId = $this->getOrCreateSwatchFolder();
    }
    
    /**
     * Main handler - processes swatch CSV file
     */
    public function process($swatchCsvPath) {
        $result = [
            'success' => false,
            'swatches_processed' => 0,
            'media_created' => 0,
            'errors' => []
        ];
        
        if (!file_exists($swatchCsvPath)) {
            $result['errors'][] = "CSV file not found: $swatchCsvPath";
            return $result;
        }
        
        $handle = fopen($swatchCsvPath, 'r');
        if (!$handle) {
            $result['errors'][] = "Cannot open CSV file: $swatchCsvPath";
            return $result;
        }
        
        // Skip header
        $header = fgetcsv($handle, null, ";");
        
        while (($row = fgetcsv($handle, null, ";")) !== false) {
            if (count($row) < 4) continue;
            
            $colorName = trim($row[1]);
            $swatchImage = trim($row[3]);
            
            if (empty($colorName) || empty($swatchImage)) {
                continue;
            }
            
            try {
                $optionId = $this->getPropertyOptionByName($colorName);
                if (!$optionId) {
                    $result['errors'][] = "Color not found: $colorName";
                    continue;
                }
                
                $mediaId = $this->createOrUpdateMedia($swatchImage, $colorName);
                if (!$mediaId) {
                    $result['errors'][] = "Failed to create media for: $colorName";
                    continue;
                }
                
                $this->assignMediaToPropertyOption($optionId, $mediaId);
                $result['swatches_processed']++;
                
                if ($this->mediaCreated) {
                    $result['media_created']++;
                }
            } catch (\Exception $e) {
                $result['errors'][] = "Error processing $colorName: " . $e->getMessage();
            }
        }
        
        fclose($handle);
        $result['success'] = $result['swatches_processed'] > 0;
        
        error_log("[SWATCH HANDLER] Processed {$result['swatches_processed']} swatches, created {$result['media_created']} media files");
        return $result;
    }
    
    /**
     * Get property option by color name
     */
    private function getPropertyOptionByName($name) {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('groupId', self::COLOR_GROUP_ID));
        
        $colors = $this->propertyRepository->search($criteria, $this->context);
        foreach ($colors as $color) {
            if (strtolower($color->getName()) === strtolower($name)) {
                return $color->getId();
            }
        }
        return null;
    }
    
    /**
     * Create or update media for swatch image
     */
    private $mediaCreated = false;
    
    private function createOrUpdateMedia($imageUrl, $title) {
        $this->mediaCreated = false;
        
        // Convert relative paths to absolute
        if (strpos($imageUrl, '/') === 0 && strpos($imageUrl, '://') === false) {
            $imageUrl = '/var/www/html/shopware678/public' . $imageUrl;
        }
        
        // Check if file exists
        if (!file_exists($imageUrl)) {
            error_log("[SWATCH] Image not found: $imageUrl");
            return null;
        }
        
        // Read image
        $imageContent = file_get_contents($imageUrl);
        if (!$imageContent) {
            error_log("[SWATCH] Failed to read image: $imageUrl");
            return null;
        }
        
        // Generate filename
        $filename = 'swatch_' . preg_replace('/[^a-z0-9-_]/i', '_', $title) . '_' . time() . '.jpg';
        
        // Check if media already exists
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $filename));
        $existing = $this->mediaRepository->search($criteria, $this->context);
        
        if ($existing->count() > 0) {
            return $existing->first()->getId();
        }
        
        // Create new media
        try {
            $mediaId = Uuid::randomHex();
            
            // Save file
            $mediaPath = '/var/www/html/shopware678/public/media/image/' . $filename;
            file_put_contents($mediaPath, $imageContent);
            
            // Create media entry
            $this->mediaRepository->create([
                [
                    'id' => $mediaId,
                    'mediaFolderId' => $this->mediaFolderId,
                    'fileName' => $filename,
                    'fileExtension' => 'jpg',
                    'fileSize' => filesize($mediaPath),
                    'mimeType' => 'image/jpeg',
                    'metaData' => json_encode(['width' => 100, 'height' => 100])
                ]
            ], $this->context);
            
            $this->mediaCreated = true;
            error_log("[SWATCH] ✅ Created media: $filename ($mediaId)");
            return $mediaId;
        } catch (\Exception $e) {
            error_log("[SWATCH] Error creating media: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Assign media to property option
     */
    private function assignMediaToPropertyOption($optionId, $mediaId) {
        try {
            // Update property_group_option.media_id
            $this->connection->executeStatement(
                'UPDATE property_group_option SET media_id = UNHEX(?) WHERE id = UNHEX(?)',
                [$mediaId, $optionId]
            );
            
            error_log("[SWATCH] ✅ Assigned media to option: $optionId");
            return true;
        } catch (\Exception $e) {
            error_log("[SWATCH] Error assigning media: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get or create swatch media folder
     */
    private function getOrCreateSwatchFolder() {
        $folderRepository = $this->container->get('media_folder.repository');
        
        // Try to find existing "Product Media" folder
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Product Media'));
        $folders = $folderRepository->search($criteria, $this->context);
        
        if ($folders->count() > 0) {
            return $folders->first()->getId();
        }
        
        // Fallback: get first folder
        $criteria = new Criteria();
        $criteria->setLimit(1);
        $folders = $folderRepository->search($criteria, $this->context);
        
        if ($folders->count() > 0) {
            return $folders->first()->getId();
        }
        
        // Last resort: return default folder ID
        return null;
    }
}

// Execute handler
if (php_sapi_name() === 'cli' || defined('IMPORT_PROCESSOR_MODE')) {
    $vendor = $_POST['vendor'] ?? 'media';
    $date = date("dmy");
    $swatchDir = __DIR__ . '/csv-imports/swatches';
    if (!is_dir($swatchDir)) {
        if (!@mkdir($swatchDir, 0777, true) && !is_dir($swatchDir)) {
            error_log("[SWATCH HANDLER] Failed to create swatch directory: {$swatchDir}");
        }
    }
    $swatchCsvPath = __DIR__ . "/csv-imports/swatches/{$vendor}_color_swatches_{$date}.csv";
    
    $handler = new SwatchImageHandler($container, $connection);
    $result = $handler->process($swatchCsvPath);
    
    if (php_sapi_name() === 'cli') {
        echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        // Return result to calling script
        return $result;
    }
}
?>
