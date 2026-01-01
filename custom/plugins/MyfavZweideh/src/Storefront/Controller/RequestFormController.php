<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Controller;

use Myfav\Zweideh\MyfavZweideh;
use Myfav\Zweideh\Services\DesignRequestService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ParameterBag;


#[Route(defaults: ['_routeScope' => ['storefront']])]
class RequestFormController extends StorefrontController
{
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $mailTemplateRepository;
    private MailService $mailService;
    private $logger;
    private DesignRequestService $designRequestService;
    private $customer = null;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $mailTemplateRepository,
        AbstractMailService $mailService,
        LoggerInterface $logger,
        DesignRequestService $designRequestService)
    {
        $this->systemConfigService = $systemConfigService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->mailService = $mailService;
        $this->logger = $logger;
        $this->designRequestService = $designRequestService;
    }

    
    #[Route(
        path: '/myfav-designer-request-form',
        name: 'frontend.myfav.designer.request.form',
        methods: ['POST'],
        defaults: ['XmlHttpRequest' => true, 'csrf_protected' => false]
    )]
    public function submitRequestForm(
        Request $request, 
        SalesChannelContext $salesChannelContext) : JsonResponse
    {
        $quantity = $request->request->get('quantity');
        $freetext = $request->request->get('freetext');
        $tmp_cart_id = $request->request->get('tmp_cart_id');
        $key = $request->request->get('key');
        $tmp = $quantity. '-' . $freetext . '-' . $tmp_cart_id . '-' . $key . '-' . time() . '-' . uniqid();
        
        $data = $this->designRequestService->createDesignRequestFromTmpCart($salesChannelContext, $key, $tmp_cart_id);
        $mailImage = $data['dstPreviewJpgFilepath'];
        
        $status = false;
        $this->customer = $salesChannelContext->getCustomer();
        
        if(null === $this->customer) {
            die('Do not forget parameters for redirecting to purchase, but send to login screen!');
        }

        $mailTemplate = $this->getMailTemplate($salesChannelContext);

        if(null === $mailTemplate) {
            $data = array(
                'status' => 'error',
                'errors' => [ 1 ],
                'tmp' => $tmp
            );

            return new JsonResponse($data);
        }
        
        try {
            $status = $this->sendMail($mailTemplate, $salesChannelContext, $quantity, $freetext, $tmp, $tmp_cart_id, $key);
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        if($status === true) {
            $data = array(
                'status' => 'success',
                'tmp' => $tmp,
                //'data' => $data
            );

            return new JsonResponse($data);
        }

        $data = array(
            'status' => 'error',
            'errors' => [ 2 ],
            'tmp' => $tmp
        );

        return new JsonResponse($data);
    }

    /**
    * E-Mail Template ermitteln.
    */
    private function getMailTemplate(
        SalesChannelContext $salesChannelContext): mixed
    {
        try {
            $mailTemplateId = $this->systemConfigService->get(
                MyfavZweideh::PLUGIN_CONFIG . 'requestFormMail',
                $salesChannelContext->getSalesChannelId()
            );

            if (!$mailTemplateId) {
                $this->logger->error('Missing Mail template for requestFormMail in ' . __FILE__ . ', Line: ' . __LINE__);
                return null;
            }

            $criteria = new Criteria([$mailTemplateId]);
            $criteria->addAssociation('translations');

            /** @var MailTemplateEntity|null $mailTemplate */
            $mailTemplate = $this->mailTemplateRepository->search($criteria, $salesChannelContext->getContext())->first();
            
            return $mailTemplate;
        } catch (Exception $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    /**
     * E-Mail versenden.
     */
    private function sendMail(
        MailTemplateEntity $mailTemplate,
        SalesChannelContext $salesChannelContext,
        $quantity,
        $freetext, 
        $tmp,
        $tmp_cart_id,
        $key): bool
    {
        $mailTranslations = $mailTemplate->getTranslations();
        
        if ($mailTranslations === null) {
            return false;
        }

        $mailTemplateTranslated = $mailTranslations->filterByLanguageId($this->customer->getLanguageId())->first();

        if ($mailTemplateTranslated === null) {
            $mailTemplateTranslated = $mailTranslations->first();
        }

        if($mailTemplateTranslated === null) {
            return false;
        }

        $data = new ParameterBag();

        $mailReceiver = $this->systemConfigService->get('core.basicInformation.email', $salesChannelContext->getSalesChannel()->getId());
        $receivers[$mailReceiver] = $mailReceiver;

        $data->set(
            'recipients',
            $receivers
        );

        $sender = $mailTemplateTranslated->getSenderName() ?? $mailTemplate->getTranslation('senderName');
        $subject = $mailTemplateTranslated->getSubject() ?? $mailTemplate->getTranslation('subject');

        $data->set('senderName', $sender);
        $data->set('salesChannelId', $salesChannelContext->getSalesChannel()->getId());
        $data->set('templateId', $mailTemplate->getId());
        $data->set('contentHtml', $mailTemplateTranslated->getContentHtml());
        $data->set('contentPlain', $mailTemplateTranslated->getContentPlain());
        $data->set('subject', $subject);

        $this->mailService->send(
            $data->all(),
            $salesChannelContext->getContext(),
            [
                'customer' => $this->customer,
                'quantity' => $quantity,
                'freetext' => htmlspecialchars($freetext),
                'tmp_cart_id' => $tmp_cart_id,
                'key' => $key
            ]
        );

        return true;
    }
}