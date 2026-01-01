<?php declare(strict_types=1);

namespace ArticleImport\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Shopware\Core\Defaults;
use ArticleImport\Helper\Data;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

#[AsCommand(
    name: 'import:category:assignment',
    description: 'Assign categories to product'
)]
class AssignCategoriesToProduct extends Command
{
    private SystemConfigService $systemConfigService;
    private $ImportHelper;
    private $productPriceRepository;
    private $categoryRepository;
    public $productConfiguratorSettingRepo;
    public $customFieldRepository;
    private $salesChannelRepository;
    private Connection $connection;

    public function __construct(
        SystemConfigService $systemConfigService,
        #[Autowire('product.repository')] 
        EntityRepository $productRepository,
        Data $importHelper,
        EntityRepository $productPriceRepository,
        EntityRepository $productConfiguratorSettingRepo,
        EntityRepository $customFieldRepository,
        Connection $connection,
        EntityRepository $categoryRepository,
        EntityRepository $salesChannelRepository
    ){
        $this->systemConfigService  =   $systemConfigService;
        $this->productRepository    =   $productRepository;
        $this->importHelper         =   $importHelper;
        $this->customFieldRepository            =   $customFieldRepository;
        $this->productPriceRepository           =   $productPriceRepository;
        $this->categoryRepository               =   $categoryRepository;
        $this->productConfiguratorSettingRepo   =   $productConfiguratorSettingRepo;
        $this->connection                       =   $connection;
        $this->salesChannelRepository           =   $salesChannelRepository;
        parent::__construct();
    }

    // Provides a description, printed out in bin/console
    protected function configure(): void
    {
        $this->setDescription('Assign categories to products. If categories not exist it will create.');
    }

    // Actual code executed in the command
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvPath = __DIR__ . '/csv/HAKRO-categoriess.csv';

        if (!file_exists($csvPath)) {
            $output->writeln('<error>CSV file not found at: ' . $csvPath . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Reading CSV from:</info> ' . $csvPath);

        $handle = fopen($csvPath, 'r');
        if (!$handle) {
            $output->writeln('<error>Unable to open the CSV file.</error>');
            return Command::FAILURE;
        }

        $headers = fgetcsv($handle);
        if (!$headers) {
            $output->writeln('<error>CSV file is empty or invalid.</error>');
            return Command::FAILURE;
        }

        $rowCount   =   1;

        $parentProIds   =   [];

        while (($row = fgetcsv($handle)) !== false) {
            $data           =   array_combine($headers, $row);
            $categoryId    =   $this->importHelper->findCategory($data['Category']);

            if (empty($categoryId)){
                $categoryId     =   $this->importHelper->createCategory($data['Category']);
            }

            if ($categoryId){
                $result   =   $this->assinCatToProduct($data['ItemCode'], $categoryId);
                if ($result['success'] === false){
                    $output->writeln($result['message']);
                } else {
                    $output->writeln("Category {$data['Category']} assigned to {$data['ItemCode']}");
                }
            }
        }
        fclose($handle);

        return Command::SUCCESS;
    }

    public function assinCatToProduct($itemCode, $categoryId){
        $criteria = new Criteria();
        $criteria->addAssociation('categories');
        $criteria->addFilter(new EqualsFilter('customFields.products_additional_data_itemcode', $itemCode));
        $product = $this->productRepository->search($criteria, Context::createDefaultContext())->first();

        $return     =   ["success" => true, "message"   => ''];

        if ($product){
            $existingCategories = $product->getCategories();
            $categoryIds = [];
            if (!empty($existingCategories)){
                foreach ($existingCategories as $cat) {

                    if ($categoryId == $cat->getId()){
                        $return     =   [
                                            "success" => false, 
                                            "message"   => "Category already assigned to {$itemCode}"
                                        ];
                        return $return;
                    }

                    $categoryIds[] = ['id' => $cat->getId()];
                }    
            }           
            $categoryIds[] = ['id' => $categoryId];

            try {
                $this->productRepository->update([[
                    'id' => $product->getId(),
                    'categories' => $categoryIds,
                ]], Context::createDefaultContext());
            } catch (\Exception $e) {
                $return     =   [
                                    "success" => false, 
                                    "message"   => $e->getMessage()
                                ];
                return $return;
            }
        } else {
            $return     =   [
                                "success" => false, 
                                "message"   => 'Product {$itemCode} not exist'
                            ];
            return $return;
        }
        return $return;
    }
}