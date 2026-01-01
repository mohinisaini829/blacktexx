<?php declare(strict_types=1);

namespace ArticleImport\Helper;

use Shopware\Core\Framework\Context;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Content\Media\File\MediaFile;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\AndFilter;
use Symfony\Component\HttpKernel\KernelInterface;

class Data
{
	public $mediaRepository;
    public $productRepository;
	public $mediaService;
    public $propertyGrpRepository;
    public $propertyGroupOptionRepository;
    public $ruleRepository;
    public $categoryRepository;
    public $manufacturerRepository;
    public $productConfiguratorSettingRepo;
    public $salesChannelRepository;

    public function __construct(
        EntityRepository $mediaRepository,
        MediaService $mediaService,
        #[Autowire('property_group.repository')] 
        EntityRepository $propertyGrpRepository,
        EntityRepository $propertyGroupOptionRepository,
        EntityRepository $productRepository,
        EntityRepository $ruleRepository,
        EntityRepository $categoryRepository,
        EntityRepository $manufacturerRepository,
        EntityRepository $productConfiguratorSettingRepo,
        EntityRepository $salesChannelRepository,
        KernelInterface $kernel
    ){
        $this->mediaRepository 	    = 	$mediaRepository;
        $this->mediaService 	    = 	$mediaService;
        $this->productRepository    =   $productRepository;
        $this->ruleRepository       =   $ruleRepository;
        $this->categoryRepository   =   $categoryRepository;        
        $this->propertyGrpRepository            =   $propertyGrpRepository;
        $this->propertyGroupOptionRepository    =   $propertyGroupOptionRepository;
        $this->manufacturerRepository           =   $manufacturerRepository;
        $this->productConfiguratorSettingRepo   =   $productConfiguratorSettingRepo;
        $this->salesChannelRepository           =   $salesChannelRepository;
        $this->kernel           =   $kernel;
    }

    public function getDefaultSalesChannel(): string
    {
        return '0197e3dc1566708987331d818f8e1867';
    }

    public function getCategoryFolderId(): string
    {
        return '019600f43e7f7398908b4422cc1e1bf4';
    }

    public function getProductFolderId(): string
    {
        return '0197e3c809ad72c0bf36afbb80176064';
    }

    public function getDefaultTaxId(): string
    {
        return '0197e3c80947729bbb9c9ca9f3238a05';
/*        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'Standard rate'));

        $tax = $taxRepository->search($criteria, Context::createDefaultContext())->first();

        if ($tax) {
            $taxId = $tax->getId();
        }*/
    }

   /* public function uploadImageFromUrl(string $imageUrl, string $folderId = '', string $fName = '')
    { 

        $basePath   =   $this->kernel->getProjectDir().'/my-imports/Harko/';
        $imageUrl   =   $basePath.$imageUrl;

        if (!file_exists($imageUrl)) {
            return;
        }

        if (empty($fName)) {
        	$fName 	=	basename($imageUrl);
        }
    	
        $context    	    =   Context::createDefaultContext();
        $tmpFilePath        =   $imageUrl;

        $originalFileName 	= 	pathinfo($tmpFilePath, PATHINFO_FILENAME);
        $extension 			= 	pathinfo($tmpFilePath, PATHINFO_EXTENSION);        	
        
        $ifMediaExist 		=	$this->imageExistsInFolder($originalFileName, $folderId, $context);

        if (!empty($ifMediaExist)) {
        	return $ifMediaExist;
        } 

        //$uniqueFileName 	= 	$this->generateUniqueFileName($originalFileName, $extension, $context);

        // Generate new media ID
        $mediaId = Uuid::randomHex();

        // Create media entity
        $this->mediaRepository->create([[
            'id' => $mediaId,
            'name' => $originalFileName,
            'fileName' => $originalFileName,
            'mediaFolderId' => $folderId,
            'fileExtension' => $extension
        ]], $context);

        // Upload file
        $mediaFile = new MediaFile(
            $tmpFilePath,
            mime_content_type($tmpFilePath),
            $extension,
            filesize($tmpFilePath)
        );

        $this->mediaService->saveMediaFile($mediaFile, $originalFileName, $context, $folderId, $mediaId);

        unlink($tmpFilePath);
        return $mediaId;    
    }*/
    public function uploadImageFromUrl(string $imageUrl, string $folderId = '', string $fName = '') 
    {
        if (empty($fName)) {
            $fName = basename(parse_url($imageUrl, PHP_URL_PATH));
        }

        // Decode URL
        $fName = urldecode($fName);

        // Sanitize
        $fName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $fName);

        // Separate filename and extension
        $extension = pathinfo($fName, PATHINFO_EXTENSION);
        $originalFileName = pathinfo($fName, PATHINFO_FILENAME);

        // Temporary file path
        $tmpFilePath = sys_get_temp_dir() . '/' . $fName;

        // Download file
        $fileContents = @file_get_contents($imageUrl);
        if (!$fileContents) return null;
        file_put_contents($tmpFilePath, $fileContents);

        $context = Context::createDefaultContext();

        // Check if already exists
        $existingMedia = $this->imageExistsInFolder($originalFileName, $folderId, $context);
        if (!empty($existingMedia)) {
            @unlink($tmpFilePath);
            return $existingMedia;
        }

        // Generate media ID
        $mediaId = Uuid::randomHex();

        // Create media entity
        $this->mediaRepository->create([[
            'id' => $mediaId,
            'name' => $originalFileName, // name without extension
            'fileName' => $originalFileName, // remove extension here
            'mediaFolderId' => $folderId,
            'fileExtension' => $extension
        ]], $context);

        // Save file
        $mediaFile = new MediaFile(
            $tmpFilePath,
            mime_content_type($tmpFilePath),
            $extension,
            filesize($tmpFilePath)
        );

        $this->mediaService->saveMediaFile($mediaFile, $originalFileName, $context, $folderId, $mediaId);

        @unlink($tmpFilePath);

        return $mediaId;
    }


	public function generateUniqueFileName(string $baseName, string $extension, Context $context): string
    {
        $name = $baseName;
        $counter = 1;

        while ($this->mediaExists($name, $context)) {
            $name = $baseName . '-' . $counter;
            $counter++;
        }

        return $name;
    }

    public function mediaExists(string $fileName, Context $context): bool
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('fileName', $fileName));
        $criteria->setLimit(1);

        return $this->mediaRepository->search($criteria, $context)->count() > 0;
    }

	public function imageExistsInFolder(string $fileName, string $folderId, Context $context): string
	{
	    $criteria = new Criteria();
	    $criteria->addFilter(new AndFilter([
	        new EqualsFilter('fileName', $fileName),
	        new EqualsFilter('mediaFolderId', $folderId),
	    ]));

	    $result = $this->mediaRepository->search($criteria, $context);
	    
	    if ($result->count() > 0) {
			return $result->first()->getId();	    	
	    } else {
	    	return '';
	    }
	}

    public function getPropertyId($name){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $groups = $this->propertyGrpRepository->search($criteria, Context::createDefaultContext());
        foreach ($groups as $group) {
            return $group->getId();
        }
    }

    public function getPropertyOptionId($name, $gName){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $criteria->addFilter(new EqualsFilter('group.name', $gName));
        $groups = $this->propertyGroupOptionRepository->search($criteria, Context::createDefaultContext());

        if (count($groups) > 0){
            foreach ($groups as $group) {            
                return $group->getId();
            }
        } else {
            return $this->createPropertyOption($gName, $name);
        }
    }

    public function createPropertyOption(string $propertyGroupName, string $optionName){
        $context = Context::createDefaultContext();

        $criteria = new \Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria();
        $criteria->addFilter(new \Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter('name', $propertyGroupName));

        /** @var PropertyGroupEntity|null $propertyGroup */
        $propertyGroup = $this->propertyGrpRepository->search($criteria, $context)->first();

        if (!$propertyGroup) {
            return;
        }

        // Step 2: Create the option inside that property group
        $optId = Uuid::randomHex();
        $this->propertyGroupOptionRepository->create([
            [
                'id' => $optId,
                'groupId' => $propertyGroup->getId(),
                'name' => $optionName,
                'translations' => [
                    'en-GB' => ['name' => $optionName],
                    'de-DE' => ['name' => $optionName],
                ],
            ]
        ], $context);
        return $optId;
    }

    public function checkIfProductExist($productNumber){
        //die('hhhh');
        $criteria   =   new Criteria();
        $criteria->addFilter(new EqualsFilter('productNumber', $productNumber));
        $result     =   $this->productRepository->search($criteria, Context::createDefaultContext());

        if ($result->count() > 0) {
            return $result->first()->getId();
        } else {
            return false;
        }
    }

    public function getDefaultRuleId()
    {
        $context    =   Context::createDefaultContext();
        $criteria   =   new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'All customers'));
        $rule       =   $this->ruleRepository->search($criteria, $context)->first();
        return $rule->getId();
    }

    public function findCategory($name){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $category = $this->categoryRepository->search($criteria, Context::createDefaultContext())->first();
        if ($category){
            return $category->getId();
        }
    }

    public function getManufacturerId($name){
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', $name));
        $context = Context::createDefaultContext();
        $result = $this->manufacturerRepository->search($criteria, $context);
        if ($result->getTotal() > 0) {
            return $result->first()->getId();
        }
    }

    public function getProuctVariantProperyId($proId, $optionId){
        $criteria   =   new Criteria();
        $context    =   Context::createDefaultContext();
        $criteria->addFilter(new EqualsFilter('productId', $proId));
        $criteria->addFilter(new EqualsFilter('optionId', $optionId));
        $setting = $this->productConfiguratorSettingRepo->search($criteria, $context)->first();

        if ($setting) {
            return $setting->getId();
        }
    }

    public function checkIfImpringConfigExist($imprintName){
        $criteria   =   new Criteria();
        $context    =   Context::createDefaultContext();
        $criteria->addFilter(new EqualsFilter('name', $imprintName));
        $setting = $this->neonConfiguratorRepo->search($criteria, $context)->first();
        if ($setting) {
            return $setting->getId();
        }
    }

    public function checkIfImpringConfigGrpExist($configId, $grpName){
        $criteria   =   new Criteria();
        $context    =   Context::createDefaultContext();
        $criteria->addFilter(new EqualsFilter('label', $grpName));
        $criteria->addFilter(new EqualsFilter('configuration_id', $configId));
        $setting = $this->neonConfiguratorRepo->search($criteria, $context)->first();
        if ($setting) {
            return $setting->getId();
        }
    }

    public function checkIfImpringGrpFieldExist($grpId, $fieldName){
        $criteria   =   new Criteria();
        $context    =   Context::createDefaultContext();
        $criteria->addFilter(new EqualsFilter('name', $imprintName));
        $setting = $this->neonConfiguratorRepo->search($criteria, $context)->first();
        if ($setting) {
            return $setting->getId();
        }
    }

    public function getProductCustomFieldSet(){
        return '01964842017a7e25b12daa356d58ae9a';
    }

    public function createCategory($name){
        $categoryNameArr    =   [];
        $categoryName       =   $name;
        //$parentCategoryId   =   $this->getRootCategory();
        $parentCategoryId   =   $this->getImportParentCat();
        $categoryId = Uuid::randomHex();
        $categoryData = [
            'id' => $categoryId,
            'name' => $name,
            'active' => true,
            'type' => 'page',
            'parentId' => $parentCategoryId
        ];
        $context    =   Context::createDefaultContext();
        $catData    =   $this->categoryRepository->create([$categoryData], $context);
        return $categoryId;
    }

    public function getRootCategory()
    {
        $salesChannelId     =   $this->getDefaultSalesChannel();
        $context = Context::createDefaultContext();
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $salesChannelId));
        $criteria->addAssociation('navigationCategoryId');
        $salesChannel = $this->salesChannelRepository->search($criteria, $context)->first();

        $categoryId = $salesChannel->getNavigationCategoryId();
        return $categoryId;
    }

    public function getImportParentCat(){
        return "01980cc2b7f6704fafdb4844bd5c097f";
    }

    public function getProductSheetFolderId(){
        return "0198754a77ad7e568324b0d4bdb83f63";
    }

    public function uploadProductSheet($sheetName){
        $basePath   =   $this->kernel->getProjectDir().'/my-imports/Harko/productsheets/';
        $imageUrl   =   $basePath.$sheetName;
        $folderId   =   $this->getProductSheetFolderId();

        if (!file_exists($imageUrl)) {
            return;
        }

        if (empty($fName)) {
            $fName  =   basename($imageUrl);
        }
        
        $context            =   Context::createDefaultContext();
        $tmpFilePath        =   $imageUrl;

        $originalFileName   =   pathinfo($tmpFilePath, PATHINFO_FILENAME);
        $extension          =   pathinfo($tmpFilePath, PATHINFO_EXTENSION);         
        
        $ifMediaExist       =   $this->imageExistsInFolder($originalFileName, $folderId, $context);

        if (!empty($ifMediaExist)) {
            return $ifMediaExist;
        } 

        // Generate new media ID
        $mediaId = Uuid::randomHex();

        // Create media entity
        $this->mediaRepository->create([[
            'id' => $mediaId,
            'name' => $originalFileName,
            'fileName' => $originalFileName,
            'mediaFolderId' => $folderId,
            'fileExtension' => $extension
        ]], $context);

        // Upload file
        $mediaFile = new MediaFile(
            $tmpFilePath,
            mime_content_type($tmpFilePath),
            $extension,
            filesize($tmpFilePath)
        );

        $this->mediaService->saveMediaFile($mediaFile, $originalFileName, $context, $folderId, $mediaId);

        unlink($tmpFilePath);
        return $mediaId; 
    }
    public function deleteMediaById(string $mediaId): void {
        $context = Context::createDefaultContext();
        $this->mediaRepository->delete([['id' => $mediaId]], $context);
    }
}
