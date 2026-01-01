<?php declare(strict_types=1);

namespace Myfav\Zweideh\Storefront\Subscriber;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Subscriber;
use Symfony\Component\HttpFoundation\RequestStack;

class ArticleDetailSubscriber implements EventSubscriberInterface {
	private $systemConfigService;
	private $requestStack;
	
	public function __construct(
		SystemConfigService $systemConfigService,
		RequestStack $requestStack
	)
	{
		$this->systemConfigService = $systemConfigService;
		$this->requestStack = $requestStack;
	}

	public static function getSubscribedEvents()
	{
		return [
			ProductPageLoadedEvent::class => 'onProductPageLoaded'
		];
	}
	
	public function onProductPageLoaded(ProductPageLoadedEvent $event): void
	{
		/*
		$saleschannelContext = $event->getSaleschannelContext();
		$customer = $saleschannelContext->getCustomer();
		
		if(NULL === $customer) {
			$customer = 'not-logged-in';
		}
		*/
		
		// Get a get param.
		/*
        $request = $this->requestStack->getCurrentRequest();
		$myfavObilityStart = $request->get('myfav-obility-start');
				
		$event->getPage()->addExtension(
			'myfav_obility', new ArrayStruct([
				'myfav_obility_start' => $myfavObilityStart
			])
		);
        */

        $event->getPage()->addExtension(
			'myfav_zweideh', new ArrayStruct([
				'test' => 'test'
			])
		);
	}
}