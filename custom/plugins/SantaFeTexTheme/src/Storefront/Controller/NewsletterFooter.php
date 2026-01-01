<?php
declare(strict_types=1);

namespace SantaFeTexTheme\Storefront\Controller;

use Shopware\Core\Content\Newsletter\SalesChannel\AbstractNewsletterSubscribeRoute;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Shopware\Storefront\Framework\Routing\RequestTransformer;
use Shopware\Storefront\Page\GenericPageLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @RouteScope(scopes={"storefront"})
 */
class NewsletterFooter extends StorefrontController
{

    private AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute;

    public function __construct(AbstractNewsletterSubscribeRoute $newsletterSubscribeRoute)
    {
        $this->newsletterSubscribeRoute = $newsletterSubscribeRoute;
    }

    /**
     * @Route("/newsletter/forward/", name="frontend.newsletter.forward", methods={"POST"})
     */
    public function forwardToNewsletter(GenericPageLoader $pageLoader, RequestDataBag $dataBag, Request $request, SalesChannelContext $context): Response
    {
        try {

            $dataBag->set('storefrontUrl', $request->attributes->get(RequestTransformer::STOREFRONT_URL));

            $this->newsletterSubscribeRoute->subscribe($dataBag, $context, false);

            $response = [
                'type' => 'success',
                'alert' => [
                    $this->trans('newsletter.subscriptionPersistedSuccess'),
                    $this->trans('newsletter.subscriptionPersistedInfo')
                ]
            ];
        } catch (ConstraintViolationException $exception) {
            $errors = [];
            foreach ($exception->getViolations() as $error) {
                $errors[] = $error->getMessage();
            }
            $response = [
                'type' => 'danger',
                'alert' => $errors
            ];
        } catch (\Exception $exception) {
            $response = [
                'type' => 'danger',
                'alert' => [$this->trans('error.message-default')]
            ];
        }
        return $this->renderStorefront('@SantaFeTexTheme/storefront/santa-fe-tex/newsletter-footer.html.twig', [
            'response' => $response,
            'page' => $pageLoader->load($request, $context)
        ]);
    }
}
