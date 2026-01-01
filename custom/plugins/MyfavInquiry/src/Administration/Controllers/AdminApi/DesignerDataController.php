<?php declare(strict_types=1);

namespace Myfav\Inquiry\Administration\Controllers\AdminApi;

use Doctrine\DBAL\Connection;
use Myfav\Inquiry\Services\InquiryService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;

#[RouteScope(scopes: ['api'])]
#[Route('/api/myfav', name: 'api.myfav.')]

class DesignerDataController extends AbstractController
{
    private Connection $connection;

    private RequestStack $requestStack;

    private InquiryService $inquiryService;
    
    private EntityRepository $productRepository;

    public function __construct(
        Connection $connection,
        RequestStack $requestStack,
        InquiryService $inquiryService,
        EntityRepository $productRepository
    ) {
        $this->connection = $connection;
        $this->requestStack = $requestStack;
        $this->inquiryService = $inquiryService;
        $this->productRepository = $productRepository;
    }

    #[Route(path: '/designer/geturl', name: 'designer.geturl', methods: ['GET'])]
    public function getUrl(Context $context): JsonResponse
    {
        //echo $token = $this->createDesignerLoginToken();die;
        $token = '';
        $currentRequest = $this->requestStack->getCurrentRequest();
        $itemId = $currentRequest->query->get('itemId');
        $itemData = $this->getInquiryItems($context, $itemId);

        $extendedData = null;
        if ($itemData !== null) {
            $lineItems = $itemData->getLineItems();
            foreach ($lineItems as $lineItem) {
                if ($lineItem->getId() === $itemId) {
                    $extendedData = $lineItem->getExtendedData();
                }
            }
        }

        $url = '';
        if (is_string($extendedData)) {
            try {
                $extendedData = json_decode($extendedData, true);
                $url = $currentRequest->getSchemeAndHttpHost() .
                    '/lumise/editor.php?' .
                    'product_base=' . $extendedData['lumiseArticleId'] .
                    '&mvtoken=' . $token .
                    '&lumiseTmpCartId=' . $extendedData['lumiseTmpCartId'] .
                    '&lumiseShopwareDesignsId=' . $extendedData['lumiseShopwareDesignsId'] .
                    '&mode=admin';
            } catch (\Exception $e) {
                // Silently fail
            }
        }

        return new JsonResponse(['url' => $url]);
    }

    #[Route(path: '/designer/getItems', name: 'designer.get.items', methods: ['POST'])]
    public function getItems(Context $context, Request $request): JsonResponse
    {
        $inquiryId = $request->request->get('inquiryId');
        //$designerLoginToken = $this->getDesignerLoginTokenForTheDay();
        $designerLoginToken = '';
        $designerBaseUrl = $request->getSchemeAndHttpHost() . '/lumise/editor.php';
        $inquiry = $this->getInquiryItems($context, $inquiryId);

        $lineItemsExtendedData = [];
        if ($inquiry !== null) {
            foreach ($inquiry->getLineItems() as $lineItem) {
                $extendedData = $lineItem->getExtendedData();
                if (is_string($extendedData)) {
                    try {
                        $extendedData = json_decode($extendedData, true);
                        $extendedData['originalProductName'] = '';

                        if (!empty($extendedData['originalProductId'])) {
                            $originalProduct = $this->loadProduct($context, $extendedData['originalProductId']);
                            if ($originalProduct !== null) {
                                $extendedData['originalProductName'] = $originalProduct->getName();
                            }
                        }

                        $lineItemsExtendedData[$lineItem->getId()] = $extendedData;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }
        }

        return new JsonResponse([
            'designerLoginToken' => $designerLoginToken,
            'designerBaseUrl' => $designerBaseUrl,
            'lineItemsExtendedData' => $lineItemsExtendedData
        ]);
    }

    private function createDesignerLoginToken(): string
    {
        $id = bin2hex(random_bytes(20));
        $this->connection->createQueryBuilder()
            ->insert('lumise_shopware_login_token')
            ->setValue('id', '?')
            ->setValue('expires', '?')
            ->setParameter(0, $id)
            ->setParameter(1, date('Y-m-d H:i:s', strtotime('+1 day')))
            ->executeQuery();

        return $id;
    }

    private function getDesignerLoginTokenForTheDay(): string
    {
        $token = $this->loadDesignerLoginTokenForTheDay();
        return $token ?? $this->createDesignerLoginToken();
    }

    private function loadDesignerLoginTokenForTheDay(): ?string
    {
        $result = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('lumise_shopware_login_token')
            ->where('expires > ? AND expires < ?')
            ->setParameter(0, date('Y-m-d') . ' 00:00:00')
            ->setParameter(1, date('Y-m-d', strtotime('+1 day')) . ' 00:00:00')
            ->executeQuery()
            ->fetchAll();

        if (!is_array($result) || count($result) === 0) {
            return null;
        }

        return $result[0]['id'] ?? null;
    }

    private function loadProduct(Context $context, string $productId)
    {
        return $this->productRepository->search(new Criteria([$productId]), $context)->first();
    }

    private function getInquiryItems(Context $context, string $inquiryId): mixed
    {
        return $this->inquiryService->getInquiryItems($context, $inquiryId);
    }
}
