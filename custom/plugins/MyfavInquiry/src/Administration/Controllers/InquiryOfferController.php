<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Administration\Controllers;

use Shopware\Core\Content\Media\File\FileLoader;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Myfav\Inquiry\Entity\Inquiry\Aggregate\InquiryOffer\InquiryOfferEntity;
use Myfav\Inquiry\Services\OfferService;

#[Route(defaults: ['_routeScope' => ['api']])]
class InquiryOfferController extends AbstractController
{

    protected OfferService $offerService;
    protected EntityRepository $inquiryOfferRepository;
    protected FileLoader $fileLoader;

    public function __construct(
        OfferService $offerService,
        EntityRepository $inquiryOfferRepository,
        FileLoader $fileLoader
    )
    {
        $this->offerService = $offerService;
        $this->inquiryOfferRepository = $inquiryOfferRepository;
        $this->fileLoader = $fileLoader;
    }

    
    #[Route(
        path: '/api/_action/myfav_inquiry_offer/create/{inquiryId}',
        name: 'api.myfav_inquiry.offer.create',
        methods: ['POST'],
        requirements: ['inquiryId' => '\w{32}']
    )]
    public function create(string $inquiryId, Context $context): Response
    {
        $offerId = $this->offerService->createOffer($inquiryId, $context);
        return $this->json(['offerId' => $offerId]);
    }


    
     #[Route(
        path: '/api/_action/myfav_inquiry_offer/download/{offerId}',
        name: 'api.myfav_inquiry.offer.download',
        methods: ['GET'],
        requirements: ['offerId' => '\w{32}']
    )]
    public function download(string $offerId, Context $context): Response
    {
        return $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($offerId) : Response {
            /** @var InquiryOfferEntity $offer */
            $offer = $this->inquiryOfferRepository->search(
                (new Criteria([$offerId]))
                    ->addAssociation('media')
                , $context)
                ->first();

            if ($offer === null) {
                throw new NotFoundHttpException(sprintf('"%s" isn\'t a valid offer id!', $offerId));
            }
            if($offer->getMedia() === null) {
                throw new BadRequestException(sprintf('Offer "%s" has no media!', $offerId));
            }

            $content = $this->fileLoader->loadMediaFile($offer->getMediaId(), $context);
            $response = new Response($content);
            $disposition = HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_ATTACHMENT,
                $offer->getMedia()->getFileName()
            );
            $response->headers->set('Content-Disposition', $disposition);
            return $response;
        });
    }
}
