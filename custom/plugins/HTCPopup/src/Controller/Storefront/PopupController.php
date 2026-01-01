<?php 
declare(strict_types=1);

namespace HTC\Popup\Controller\Storefront;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Log\Package;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\JsonResponse;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Defaults;

/**
 * @internal
 * Do not use direct or indirect repository calls in a controller. Always use a store-api route to get or put data
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
#[Package('storefront')]
class PopupController extends StorefrontController
{
    /**
     * @var EntityRepository $popupRepository
     */
    protected $popupRepository;
    /**
     * PopupController constructor.
     *
     * @param EntityRepository $popupRepository
     */
    public function __construct(
        EntityRepository $popupRepository
    ) {
        $this->popupRepository = $popupRepository;
    }

    #[Route(path: '/popup/updateCtr', name: 'frontend.popup.updateCtr', defaults: ['XmlHttpRequest' => true], methods: ['GET'])]
    public function updateCtr(Request $request, SalesChannelContext $context): JsonResponse
    {
        $status = true;
        $message = "";
        try {
            $popupId = $request->get('id');
            $mode = $request->get('mode');
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsAnyFilter('id', [$popupId]));
            $popup = $this->popupRepository->search($criteria, $context->getContext())->first();
            $currentClickNumber = $popup->getClick();
            $currentViewNumber = $popup->getView();
            if ($mode == 0) {
                // count click
                $newClickNumber = $currentClickNumber + 1;
                $newViewNumber = $currentViewNumber;    
            } else {
                // count view
                $newClickNumber = $currentClickNumber;
                $newViewNumber = $currentViewNumber + 1;
            }
            $newCtr = floatval(intval($newClickNumber * 100) / intval($newViewNumber));
            $this->popupRepository->update(
                [
                    [ 'id' => $popupId, 'view' => intval($newViewNumber), 'click' => intval($newClickNumber) , 'ctr' => $newCtr]
                ],
                \Shopware\Core\Framework\Context::createDefaultContext()
            );
            $message = "Success!";
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $status = false;
        }
        $result = [
            'status' => $status,
            'message' => $message
        ];
        return new JsonResponse(['data'=> $result]);
    }
}
