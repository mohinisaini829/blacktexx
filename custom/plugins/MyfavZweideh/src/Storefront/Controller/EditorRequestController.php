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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\ParameterBag;

#[Route(defaults: ['_routeScope' => ['storefront']])]
/**
 * 
 * Dieser Controller ist für diesen Anwendungsfall zuständig:
 * Wenn ein User ein Design erstellt hat, und für dieses eine Anfrage über den
 * Designer stellen möchte, der User aber nicht eingeloggt ist,
 * sorgt dieser Controller nach dem Login oder der Registrierung dafür,
 * dass der User sauber an Lumise redirected wird,
 * zusammen mit den nötigen Parametern, um die Session wieder aufzunehmen,
 * und das Anfrage-Popup anzuzeigen.
 */
class EditorRequestController extends StorefrontController
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
        path: '/request-in-editor',
        name: 'frontend.myfav.zweideh.request.in.editor',
        methods: ['GET']
    )]
    public function requestInEditor(
        Request $request, 
        SalesChannelContext $salesChannelContext) : Response
    {
        $lumise_design_id = $request->query->get('lumise_design_id');
        $key = $request->query->get('key');
        $product = $request->query->get('product');

        $url = $salesChannelContext->getSalesChannel()->getDomains()->first()->getUrl();
        $url .= '/lumise/editor.php?product_base=' . $product . '&continue=' . $lumise_design_id . '&key=' . $key;

        header('Location: ' . $url, true, 302);
        die;
    }
}