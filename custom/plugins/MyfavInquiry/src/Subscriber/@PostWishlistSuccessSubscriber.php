<?php
namespace Myfav\Inquiry\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class PostWishlistSuccessSubscriber implements EventSubscriberInterface
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::TERMINATE => 'onKernelTerminate',
        ];
    }

    public function onKernelTerminate(TerminateEvent $event): void
    {
        die('fsdfsdfsfsf');
        $request = $this->requestStack->getCurrentRequest();

        // Only run on your specific route
        if (!$request || $request->attributes->get('_route') !== 'frontend.myfav.wishlisted.finish') {
            return;
        }

        echo $inquiryId = $request->query->get('inquiryId');die;

        if ($inquiryId) {
            // ✅ Run your post-response logic here
            // e.g., DB update, API call, etc.
        }
    }
}
