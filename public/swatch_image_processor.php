<?php
/**
 * SWATCH IMAGE PROCESSOR
 * 
 * Purpose: Handle swatch image assignment to simple products during import
 * Called by: create_product_media.php during image import process
 * 
 * Features:
 * - Assigns swatch images to product property options (colors)
 * - Updates media associations for color swatches
 * - Works independently from main product CSV import
 */

// Increase memory and execution time
ini_set('memory_limit', '2048M');
ini_set('max_execution_time', '600');
set_time_limit(600);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

use Doctrine\DBAL\Connection;
use Shopware\Core\Kernel;
use Composer\Autoload\ClassLoader;
use Shopware\Core\Framework\Plugin\KernelPluginLoader\ComposerPluginLoader;
use Shopware\Core\Framework\Adapter\Kernel\KernelFactory;
use Symfony\Component\Dotenv\Dotenv;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\DevOps\Environment\EnvironmentHelper;

// ============================================
// 1. BOOTSTRAP SHOPWARE KERNEL
// ============================================
$_SERVER['SCRIPT_FILENAME'] = __FILE__;
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

$classLoader = require __DIR__ . '/../vendor/autoload.php';
$appEnv = $_ENV['APP_ENV'] ?? 'dev';
$debug  = ($_ENV['APP_DEBUG'] ?? '1') !== '0';

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
$context = Context::createDefaultContext();

// ============================================
// 2. SWATCH IMAGE PROCESSOR CLASS
// ============================================
class SwatchImageProcessor {
    private $container;
    private $connection;
    private $context;
    private $mediaRepository;
    private $propertyOptionRepository;
    private $mediaFolderRepository;
    
    public function __construct($container, $connection, $context) {
        $this->container = $container;
        $this->connection = $connection;
        $this->context = $context;
        $this->mediaRepository = $container->get('media.repository');
        $this->propertyOptionRepository = $container->get('property_group_option.repository');
        $this->mediaFolderRepository = $container->get('media_folder.repository');
    }
    
    /**
     * Process swatch images from CSV data
     * 
     * @param array $swatchData [
     *   'color_name' => 'image_url',
     *   'Red' => 'https://example.com/red.jpg',
     *   'Blue' => 'https://example.com/blue.jpg'
     * ]
     * @param string $vendor Vendor name (ross, harko, newwave)
     * 
     * @return array ['success' => bool, 'message' => string, 'updated' => int]
     */
    public function processSatches($swatchData, $vendor = 'media') {
        $updated = 0;
        $errors = [];
        
        if (empty($swatchData)) {
            return [
                'success' => false,
                'message' => 'No swatch data provided',
                'updated' => 0
            ];
        }
        
        // ✅ Get or create media folder for swatches
        $mediaFolderId = $this->getOrCreateSwatchFolder($vendor);
        
        if (!$mediaFolderId) {
            return [
                'success' => false,
                'message' => 'Failed to create/get swatch media folder',
                'updated' => 0
            ];
        }
        
        // ✅ Color property group ID (Shopware 6.x standard)
        $colorPropertyGroupId = '0198135f7a2f7600a44ed9ab388d112a';
        
        // ✅ Process each color and its swatch image
        foreach ($swatchData as $colorName => $imageUrl) {
            try {
                $colorName = trim($colorName);
                $imageUrl = trim($imageUrl);
                
                if (empty($colorName) || empty($imageUrl)) {
                    $errors[] = "Skipped empty color or URL: '$colorName'";
                    continue;
                }
                
                // Get property option by color name
                $propertyOption = $this->getPropertyOptionByName($colorName, $colorPropertyGroupId);
                
                if (!$propertyOption) {
                    $errors[] = "Property option not found for color: $colorName";
                    continue;
                }
                
                // ✅ Upload/create media from URL
                $media = $this->createOrUpdateMedia($imageUrl, $colorName, $mediaFolderId);
                
                if (!$media) {
                    $errors[] = "Failed to create media for color: $colorName";
                    continue;
                }
                
                // ✅ Associate media with property option
                $updated += $this->assignMediaToPropertyOption($propertyOption->getId(), $media->getId());
                
                $this->log("✅ Swatch assigned: $colorName -> {$media->getFileName()}");
                
            } catch (\Exception $e) {
                $errors[] = "Error processing color '$colorName': " . $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'message' => "Processed " . count($swatchData) . " swatches, updated $updated",
            'updated' => $updated,
            'errors' => $errors
        ];
    }
    
    /**
     * Get property option by name
     */
    private function getPropertyOptionByName($name, $groupId) {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->addFilter(new EqualsFilter('groupId', $groupId));
        
        return $this->propertyOptionRepository->search($criteria, $this->context)->first();
    }
    
    /**
     * Get or create swatches media folder
     */
    private function getOrCreateSwatchFolder($vendor) {
        $folderName = 'Product Swatches - ' . ucfirst($vendor);
        
        // Try to find existing folder
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $folderName));
        
        $existingFolder = $this->mediaFolderRepository->search($criteria, $this->context)->first();
        
        if ($existingFolder) {
            return $existingFolder->getId();
        }
        
        // ✅ Use root Product Media folder instead
        $rootCriteria = new Criteria();
        $rootCriteria->addFilter(new EqualsFilter('name', 'Product Media'));
        $rootFolder = $this->mediaFolderRepository->search($rootCriteria, $this->context)->first();
        
        if ($rootFolder) {
            // Return root folder ID if exists
            return $rootFolder->getId();
        }
        
        // ✅ Fallback: Get first available media folder
        $allFoldersCriteria = new Criteria();
        $allFoldersCriteria->setLimit(1);
        $firstFolder = $this->mediaFolderRepository->search($allFoldersCriteria, $this->context)->first();
        
        if ($firstFolder) {
            return $firstFolder->getId();
        }
        
        // ✅ Last resort: Return null (use default)
        $this->log("⚠️ No media folder found, using default storage");
        return null;
    }
    
    /**
     * Create or update media from image URL or file path
     */
    private function createOrUpdateMedia($imageUrl, $title, $folderId) {
        try {
            $imageContent = null;
            
            // ✅ Check if it's a relative web path (starts with /)
            if (strpos($imageUrl, '/') === 0 && strpos($imageUrl, '://') === false) {
                // Relative path like "/my-imports/NEWWAVE/..."
                // Convert to absolute file path
                $kernel = $this->container->get('kernel');
                $absolutePath = $kernel->getProjectDir() . '/public' . $imageUrl;
                
                $this->log("📂 Reading from disk: $imageUrl");
                
                if (file_exists($absolutePath)) {
                    $imageContent = file_get_contents($absolutePath);
                    $this->log("✅ Image read from disk: $absolutePath");
                } else {
                    $this->log("❌ File not found: $absolutePath");
                    return null;
                }
            } else {
                // Full URL - download it
                $this->log("📥 Downloading from URL: $imageUrl");
                $imageContent = @file_get_contents($imageUrl);
                
                if (!$imageContent) {
                    $this->log("❌ Failed to download image from: $imageUrl");
                    return null;
                }
            }
            
            if (!$imageContent) {
                $this->log("❌ Failed to get image content from: $imageUrl");
                return null;
            }
            
            // Generate filename
            $filename = $this->sanitizeFilename($title) . '_' . substr(md5($imageUrl), 0, 8) . '.jpg';
            
            // Check if media already exists
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('fileName', $filename));
            if ($folderId) {
                $criteria->addFilter(new EqualsFilter('mediaFolderId', $folderId));
            }
            
            $existingMedia = $this->mediaRepository->search($criteria, $this->context)->first();
            
            if ($existingMedia) {
                return $existingMedia;
            }
            
            // Create media entry
            $mediaId = Uuid::randomHex();
            
            // ✅ Build media data array
            $mediaData = [
                'id' => $mediaId,
                'fileName' => $filename,
                'fileExtension' => 'jpg',
                'mimeType' => 'image/jpeg',
                'title' => $title . ' Swatch',
                'alt' => $title . ' Color Swatch',
                'fileSize' => strlen($imageContent)
            ];
            
            // Only add folder if it exists
            if ($folderId) {
                $mediaData['mediaFolderId'] = $folderId;
            }
            
            // Save to disk
            $kernel = $this->container->get('kernel');
            $mediaPath = $kernel->getProjectDir() . '/public/media/image/';
            if (!is_dir($mediaPath)) {
                mkdir($mediaPath, 0777, true);
            }
            
            file_put_contents($mediaPath . $filename, $imageContent);
            $this->log("✅ File saved to disk: $filename");
            
            // Create media entity in database
            $this->mediaRepository->create(
                [$mediaData],
                $this->context
            );
            
            return $this->mediaRepository->search(
                (new Criteria())->addFilter(new EqualsFilter('id', $mediaId)),
                $this->context
            )->first();
            
        } catch (\Exception $e) {
            $this->log("❌ Error creating media: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Assign media to property option
     */
    private function assignMediaToPropertyOption($optionId, $mediaId) {
        try {
            $this->propertyOptionRepository->update([
                [
                    'id' => $optionId,
                    'mediaId' => $mediaId
                ]
            ], $this->context);
            
            return 1;
        } catch (\Exception $e) {
            $this->log("❌ Error assigning media to property: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Sanitize filename
     */
    private function sanitizeFilename($filename) {
        // Remove special characters and spaces
        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        // Remove multiple underscores
        $filename = preg_replace('/_+/', '_', $filename);
        // Convert to lowercase
        return strtolower(trim($filename, '_'));
    }
    
    /**
     * Log messages
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        if (!defined('SWATCH_QUIET') || SWATCH_QUIET !== true) {
            echo "[$timestamp] $message\n";
        }
        error_log("[$timestamp] SWATCH PROCESSOR: $message");
    }
}

// ============================================
// 3. HANDLE REQUESTS
// ============================================

// Check if called directly with POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'process_swatches') {
    
    $swatchData = $_POST['swatch_data'] ?? [];
    $vendor = $_POST['vendor'] ?? 'media';
    
    $processor = new SwatchImageProcessor($container, $connection, $context);
    $result = $processor->processSatches($swatchData, $vendor);
    
    header('Content-Type: application/json');
    echo json_encode($result);
    exit;
}

// Export for inclusion in other files
return SwatchImageProcessor::class;
?>