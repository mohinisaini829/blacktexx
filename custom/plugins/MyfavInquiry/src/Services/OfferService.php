<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Services;

use RuntimeException;
use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\Service\DocumentGenerator;
//use Shopware\Core\Checkout\Document\GeneratedDocument;
use Shopware\Core\Content\Document\DocumentGenerator\GeneratedDocument;
use Shopware\Core\Checkout\Document\Twig\DocumentTemplateRenderer;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\NumberRange\ValueGenerator\NumberRangeValueGeneratorInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer\InquiryOfferDefinition;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;
use Myfav\Inquiry\MyfavInquiry;

class OfferService
{
    protected EntityRepository $inquiryOfferRepository;
    protected MediaService $mediaService;
    protected NumberRangeValueGeneratorInterface $numberRangeValueGenerator;
    protected EntityRepository $inquiryRepository;
    protected DocumentTemplateRenderer $documentTemplateRenderer;
    protected DocumentGenerator $documentGenerator;
    protected EntityRepository $documentConfigRepository;

    public function __construct(
        EntityRepository $inquiryOfferRepository,
        EntityRepository $inquiryRepository,
        EntityRepository $documentConfigRepository,
        MediaService $mediaService,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        DocumentTemplateRenderer $documentTemplateRenderer,
        DocumentGenerator $documentGenerator
    )
    {
        $this->inquiryOfferRepository = $inquiryOfferRepository;
        $this->mediaService = $mediaService;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->inquiryRepository = $inquiryRepository;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
        $this->documentGenerator = $documentGenerator;
        $this->documentConfigRepository = $documentConfigRepository;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    public function createOffer(string $inquiryId, Context $context): string
    {
        // get offer number
        $offerNumber = $this->numberRangeValueGenerator->getValue(
            MyfavInquiry::OFFER_NUMBER_RANGE,
            $context,
            null
        );
        $filename = sprintf('offer_%s.pdf', $offerNumber);

        // create file
        $document = $this->createFile($inquiryId, $offerNumber, $filename, $context);

        $mediaId = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($document) : string {
            // save file as media
            return $this->mediaService->saveFile(
                $document->getFileBlob(),
                'pdf',
                $document->getContentType(),
                $document->getFileName(),
                $context,
                DocumentDefinition::ENTITY_NAME
            );
        });

        // create inquiry offer
         $primaryKeys = $this->inquiryOfferRepository->create([
            [
                'inquiryId' => $inquiryId,
                'offerNumber' => $offerNumber,
                'mediaId' => $mediaId,
            ]
        ], $context)->getPrimaryKeys(InquiryOfferDefinition::ENTITY_NAME);
        // return offer id
        return array_values($primaryKeys)[0];
    }

    /**
     * @throws RuntimeError
     * @throws SyntaxError
     * @throws LoaderError
     */
    private function createFile(string $inquiryId, string $offerNumber, string $filename, Context $context): GeneratedDocument
    {
        // create file
        $generatedDocument = new GeneratedDocument();
        $generatedDocument->setFilename($filename);
        $generatedDocument->setHtml($this->getHtml($inquiryId, $offerNumber, $context));
        $generatedDocument->setPageOrientation('portrait');
        $generatedDocument->setPageSize('a4');
        $generatedDocument->setContentType('application/pdf');
        $generatedDocument->setFileBlob($this->documentGenerator->generate($generatedDocument));
        return $generatedDocument;
    }

    /**
     * @throws SyntaxError
     * @throws RuntimeError
     * @throws LoaderError
     */
    protected function getHtml(string $inquiryId, string $offerNumber, Context $context): string
    {
        // get inquiry
        /** @var ?InquiryEntity $inquiry */
        $inquiry = $this->inquiryRepository->search(
            (new Criteria([$inquiryId]))
                ->addAssociations([
                    'lineItems.product',
                    'medias'
                ])
            ,$context
        )->first();
        if($inquiry === null) {
            throw new RuntimeException('Inquiry not found');
        }

        $config = $this->getConfiguration($context, $inquiry->getSalesChannelId());

        return $this->documentTemplateRenderer->render(
            'documents/myfav_inquiry_offer.html.twig',
            [
                'inquiry' => $inquiry,
                'offerNumber' => $offerNumber,
                'config' => DocumentConfigurationFactory::mergeConfiguration($config, new DocumentConfiguration())->jsonSerialize(),
                'context' => $context,
                'locale' => 'de-DE'
            ],
            $context,
            $inquiry->getSalesChannelId(),
            $context->getLanguageId(),
            'de-DE'
        );
    }

    protected function getConfiguration(
        Context $context,
        ?string $salesChannelId,
        ?array $specificConfiguration = null
    ): DocumentConfiguration {
        $specificConfiguration = $specificConfiguration ?? [];
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('name', 'invoice'));
        $criteria->addAssociation('logo');
        $criteria->addFilter(new EqualsFilter('global', true));

        /** @var DocumentBaseConfigEntity $globalConfig */
        $globalConfig = $this->documentConfigRepository->search($criteria, $context)->first();

        $salesChannelConfig = null;
        if($salesChannelId !== null) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('name', 'invoice'));
            $criteria->addAssociation('logo');
            $criteria->addFilter(new EqualsFilter('salesChannels.salesChannelId', $salesChannelId));

            $salesChannelConfig = $this->documentConfigRepository->search($criteria, $context)->first();
        }

        return DocumentConfigurationFactory::createConfiguration($specificConfiguration, $globalConfig, $salesChannelConfig);
    }

}
