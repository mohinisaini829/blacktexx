<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Doctrine\DBAL\Connection;
use Myfav\Inquiry\Services\InquiryCartService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Context;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
 
#[Route(defaults: ['_routeScope' => ['storefront']])]
class LumiseAdminController extends StorefrontController
{
    private Connection $connection;

    public function __construct(
        Connection $connection
    ) {
        $this->connection = $connection;
    }

    
    #[Route(
        path: '/myfavDesignerAdmin/updateInquiryDesign',
        name: 'frontend.myfav.designer.admin.update.inquiry.design',
        methods: ['POST'],
        defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false]
    )]
    public function updateInquiryDesign(Request $request, SalesChannelContext $salesChannelContext): JsonResponse {
        $saveMethod = $request->request->get('saveMethod');
        $oldLumiseShopwareDesignsId = $request->request->get('oldLumiseShopwareDesignsId');
        $oldLumiseTmpCartId = $request->request->get('oldLumiseTmpCartId');
        $lumiseShopwareDesignsId = $request->request->get('lumiseShopwareDesignsId');
        $lumiseTmpCartId = $request->request->get('lumiseTmpCartId');
        $inquiryId = $request->request->get('inquiryId');
        $inquiryItemId = $request->request->get('inquiryItemId');
        $mvtoken = $request->request->get('mvtoken');

        if(false === $this->checkToken($mvtoken, $salesChannelContext)) {
            throw new \Exception('Not allowed');
        }

        if($saveMethod == 'saveSinglePosition') {
            $this->updateInquiryPosition(
                $oldLumiseShopwareDesignsId,
                $oldLumiseTmpCartId,
                $lumiseShopwareDesignsId,
                $lumiseTmpCartId,
                $inquiryItemId
            );
        } else if($saveMethod == 'saveAllPositions') {
            $this->updateInquiryMultiplePositions(
                $oldLumiseShopwareDesignsId,
                $oldLumiseTmpCartId,
                $lumiseShopwareDesignsId,
                $lumiseTmpCartId,
                $inquiryId
            );
        }

        return new JsonResponse([
            'status' => 'success'
        ]);
    }
    
    /**
     * checkToken
     *
     * @param  mixed $mvtoken
     * @return bool
     */
    private function checkToken($mvtoken): bool
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('lumise_shopware_login_token', 'lslt');
        $queryBuilder->where('lslt.id = ? and expires > NOW()');
        $queryBuilder->setParameter(0, $mvtoken);
        $result = $queryBuilder->execute()->fetchAll();

        if (!is_array($result) || count($result) == 0) {
            return false;
        }

        $result = $result[0];

        // Versuche erweiterte Daten auszulesen.
        $id = $result['id'];

        if($id === null) {
            return false;
        }

        return true;
    }
    
    /**
     * updateInquiry
     */
    private function updateInquiryPosition(
        $oldLumiseShopwareDesignsId,
        $oldLumiseTmpCartId,
        $lumiseShopwareDesignsId,
        $lumiseTmpCartId,
        $inquiryItemId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('ili.*');
        $queryBuilder->from('myfav_inquiry_line_item', 'ili');
        $queryBuilder->where('ili.id = UNHEX(?)');
        $queryBuilder->setParameter(0, $inquiryItemId);
        $result = $queryBuilder->execute()->fetchAll();

        if (!is_array($result) || count($result) == 0) {
            return;
        }

        $result = $result[0];

        // Versuche erweiterte Daten auszulesen.
        $extendedData = $result['extended_data'];

        if($extendedData === null) {
            return;
        }

        $extendedData = json_decode($extendedData, true);

        // Prüfe Daten
        if($extendedData['lumiseShopwareDesignsId'] != $oldLumiseShopwareDesignsId) {
            return;
        }

        if($extendedData['lumiseTmpCartId'] != $oldLumiseTmpCartId) {
            return;
        }

        // Aktualisiere Daten.
        $extendedData['lumiseShopwareDesignsId'] = $lumiseShopwareDesignsId;
        $extendedData['lumiseTmpCartId'] = $lumiseTmpCartId;
        $extendedData = json_encode($extendedData);

        // Speichere aktualisierte Daten in Datenbank.
        $queryBuilder = $this->connection->createQueryBuilder();

        $queryBuilder->update('myfav_inquiry_line_item', 'ili');
        $queryBuilder->set('ili.extended_data', ':extended_data');
        $queryBuilder->where('ili.id = UNHEX(:id)');
        $queryBuilder->setParameter('extended_data', $extendedData);
        $queryBuilder->setParameter('id', $inquiryItemId);
        $result = $queryBuilder->execute();
    }
    
    /**
     * updateInquiry
     */
    private function updateInquiryMultiplePositions(
        $oldLumiseShopwareDesignsId,
        $oldLumiseTmpCartId,
        $lumiseShopwareDesignsId,
        $lumiseTmpCartId,
        $inquiryId)
    {
        $queryBuilder = $this->connection->createQueryBuilder();
        $queryBuilder->select('*');
        $queryBuilder->from('myfav_inquiry_line_item', 'ili');
        $queryBuilder->where('ili.inquiry_id = UNHEX(?)');
        $queryBuilder->setParameter(0, $inquiryId);
        $results = $queryBuilder->execute()->fetchAll();

        if (!is_array($results) || count($results) == 0) {
            return;
        }

        foreach($results as $result) {
            // Versuche erweiterte Daten auszulesen.
            $extendedData = $result['extended_data'];

            if($extendedData === null) {
                die('continue1');
                continue;
            }

            $extendedData = json_decode($extendedData, true);

            // Prüfe Daten
            if($extendedData['lumiseShopwareDesignsId'] != $oldLumiseShopwareDesignsId) {
                die('continue2');
                continue;
            }

            if($extendedData['lumiseTmpCartId'] != $oldLumiseTmpCartId) {
                die('continue3');
                continue;
            }

            // Aktualisiere Daten.
            $extendedData['lumiseShopwareDesignsId'] = $lumiseShopwareDesignsId;
            $extendedData['lumiseTmpCartId'] = $lumiseTmpCartId;
            $extendedData = json_encode($extendedData);

            // Speichere aktualisierte Daten in Datenbank.
            $queryBuilder = $this->connection->createQueryBuilder();

            $queryBuilder->update('myfav_inquiry_line_item', 'ili');
            $queryBuilder->set('ili.extended_data', ':extended_data');
            $queryBuilder->where('ili.id = :id');
            $queryBuilder->setParameter('extended_data', $extendedData);
            $queryBuilder->setParameter('id', $result['id']);
            $result = $queryBuilder->execute();
        }
    }
}