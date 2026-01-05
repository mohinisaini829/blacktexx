<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Services;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;
use Shopware\Core\Checkout\Document\Aggregate\DocumentBaseConfig\DocumentBaseConfigEntity;
use Shopware\Core\Checkout\Document\DocumentConfiguration;
use Shopware\Core\Checkout\Document\DocumentConfigurationFactory;
use Shopware\Core\Checkout\Document\DocumentDefinition;
use Shopware\Core\Checkout\Document\Service\PdfRenderer;
use Shopware\Core\Checkout\Document\Renderer\RenderedDocument;
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
    protected EntityRepository $documentConfigRepository;

    public function __construct(
        EntityRepository $inquiryOfferRepository,
        EntityRepository $inquiryRepository,
        EntityRepository $documentConfigRepository,
        MediaService $mediaService,
        NumberRangeValueGeneratorInterface $numberRangeValueGenerator,
        DocumentTemplateRenderer $documentTemplateRenderer
    )
    {
        $this->inquiryOfferRepository = $inquiryOfferRepository;
        $this->mediaService = $mediaService;
        $this->numberRangeValueGenerator = $numberRangeValueGenerator;
        $this->inquiryRepository = $inquiryRepository;
        $this->documentTemplateRenderer = $documentTemplateRenderer;
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
        $pdfContent = $this->createFile($inquiryId, $offerNumber, $filename, $context);

        $mediaId = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($pdfContent, $filename) : string {
            // save file as media
            return $this->mediaService->saveFile(
                $pdfContent,
                'pdf',
                'application/pdf',
                $filename,
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
    private function createFile(string $inquiryId, string $offerNumber, string $filename, Context $context): string
    {
        // Get HTML content
        $html = $this->getHtml($inquiryId, $offerNumber, $context);
        
        // Generate PDF from HTML using Dompdf
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
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
