<?php declare(strict_types=1);

namespace ArticleImport\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Context;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use ArticleImport\Helper\Data;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;

#[AsCommand(
    name: 'update:product-media',
    description: 'Update cover, variant swatch and gallery images from CSV (URL based)'
)]
class UpdateProductMedia extends Command
{
    private Data $importHelper;
    private EntityRepository $productRepository;
    private EntityRepository $configuratorRepo;

    public function __construct(
        Data $importHelper,
        #[Autowire('product.repository')] EntityRepository $productRepository,
        #[Autowire('product_configurator_setting.repository')]
        EntityRepository $configuratorRepo
    ){
        parent::__construct();
        $this->importHelper     = $importHelper;
        $this->productRepository = $productRepository;
        $this->configuratorRepo  = $configuratorRepo;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $csvPath = __DIR__ . '/csv/media-update.csv';
        $context = Context::createDefaultContext();

        if (!file_exists($csvPath)) {
            $output->writeln('<error>CSV not found</error>');
            return Command::FAILURE;
        }

        $handle  = fopen($csvPath, 'r');
        $headers = fgetcsv($handle, 0, ';');

        while (($row = fgetcsv($handle, 0, ';')) !== false) {

             if (count($row) < count($headers)) {
                $row = array_pad($row, count($headers), '');
            }

            if (count($row) !== count($headers)) {
                $output->writeln('<error>CSV column mismatch, skipping row</error>');
                continue;
            }

            $data = array_combine($headers, $row);

            $productNumber = trim($data['product_number'] ?? '');

            if (!$productNumber) {
                $output->writeln('<error>Product number missing in CSV row</error>');
                continue;
            }
            //print_r($data);die;

            $productNumber = trim(
            $data['product_number']
                ?? $data['productnumber']
                ?? $data['ProductNumber']
                ?? ''
            );

            if (!$productNumber) {
                $output->writeln('<error>Product number missing in CSV row</error>');
                continue;
            }

            $coverUrl      = trim($data['cover_media_url'] ?? '');
            $variantImage  = trim($data['variant_image'] ?? '');
            $galleryUrls   = array_filter(array_map('trim', explode('|', $data['gallery_images'] ?? '')));

            $productId = $this->importHelper->checkIfProductExist($productNumber);

            if (!$productId) {
                $output->writeln("<error>Product not found: {$productNumber}</error>");
                continue;
            }

            /* ================= COVER IMAGE ================= */
            /* ================= COVER IMAGE ================= */
            if ($coverUrl) {

                // 1️⃣ Get current product to find existing cover
                $criteria = new Criteria([$productId]);
                $criteria->addAssociation('cover.media');
                $product = $this->productRepository->search($criteria, $context)->first();

                // 2️⃣ Delete old cover media if exists
                $oldCoverId = $product->getCover()?->getMedia()?->getId();
                if ($oldCoverId) {
                    $this->importHelper->deleteMediaById($oldCoverId); // You need to implement this in your Helper
                }

                // 3️⃣ Upload new cover image
                $coverMediaId = $this->importHelper->uploadImageFromUrl(
                    $coverUrl,
                    $this->importHelper->getProductFolderId(),
                    basename(parse_url($coverUrl, PHP_URL_PATH))
                );
                //echo $coverMediaId;die('dfgdfgd');
                // 4️⃣ Update product with new cover
                if ($coverMediaId) {
                    //echo $coverMediaId;die('dfgdfgd');
                    $this->productRepository->update([[
                        'id' => $productId,
                        'coverId' => $coverMediaId
                    ]], $context);

                    $output->writeln("<info>✔ Cover image replaced for: {$productNumber}</info>");
                } else {
                    $output->writeln("<error>Failed to upload cover image: {$coverUrl}</error>");
                }
            }


            /* ================= VARIANT COLOR SWATCH ================= */
            /* ================= VARIANT COLOR SWATCH ================= */
            /*if ($variantImage) {
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('productId', $productId));
                $criteria->addAssociation('option.group');

                $settings = $this->configuratorRepo->search($criteria, $context);

                $upData = [];

                foreach ($settings as $setting) {
                    if (
                        $setting->getOption() &&
                        $setting->getOption()->getGroup() &&
                        strtolower($setting->getOption()->getGroup()->getName()) === 'color'
                    ) {
                        $mediaId = $this->importHelper->uploadImageFromUrl(
                            $variantImage,
                            $this->importHelper->getProductFolderId(),
                            basename(parse_url($variantImage, PHP_URL_PATH))
                        );

                        if ($mediaId) {
                            $upData[] = [
                                'id' => $setting->getId(),
                                'mediaId' => $mediaId
                            ];
                        }
                    }
                }

                if (!empty($upData)) {
                    $this->configuratorRepo->update($upData, $context);
                }
            }*/

            

            /* ================= GALLERY IMAGES ================= */
            if ($galleryUrls) {

                $gallery = [];
                $position = 1;

                foreach ($galleryUrls as $url) {

                    $mediaId = $this->importHelper->uploadImageFromUrl(
                        $url,
                        $this->importHelper->getProductFolderId(),
                        basename(parse_url($url, PHP_URL_PATH))
                    );

                    if ($mediaId) {
                        $gallery[] = [
                            'mediaId' => $mediaId,
                            'position' => $position++
                        ];
                    }
                }

                if ($gallery) {
                    $this->productRepository->update([[
                        'id' => $productId,
                        'media' => $gallery
                    ]], $context);
                }
            }

            $output->writeln("<info>✔ Media updated for: {$productNumber}</info>");
        }

        fclose($handle);
        return Command::SUCCESS;
    }
}
