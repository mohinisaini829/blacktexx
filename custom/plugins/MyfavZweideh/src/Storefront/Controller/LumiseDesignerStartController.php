<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use League\Flysystem\Filesystem;
use Myfav\Inquiry\Services\InquiryCartService;
use Shopware\Core\Content\Media\Pathname\UrlGenerator;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Shopware\Core\Content\Media\Core\Application\AbstractMediaUrlGenerator;
use Shopware\Core\Content\Media\Core\Params\UrlParams;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;

 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class LumiseDesignerStartController extends StorefrontController
{
    private Connection $connection;
    private EntityRepository $productRepository;
    private Filesystem $filesystem;
    private AbstractMediaUrlGenerator $mediaUrlGenerator;
    private EntityRepository $productMediaRepository;

    /**
     * __construct
     */
    public function __construct(
        Connection $connection,
        EntityRepository $productRepository,
        Filesystem $filesystem,
        AbstractMediaUrlGenerator $mediaUrlGenerator,
        EntityRepository $productMediaRepository,
    ) {
        $this->connection = $connection;
        $this->productRepository = $productRepository;
        $this->filesystem = $filesystem;
        $this->mediaUrlGenerator = $mediaUrlGenerator;
        $this->productMediaRepository = $productMediaRepository;
    }
    #[Route(path: '/myfavDesigner/start', name: 'frontend.myfav.designer.start', methods: ['GET'])]
    public function start(Request $request, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        $productId = $request->query->get('productId');

        // Artikel-Daten laden.
        $product = $this->loadProductData($salesChannelContext, $productId);
        $productMedias = $this->loadProductMediaSorted($salesChannelContext, $productId);

        $images = [];

        foreach($productMedias as $productMedia) {
            $media = $productMedia->getMedia();
            $mediaType = $media->getMediaType();
            $mediaTypeName = $mediaType->getName();

            if($mediaTypeName == 'IMAGE') {
                $images[] = $media;
            }
        }

        // Hash über die Bilddaten erzeugen.
        $imagesHashString = '';

        foreach($images as $index => $image) {
            $path = $image->getPath();
            //$path = $this->mediaUrlGenerator->getRelativeMediaUrl($image);
            $sha1 = sha1_file($path);

            if(strlen($sha1) > 0) {
                $imagesHashString .= ';';
                
                $imageExtensions = $image->getExtensions();
                $imageExtensions['myfavHash'] = $sha1;
                $imageExtensions['myfavPath'] = $path;
                $images[$index]->setExtensions($imageExtensions);
            }

            $imagesHashString .= $sha1;
        }

        if(strlen($imagesHashString) == 0) {
            throw new \Exception('No images found.');
        }

        $imagesHashString = $product->getId() . '|' . $imagesHashString; // Added product id, to make it unique, even if the same images are used in a different shopware product.
        $imagesHash = sha1($imagesHashString);

        // Prüfen, ob ein Designer mit diesem Hash bereits existiert.
        $lumiseProductId = '';
        $designerHashEntry = $this->getDesignerByHash($imagesHash);

        if($designerHashEntry !== null) {
            // Falls ein Designer mit diesem Hash existiert, diesen laden.
            $lumiseProductId = $designerHashEntry['lumise_product_id'];
        } else {
            // Falls kein Designer mit diesem Hash existiert einen neuen anlegen, anschließend laden.
            $lumiseProductId = $this->createDesigner($imagesHash, $product, $images);
        }

        $url = $request->getScheme() . '://' . $request->getHost() . '/lumise/editor.php?product_base=' . $lumiseProductId . '&shopwareProduct=' . $product->getId();
        return new RedirectResponse($url);
    }
    
    /**
     * loadProductData
     *
     * @param  mixed $salesChannelContext
     * @param  mixed $productId
     * @return mixed
     */
    private function loadProductData(SalesChannelContext $salesChannelContext, string $productId) {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('media');
        $criteria->addSorting(new FieldSorting('media.position', FieldSorting::ASCENDING));

        $products = $this->productRepository->search($criteria, $salesChannelContext->getContext());
        $product = $products->first();

        if(null === $product) {
            throw new \Exception('Product was not found.');
        }

        return $products->first();
    }

    /**
     * loadProductMediaSorted
     *
     * @param  mixed $salesChannelContext
     * @param  mixed $productId
     * @return mixed
     */
    private function loadProductMediaSorted($salesChannelContext, $productId): mixed
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('productId', $productId));
        $criteria->addAssociation('media');
        $criteria->addSorting(new FieldSorting('position', FieldSorting::ASCENDING));

        $productMedia = $this->productMediaRepository->search($criteria, $salesChannelContext->getContext());
        
        return $productMedia;
    }

    /**
     * getDesignerByHash
     *
     * @param  mixed $salesChannelContext
     * @param  mixed $imagesHash
     * @return mixed
     */
    private function getDesignerByHash($imagesHash): mixed
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products_hashes', 'lph');
        $queryBuilder->where('lph.images_hash_sha1 = :images_hash_sha1');
        $queryBuilder->setParameter('images_hash_sha1', $imagesHash);
        $results = $queryBuilder->executeQuery();

        if(!is_array($results)) {
            return null;
        }

        if(!isset($results[0])) {
            return null;
        }

        return $results[0];
    }

    /**
     * createDesigner
     *
     * @param  mixed $imagesHashString
     * @param  mixed $product
     * @param  mixed $images
     * @return mixed
     */
    private function createDesigner($imagesHashString, $product, $images): mixed
    {
        $images = $this->prepareImages($images);
        $stagesString = $this->buildStagesString($images);
        $stages = base64_encode(urlencode($stagesString));

        // Wir brauchen im Moment keine Attribute. Attribute wären sowas wie Größe, Farbe, ...
        $attributesString = $this->buildAttributesString([]);
        $attributes = base64_encode(urlencode($attributesString));

        // Wir brauchen im Moment keine Variationen..
        $variationsString = $this->buildVariationsString();
        $variations = base64_encode(urlencode($variationsString));

        // Get thumbnail image.
        $thumbnailImageUrl = $this->getThumbnailImage($images);

        // Get product name.
        $productName = $this->getProductName($product);

        // $this->testDesignerProductStage();
        // $this->testDesignerAttributes();
        // $this->testDesignerVariations();
        // $this->testDesignerProduct();

        $lumiseProductId = $this->createLumiseDesignerProduct(
            $productName,
            "0.00", // productPrice
            "0", // product
            "", // thumbnail
            $thumbnailImageUrl,
            "0", //template
            "", // description
            "0", // active_description
            $stages,
            $variations,
            $attributes,
            "%7B%7D", // printings
            "0", // order
            "1", // active
            "", // author
            date('Y-m-d H:i:s'), // created
            date('Y-m-d H:i:s') // updated
        );

        $this->createMappingTableEntry(
            $lumiseProductId,
            $imagesHashString,
            $product->getId()
        );

        return $lumiseProductId;
    }

    /**
     * prepareImages
     *
     * @param  mixed $images
     * @return mixed
     */
    private function prepareImages($images): mixed
    {
        foreach($images as $index => $image) {
            $extensions = $image->getExtensions();

            // myfavHash should contain the sha1 hash create with sha1_hash.
            // myfavPath should contain the path to the image on the filesystem.
            $path = $this->prepareImagePath($extensions['myfavHash'], $extensions['myfavPath']);
            $extensions['myfavLumiseImagePath'] = $path;

            // Bildbreite anhand des Bildverhältnisses berechnen. Höhe ist dabei immer ein fester Wert.
            $paintedImageHeight = 450;
            $paintedImageWidth = $this->calculatePaintedImageWidth($image, $paintedImageHeight);

            $extensions['myfavImageHeight'] = $paintedImageHeight;
            $extensions['myfavImageWidth'] = $paintedImageWidth;

            // Bildname erstellen
            $extensions['myfavStageName'] = 'Seite ' . ($index + 1);

            $images[$index]->setExtensions($extensions);
        }

        return $images;
    }
    
    /**
     * calculatePaintedImageWidth
     *
     * @param  mixed $image
     * @param  mixed $paintedImageHeight
     * @return int
     */
    private function calculatePaintedImageWidth($image, $paintedImageHeight): int
    {
        $metaData = $image->getMetaData();

        if(!isset($metaData)) {
            throw new \Exception('Missing image meta data..');
        }

        $width = $metaData['width'];
        $height = $metaData['height'];

        $percentage = $width / $height;
        $paintedImageWidth = (int)floor($paintedImageHeight * $percentage);

        return $paintedImageWidth;
    }

    /**
     * Takes the first 3 parts of 2 chars of the sha1, and uses them to build a final filename.
     */
    private function prepareImagePath($sha1, $srcPath): mixed 
    { 
        $path = "";

        // Add path to the lumises generatedProducts Folder.
        $path .= 'generatedProducts/';
        
        // Add hashy subfolders, to keep file system sane.
        $path .= substr($sha1, 0, 2);
        $path .= '/';
        $path .= substr($sha1, 2, 2);
        $path .= '/';
        $path .= substr($sha1, 4, 2);
        $path .= '/';

        // Add image name.
        $newImageFilename = $sha1;

        // Add image file extension.
        $ext = pathinfo($srcPath, PATHINFO_EXTENSION);
        $newImageFilename .= '.';
        $newImageFilename .= $ext;

        $path .= $newImageFilename;

        // Create path.
        $createPath = 'lumise/data/';
        $createPath .= dirname($path);

        if(!is_dir($createPath)) {
            $result = mkdir($createPath, 0777, true);

            if(!$result) {
                throw new \Exception('Could not create image path');
            }
        }

        // Copy the image to its final path.
        $result = copy($srcPath, $createPath . '/' . $newImageFilename);

        if(!$result) {
            throw new \Exception('Could not copy image');
        }

        return $path;
    }

    /**
     * buildAttributesString
     */
    private function buildAttributesString($attributes): string {
        $attributes = json_encode($attributes);
        return $attributes;
    }
    
    /**
     * buildVariationsString
     */
    private function buildVariationsString(): string {
        $data = [
            "default" => "",
            "attrs" => [],
            "variations" => []
        ];

        $variations = json_encode($data);
        return $variations;
    }
    
    /**
     * buildStagesString
     *
     * @param  mixed $images
     * @return string
     */
    private function buildStagesString($images): string
    {
        $stages = [];

        foreach($images as $image) {
            $extensions = $image->getExtensions();
            $stageId = strtolower(str_replace(' ', '', $extensions['myfavStageName']));

            $stages[$stageId] = $this->buildStageArrayByImage($image);
        }

        $stagesString = json_encode($stages);
        return $stagesString;
    }
    
    /**
     * buildStageArrayByImage
     *
     * @param  mixed $image
     * @return mixed
     */
    private function buildStageArrayByImage($image): mixed
    {
        $extensions = $image->getExtensions();

        $retval = [];

        $retval["edit_zone"] = [
            "height" => $extensions['myfavImageHeight'],
            "width" => $extensions['myfavImageWidth'],
            "left" => -2.5,
            "top" => -7.5,
            "radius" => "0"
        ];

        $retval["url"] = $extensions['myfavLumiseImagePath'];
        $retval["source"] = "";
        $retval["overlay"] = false;
        $retval["product_width"] = $extensions['myfavImageWidth'];
        $retval["product_height"] = $extensions['myfavImageHeight'];
        $retval["template"] = [];
        $retval["size"] = "";
        $retval["orientation"] = "portrait";
        $retval["label"] = $extensions['myfavStageName'];

        return $retval;
    }

    /**
     * testDesignerProductStage
     *
     * @return void
     */
    private function testDesignerProductStage(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products', 'lp');
        $queryBuilder->where('lp.id = 2');
        $results = $queryBuilder->execute()->fetchAll();

        if(!is_array($results) && !isset($result[0])) {
            die('product not found');
        }

        $stages = urldecode(base64_decode($results[0]['stages']));

        $stages = json_decode($stages, true);
        
        echo '<pre>';
        var_dump($stages);
        die;
    }

    /**
     * testDesignerAttributes
     *
     * @return void
     */
    private function testDesignerAttributes(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products', 'lp');
        $queryBuilder->where('lp.id = 2');
        $results = $queryBuilder->execute()->fetchAll();

        if(!is_array($results) && !isset($result[0])) {
            die('product not found');
        }

        $attributes = $results[0]['attributes'];

        $attributes = urldecode(base64_decode($attributes));

        $attributes = json_decode($attributes, true);
        
        echo '<pre>';
        var_dump($attributes);
        die;
    }

    /**
     * testDesignerVariations
     *
     * @return void
     */
    private function testDesignerVariations(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products', 'lp');
        $queryBuilder->where('lp.id = 2');
        $results = $queryBuilder->execute()->fetchAll();

        if(!is_array($results) && !isset($result[0])) {
            die('product not found');
        }

        $variations = $results[0]['variations'];

        $variations = urldecode(base64_decode($variations));

        $variations = json_decode($variations, true);
        
        echo '<pre>';
        var_dump($variations);
        die;
    }

    /**
     * testDesignerProduct
     *
     * @return void
     */
    private function testDesignerProduct(): void
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_products', 'lp');
        $queryBuilder->where('lp.id = 2');
        $results = $queryBuilder->execute()->fetchAll();

        if(!is_array($results) && !isset($result[0])) {
            die('product not found');
        }
        
        echo '<pre>';
        var_dump($results[0]);
        die;
    }

    /**
     * getThumbnailImage
     */
    private function getThumbnailImage($images): string
    {
        $extensions = $images[0]->getExtensions();
        return $extensions['myfavLumiseImagePath'];
    }

    /**
     * getProductName
     */
    private function getProductName($product): string
    {
        $name = $product->getName();
        return $name ?? 'Product';
    }
    
    /**
     * createLumiseDesignerProduct
     *
     * @return int
     */
    private function createLumiseDesignerProduct(
        $productName,
        $productPrice,
        $product,
        $thumbnail,
        $thumbnailImageUrl,
        $template,
        $description,
        $activeDescription,
        $stages,
        $variations,
        $attributes,
        $printings,
        $order,
        $active,
        $author,
        $created,
        $updated): int
    {
        $sql = 
            'INSERT INTO lumise_products ' . 
                '(`name`, `price`, `thumbnail`, `thumbnail_url`, `template`, `description`, `active_description`,' . 
                '`stages`, `variations`, `attributes`, `printings`, `order`, `active`, `author`, `created`, `updated`) ' .
            'VALUES (' . 
                ':name, ' .
                ':price, ' .
                ':thumbnail, ' .
                ':thumbnail_url, ' .
                ':template, ' .
                ':description, ' .
                ':active_description, ' .
                ':stages, ' .
                ':variations, ' .
                ':attributes, ' .
                ':printings, ' .
                ':order, ' .
                ':active, ' .
                ':author, ' .
                ':created, ' .
                ':updated' .
            ');';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':name', $productName);
        $stmt->bindValue(':price', $productPrice);
        $stmt->bindValue(':thumbnail', $thumbnail);
        $stmt->bindValue(':thumbnail_url', $thumbnailImageUrl);
        $stmt->bindValue(':template', $template);
        $stmt->bindValue(':description', $description);
        $stmt->bindValue(':active_description', $activeDescription);
        $stmt->bindValue(':stages', $stages);
        $stmt->bindValue(':variations', $variations);
        $stmt->bindValue(':attributes', $attributes);
        $stmt->bindValue(':printings', $printings);
        $stmt->bindValue(':order', $order);
        $stmt->bindValue(':active', $active);
        $stmt->bindValue(':author', $author);
        $stmt->bindValue(':created', $created);
        $stmt->bindValue(':updated', $updated);
        $stmt->executeStatement();

        $lastInsertId = $this->connection->lastInsertId();

        return (int)$lastInsertId;
    }

    /**
     * createMappingTableEntry
     */
    private function createMappingTableEntry(
        $lumiseProductId,
        $imagesHashString,
        $shopwareProductId): void
    {
        $sql = 
            'INSERT INTO lumise_products_hashes ' . 
                '(`lumise_product_id`, `images_hash_sha1`, `shopware_product_id`) ' .
            'VALUES (' . 
                ':lumise_product_id, ' .
                ':images_hash_sha1, ' .
                ':shopware_product_id' .
            ');';

        $stmt = $this->connection->prepare($sql);
        $stmt->bindValue(':lumise_product_id', $lumiseProductId);
        $stmt->bindValue(':images_hash_sha1', $imagesHashString);
        $stmt->bindValue(':shopware_product_id', $shopwareProductId);
        $stmt->executeStatement();
    }
}