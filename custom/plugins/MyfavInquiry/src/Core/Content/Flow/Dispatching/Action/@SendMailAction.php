<?php declare(strict_types=1);

namespace Myfav\Inquiry\Core\Content\Flow\Dispatching\Action;

use Myfav\Inquiry\Core\Framework\Event\TagAware;
use MYfav\Inquiry\Event\InquirySendEvent;
use Myfav\Inquiry\Services\InquiryMailService;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Event\FlowEvent;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * SendMailAction
 */
class SendMailAction extends FlowAction
{
    private InquiryMailService $inquiryMailService;
    //private MyfavZweidehDataLoaderService $myfavZweidehDataLoaderService;
    private $container;
    
    /**
     * __construct
     *
     * @param  mixed $inquiryMailService
     * @return void
     */
    public function __construct(
        InquiryMailService $inquiryMailService,
        //MyfavZweidehDataLoaderService $myfavZweidehDataLoaderService)
        $container)
    {
        // you would need this repository to create a tag
        $this->inquiryMailService = $inquiryMailService;
        //$this->myfavZweidehDataLoaderService = $myfavZweidehDataLoaderService;
        $this->container = $container;
    }
    
    /**
     * getName
     *
     * @return string
     */
    public static function getName(): string
    {
        // your own action name
        return 'action.send.inquiry';
    }
    
    /**
     * getSubscribedEvents
     *
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            self::getName() => 'handle',
        ];
    }
    
    /**
     * requirements
     *
     * @return array
     */
    public function requirements(): array
    {
        return [TagAware::class];
    }
    
    /**
     * handle
     *
     * @param  mixed $event
     * @return void
     */
    public function handle(FlowEvent $event): void
    {
        /** @var InquirySendEvent $inquirySendEvent */
        $inquirySendEvent = $event->getEvent();

        // just a step to make sure you are dispatching the correct action
        if (get_class($inquirySendEvent) !== 'Myfav\Inquiry\Event\InquirySendEvent' ) {
            $fp = fopen('myfavtest.txt', 'w');
            fwrite($fp, 'something went terribly wrong!!!' . get_class($inquirySendEvent));
            fclose($fp);
            
            return;
        }

        $salesChannelContext = $inquirySendEvent->getSalesChannelContext();
        
        // config is the "Configuration data" you get after you create a flow sequence
        // This holds the email addresses of the configured receivers in the flow builder action.
        $config = $event->getConfig();

        $tmp = json_encode($config, JSON_PRETTY_PRINT);

        
        // This holds the inquiry data form the inquiry.
        $inquiry = $inquirySendEvent->getInquiry();

        $recipients = [];
        $senderName = "";

        foreach($config['tags'] as $recipient) {
            // Use first email entry as sender name.
            // Probably could be changed to sales channel or shop name in the future.
            if($senderName == "") {
                $senderName = $recipient;
            }

            $recipients[$recipient] = $recipient;
        }

        // Add additional data to the inquiry lineItems.
        $inquiry = $this->addAdditionalDataToInquiryLineItems($salesChannelContext, $inquiry);

        // Send mail.
        $this->inquiryMailService->sendMail(
            $recipients,
            $senderName,
            $salesChannelContext,
            [ 'inquiry' => $inquiry ]
        );

        $tmp .= json_encode($inquiry, JSON_PRETTY_PRINT);

        $fp = fopen('myfavtest.txt', 'w');
        fwrite($fp, $tmp);
        fclose($fp);

        // TODO: Send mail here?!
    }
    
    /**
     * addAdditionalDataToInquiryLineItems
     *
     * @param  mixed $inquiry
     * @return mixed
     */
    private function addAdditionalDataToInquiryLineItems($salesChannelContext, $inquiry): mixed
    {
        $lineItems = $inquiry->getLineItems();

        foreach($lineItems as $entry) {

            $productId = $entry->getProductId();

            if($productId === null) {
                $extendedData = $entry->getExtendedData();

                $myfavZweidehDataLoaderService = $this->container->get('Myfav\Zweideh\Services\MyfavZweidehDataLoaderService', ContainerInterface::IGNORE_ON_INVALID_REFERENCE);

                $myfavZweidehProductData = [];

                if(null !== $myfavZweidehDataLoaderService) {
                    $myfavZweidehProductData = $myfavZweidehDataLoaderService->loadByExtendedData($extendedData, $salesChannelContext);
                }

                $entry->addExtension(
                    'myfavZweidehProductData',
                    new ArrayEntity($myfavZweidehProductData)
                );
            }
        }

        return $inquiry;
    }
    // 🔥 This is the missing method causing the FatalError
    public function handleFlow(FlowEvent $event): void
    {
        $this->handle($event);
    }
}