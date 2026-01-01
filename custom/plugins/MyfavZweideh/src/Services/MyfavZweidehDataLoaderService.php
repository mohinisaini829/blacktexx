<?php declare(strict_types=1);

namespace Myfav\Zweideh\Services;

use Doctrine\DBAL\Connection;
use Exception;
use Myfav\Zweideh\MyfavZweideh;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;

/**
 * Dieser Service lädt erweiterte Daten zu einem Design,
 * auf Basis des Anfrage-Prozesses.
 */
class MyfavZweidehDataLoaderService
{
    private Connection $connection;
    private SystemConfigService $systemConfigService;
    private SalesChannelContext $salesChannelContext;
    private EntityRepository $productRepository;

    /**
     * __construct
     *
     * @return void
     */
    public function __construct(
        Connection $connection,
        SystemConfigService $systemConfigService,
        EntityRepository $productRepository
    ) {
        $this->connection = $connection;
        $this->systemConfigService = $systemConfigService;
        $this->productRepository = $productRepository;
    }

    /**
     * loadByExtendedData
     *
     * @param  mixed $extendedData
     * @param  mixed $salesChannelContext
     * @return void
     */
    public function loadByExtendedData($extendedData, $salesChannelContext): array|null
    {
        $this->salesChannelContext = $salesChannelContext;
        $extendedData = json_decode($extendedData, true);
        $productData = null;
        $productName = 'Gestalteter Artikel';
        $productNameWithSize = '';
        $productSize = '';

        // Original-Artikelname laden, falls der Artikel in der Shopware-Datenbank existiert.
        if(isset($extendedData['originalProductId'])) {
            $productId = $extendedData['originalProductId'];
            $productData = $this->loadProduct($productId, $salesChannelContext);

            if(null !== $productData) {
                $productName = $productData->getName();
            }
        }

        // Weitere Informationen zum Artikelnamen hinzufügen.
        if(isset($extendedData['sizeName'])) {
            $productNameWithSize .= '<br /><span>Größe: ' . $extendedData['sizeName'] . '</span>';
            $productSize = $extendedData['sizeName'];
        }

        // Bild-URL laden.
        $imageUrl = $this->getImageUrlFromExtendedData($salesChannelContext, $extendedData);
        $designedPreviewImages = $this->getDesignedPreviewImages($salesChannelContext, $extendedData);
        
        return [
            'productData' => $productData,
            'productName' => $productName,
            'productNameWithSize' => $productNameWithSize,
            'productSize' => $productSize,
            'imageUrl' => $imageUrl,
            'designedPreviewImages' => $designedPreviewImages,
        ];
    }
    
    /**
     * loadProduct
     *
     * @param  mixed $productId
     * @param  mixed $salesChannelContext
     * @return void
     */
    private function loadProduct($productId, $salesChannelContext)
    {
        $product = $this->productRepository->search(new Criteria([$productId]), $salesChannelContext->getContext())->first();
        return $product;
    }
    
    /**
     * getImageUrlFromExtendedData
     *
     * @param  mixed $extendedData
     * @return void
     */
    private function getImageUrlFromExtendedData($salesChannelContext, $extendedData)
    {
        $lumiseData = $this->loadLumiseDataByExtendedData($extendedData);
        $image = $this->getLumiseImage($salesChannelContext, $lumiseData);
        return $image;
    }

    /**
     * getDesignedPreviewImages
     *
     * @param SalesChannelContext $salesChannelContext
     * @param mixed $extended
     * @return mixed
     */
    private function getDesignedPreviewImages(
        SalesChannelContext $salesChannelContext, 
        mixed $extendedData): mixed
    {
        $lumiseData = $this->loadLumiseDataByExtendedData($extendedData);

        $images = [];
        $imageFile = '';

        $timestamp = strtotime($lumiseData['created']);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $path = 
            $this->getLumisePath($salesChannelContext) . 
            'data/swCustomerDesigns/' . 
            $year . 
            '/' . 
            $month . 
            '/' . 
            $lumiseData['tmp_cart_id'] .
            '/';
        $jsonFilePath = $path . 'previewImages.json';
        //print_r($jsonFilePath);die;
        if(!file_exists($jsonFilePath)) {
            return null;
        }

        $previewImageData = file_get_contents($jsonFilePath);
        //print_r($previewImageData);die;
        $previewImageData = json_decode($previewImageData, true);

        if(null === $previewImageData || !is_array($previewImageData)) {
            return null;
        }

        foreach($previewImageData as $index => $data) {
            $realFilename = basename($data['filename']);
            $realFilename = $path . $realFilename;
            $previewImageData[$index]['realFilename'] = $realFilename;
        }

        return $previewImageData;
    }

    /**
     * getLumiseImage
     *
     * @param  SalesChannelContext $salesChannelContext
     * @param  array $lumiseData
     * @return string
     */
    private function getLumiseImage(SalesChannelContext $salesChannelContext, array $lumiseData)
    {
        $timestamp = strtotime($lumiseData['created']);
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $path = $this->getLumisePath($salesChannelContext) . 'data/swCustomerDesigns/' . $year . '/' . $month . '/';
        $filename = $lumiseData['tmp_cart_id'] . '.jpg';
        $filepath = $path . $filename;

        return $filepath;
    }
    
    /**
     * getLumisePath
     *
     * @param  SalesChannelContext $salesChannelContext
     * @return string
     */
    private function getLumisePath(SalesChannelContext $salesChannelContext)
    {
        $lumisePath = $this->systemConfigService->get(
            MyfavZweideh::PLUGIN_CONFIG . 'lumisInstallPath',
            $salesChannelContext->getSalesChannelId()
        );

        return $lumisePath;
    }

    /**
     * Load entry from database.
     *
     * @param  array $extendedData
     * @param  string $tmp_cart_id
     * @return array
     */
    public function loadLumiseDataByExtendedData($extendedData): array|null
    {
        $tmpCartId = $extendedData['lumiseTmpCartId'];

        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_shopware_designs', 'l');
        $queryBuilder->where('l.tmp_cart_id = ?');
        //$queryBuilder->setParameter(0, $key);
        $queryBuilder->setParameter(1, $tmpCartId);
        $result = $queryBuilder->executeQuery()->fetchAllAssociative();

        if (!is_array($result) || count($result) == 0) {
            return null;
        }

        $result = $result[0];
        return $result;
    }

    /*
    public function createDesignRequestFromTmpCart($salesChannelContext, $key, $tmp_cart_id)
    {

        $tmpCart = $this->loadTmpCart($key, $tmp_cart_id);

        if (!is_array($tmpCart) || count($tmpCart) == 0) {
            throw new Exception('Temporary cart with given key and id not found.');
        }

        // Create id and key
        $id = $this->generateId();
        $key = substr(bin2hex(random_bytes(20)), 0, 16);
        $timestamp = time();
        $dstPath = $this->getDesignRequestFilesPath($salesChannelContext) . '/' . date('Y', $timestamp) . '/' . date('m', $timestamp) . '/';
        
        if (!is_dir($dstPath)) {
            mkdir($dstPath, 0777, true);
        }
        
        $dstJsonDesignDataFilepath = $dstPath . $id . '.lumi';
        $dstPreviewJpgFilepath = $dstPath . $id . '.jpg';

        // Copy files
        $srcPath = $this->getTmpCartFilesPath($salesChannelContext, strtotime($tmpCart[0]['created']));
        $srcJsonDesignDataFilepath = $srcPath . $tmpCart[0]['tmp_cart_id'] . '.lumi';
        $srcPreviewJpgFilepath = $srcPath . $tmpCart[0]['tmp_cart_id'] . '.jpg';
        
        // Move data file
        if (!file_exists($dstJsonDesignDataFilepath)) {
            $status = copy($srcJsonDesignDataFilepath, $dstJsonDesignDataFilepath);

            if (false === $status) {
                throw new Exception('Could not copy data file ' . $srcJsonDesignDataFilepath . ' to ' . $dstJsonDesignDataFilepath);
            }
        }

        // Move preview file
        if (!file_exists($dstPreviewJpgFilepath)) {
            $status = copy($srcPreviewJpgFilepath, $dstPreviewJpgFilepath);

            if (false === $status) {
                throw new Exception('Could not copy preview file ' . $srcPreviewJpgFilepath . ' to ' . $dstPreviewJpgFilepath);
            }
        }

        // Save request entry
        $this->saveRequestEntry(
            $tmpCart[0]['aid'], // aid
            $key, // key
            $id, // lumise_design_request_id
            $tmpCart[0]['product'], // product
            $tmpCart[0]['product_cms'], // product_cms
            $tmpCart[0]['view'], // view
            $tmpCart[0]['author'], // author
            1, // active
            $tmpCart[0]['shopware_user_id'], // shopware_user_id
            date('Y-m-d H:i:s', $timestamp) //created
        );

        return [
            'aid' => $tmpCart[0]['aid'], // aid
            'key' => $key, // key
            'lumise_design_request_id' => $id, // lumise_design_request_id
            'product' => $tmpCart[0]['product'], // product
            'product_cms' => $tmpCart[0]['product_cms'], // product_cms
            'view' => $tmpCart[0]['view'], // view
            'author' => $tmpCart[0]['author'], // author
            'active' => 1, // active
            'tmpCart' => $tmpCart[0]['shopware_user_id'], // shopware_user_id
            'created' => date('Y-m-d H:i:s', $timestamp), //created
            'dstJsonDesignDataFilepath' => $dstJsonDesignDataFilepath,
            'dstPreviewJpgFilepath' => $dstPreviewJpgFilepath
        ];
    }

    public function createDesignRequestFromLumiseShopwareDesign($salesChannelContext, $lumise_design_id) {
        $data = $this->loadLumiseShopwareDesign($lumise_design_id);

        if (!is_array($data) || count($data) == 0) {
            throw new Exception('lumise_shopware_designs entry with given tmp_cart_id not found.');
        }

        // Create id and key
        $id = $this->generateId();
        $key = substr(bin2hex(random_bytes(20)), 0, 16);
        $timestamp = time();
        $dstPath = $this->getDesignRequestFilesPath($salesChannelContext) . '/' . date('Y', $timestamp) . '/' . date('m', $timestamp) . '/';
        
        if (!is_dir($dstPath)) {
            mkdir($dstPath, 0777, true);
        }
        
        $dstJsonDesignDataFilepath = $dstPath . $id . '.lumi';
        $dstPreviewJpgFilepath = $dstPath . $id . '.jpg';

        // Copy files
        $srcPath = $this->getLumiseShopwareDesignFilesPath($salesChannelContext, $data[0]['save_path']);
        $srcJsonDesignDataFilepath = $srcPath . $data[0]['tmp_cart_id'] . '.lumi';
        $srcPreviewJpgFilepath = $srcPath . $data[0]['tmp_cart_id'] . '.jpg';
        
        // Move data file
        if (!file_exists($dstJsonDesignDataFilepath)) {
            $status = copy($srcJsonDesignDataFilepath, $dstJsonDesignDataFilepath);

            if (false === $status) {
                throw new Exception('Could not copy data file ' . $srcJsonDesignDataFilepath . ' to ' . $dstJsonDesignDataFilepath);
            }
        }

        // Move preview file
        if (!file_exists($dstPreviewJpgFilepath)) {
            $status = copy($srcPreviewJpgFilepath, $dstPreviewJpgFilepath);

            if (false === $status) {
                throw new Exception('Could not copy preview file ' . $srcPreviewJpgFilepath . ' to ' . $dstPreviewJpgFilepath);
            }
        }

        // Save request entry
        $this->saveRequestEntry(
            $data[0]['aid'], // aid
            $key, // key
            $id, // lumise_design_request_id
            $data[0]['product'], // product
            $data[0]['product_cms'], // product_cms
            $data[0]['view'], // view
            $data[0]['author'], // author
            1, // active
            $data[0]['shopware_user_id'], // shopware_user_id
            date('Y-m-d H:i:s', $timestamp) //created
        );

        return [
            'aid' => $data[0]['aid'], // aid
            'key' => $key, // key
            'lumise_design_request_id' => $id, // lumise_design_request_id
            'product' => $data[0]['product'], // product
            'product_cms' => $data[0]['product_cms'], // product_cms
            'view' => $data[0]['view'], // view
            'author' => $data[0]['author'], // author
            'active' => 1, // active
            'tmpCart' => $data[0]['shopware_user_id'], // shopware_user_id
            'created' => date('Y-m-d H:i:s', $timestamp), //created
            'dstJsonDesignDataFilepath' => $dstJsonDesignDataFilepath,
            'dstPreviewJpgFilepath' => $dstPreviewJpgFilepath
        ];
    }

    /**
     * Load entry from database.
     *
     * @param  string $key
     * @param  string $tmp_cart_id
     * @return array
     */
    /*
    public function load(string $key, string $tmp_cart_id): array|null 
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_design_request', 'l');
        $queryBuilder->where('l.key = ? AND l.lumise_design_request_id = ?');
        $queryBuilder->setParameter(0, $key);
        $queryBuilder->setParameter(1, $tmp_cart_id);
        $result = $queryBuilder->execute()->fetchAll();

        if (!is_array($result) || count($result) == 0) {
            return null;
        }

        $result = $result[0];
        return $result;
    }

    /**
     * Load entry from database.
     *
     * @param  mixed $key
     * @param  mixed $tmp_cart_id
     * @return mixed
     */
    /*
    public function loadLumiseShopwareDesign($lumise_design_id): mixed {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('lumise_shopware_designs', 'l')
            ->where('l.tmp_cart_id = ?')
            ->setParameter(1, $lumise_design_id)
        ;

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }
    
    /**
     * loadTmpCart
     *
     * @param  mixed $key
     * @param  mixed $tmp_cart_id
     * @return mixed
     */
    /*
    public function loadTmpCart($key, $tmp_cart_id): mixed {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->select('*')
            ->from('lumise_tmp_cart_uploads', 'l')
            ->where('l.key = ? AND l.tmp_cart_id = ?')
            ->setParameter(0, $key)
            ->setParameter(1, $tmp_cart_id)
        ;

        $result = $queryBuilder->execute()->fetchAll();

        return $result;
    }

    /**
     * This method is taken from the lumise core file /[lumise-root]/core/includes/main.php
     * 
     * @param  mixed $length
     * @return void
     */
    /*
    public function generateId($length = 10) {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, $length);
    }

    public function getLumisePath($salesChannelContext) {
        $lumisePath = $this->systemConfigService->get(
            MyfavZweideh::PLUGIN_CONFIG . 'lumisInstallPath',
            $salesChannelContext->getSalesChannelId()
        );

        return $lumisePath;
    }

    public function getDesignRequestFilesPath($salesChannelContext) {
        $lumisePath = $this->getLumisePath($salesChannelContext);
        $path = getcwd() . '/' . $lumisePath . 'data/designRequests/';
        return $path;
    }

    public function getTmpCartFilesPath($salesChannelContext, $timestamp) {
        $year = date('Y', $timestamp);
        $month = date('m', $timestamp);

        $lumisePath = $this->getLumisePath($salesChannelContext);
        $path = getcwd() . '/' . $lumisePath . 'data/tmpCartUploads/' . $year . '/' . $month . '/';
        return $path;
    }

    public function getLumiseShopwareDesignFilesPath($salesChannelContext, $save_path) {
        $lumisePath = $this->getLumisePath($salesChannelContext);
        $path = getcwd() . '/' . $lumisePath . 'data/swCustomerDesigns' . $save_path;
        return $path;
    }

    public function saveRequestEntry(
        $aid,
        $key,
        $lumise_design_request_id,
        $product,
        $product_cms,
        $view,
        $author,
        $active,
        $shopware_user_id,
        $created
    ) {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder
            ->insert('lumise_design_request')
            ->setValue('aid', '?')
            ->setValue('`key`', '?')
            ->setValue('lumise_design_request_id', '?')
            ->setValue('product', '?')
            ->setValue('product_cms', '?')
            ->setValue('view', '?')
            ->setValue('author', '?')
            ->setValue('active', '?')
            ->setValue('shopware_user_id', '?')
            ->setValue('created', '?')

            ->setParameter(0, $aid)
            ->setParameter(1, $key)
            ->setParameter(2, $lumise_design_request_id)
            ->setParameter(3, $product)
            ->setParameter(4, $product_cms)
            ->setParameter(5, $view)
            ->setParameter(6, $author)
            ->setParameter(7, $active)
            ->setParameter(8, $shopware_user_id)
            ->setParameter(9, $created)
        ;
        $result = $queryBuilder->execute();
    }
    */
}