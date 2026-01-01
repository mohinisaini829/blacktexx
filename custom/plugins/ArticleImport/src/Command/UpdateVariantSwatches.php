<?php declare(strict_types=1);

namespace ArticleImport\Command;

use ArticleImport\Helper\Data;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(
    name: 'update:variant-swatches',
    description: 'Upload swatch images once per parent per color'
)]
class UpdateVariantSwatches extends Command
{
    private Data $helper;
    private EntityRepository $productRepository;
    private EntityRepository $configuratorRepo;

    public function __construct(
        Data $helper,
        #[Autowire('product.repository')] EntityRepository $productRepository,
        #[Autowire('product_configurator_setting.repository')] EntityRepository $configuratorRepo
    ) {
        parent::__construct();
        $this->helper = $helper;
        $this->productRepository = $productRepository;
        $this->configuratorRepo = $configuratorRepo;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $context = Context::createDefaultContext();
        $csvPath = __DIR__ . '/csv/swatch-images.csv';

        if (!file_exists($csvPath)) {
            $output->writeln('<error>CSV not found</error>');
            return Command::FAILURE;
        }

        $handle = fopen($csvPath, 'r');
        $headers = fgetcsv($handle, 0, ';');

        /** parent+color dedupe */
        $processedParentColors = [];

        while (($row = fgetcsv($handle, 0, ';')) !== false) {

            $data = array_combine($headers, $row);

            $productNumber = trim($data['product_number'] ?? '');
            $colorData     = trim($data['variants_image'] ?? '');

            if (!$productNumber || !$colorData || strpos($colorData, '|') === false) {
                continue;
            }

            [$colorName, $imageUrl] = array_map('trim', explode('|', $colorData, 2));
            if (!$colorName || !$imageUrl) continue;

            /** variant product */
            $variantId = $this->helper->checkIfProductExist($productNumber);
            if (!$variantId) continue;

            /** parent id from variant */
            $criteria = new Criteria([$variantId]);
            $criteria->addFields(['parentId']);

            $variant = $this->productRepository->search($criteria, $context)->first();
            if (!$variant || !$variant->get('parentId')) continue;

            //$parentId = $variant->getParentId();
            $parentId = $variant->get('parentId');


            /** dedupe */
            $key = $parentId . '|' . strtolower($colorName);
            if (isset($processedParentColors[$key])) continue;

            /** upload swatch */
            $mediaId = $this->helper->uploadImageFromUrl(
                $imageUrl,
                $this->helper->getProductFolderId(),
                basename(parse_url($imageUrl, PHP_URL_PATH))
            );
            //die('jjjjj');
            if (!$mediaId) continue;

            /** color option */
            $optionId = $this->helper->getPropertyOptionId($colorName, 'Color');
            if (!$optionId) continue;

            /** configurator setting */
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('productId', $parentId));
            $criteria->addFilter(new EqualsFilter('optionId', $optionId));

            $setting = $this->configuratorRepo->search($criteria, $context)->first();
            if (!$setting) continue;

            $this->configuratorRepo->update([[
                'id' => $setting->getId(),
                'mediaId' => $mediaId
            ]], $context);

            $processedParentColors[$key] = true;
        }


        fclose($handle);
        return Command::SUCCESS;
    }

    private function getParentId(string $productId, Context $context): ?string
    {
        $criteria = new Criteria([$productId]);
        $criteria->addAssociation('parent');
        $product = $this->productRepository->search($criteria, $context)->first();
        return $product?->getParentId() ?? $productId;
    }
}
