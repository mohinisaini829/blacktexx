<?php declare(strict_types=1);

namespace Myfav\Inquiry\Core\Content\Flow\Dispatching\Action;

use Myfav\Inquiry\Services\InquiryMailService;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;

class SendMailAction extends FlowAction
{
    private InquiryMailService $inquiryMailService;
    private ContainerInterface $container;

    public function __construct(
        InquiryMailService $inquiryMailService,
        ContainerInterface $container
    ) {
        $this->inquiryMailService = $inquiryMailService;
        $this->container = $container;
    }

    public static function getName(): string
    {
        return 'action.send.inquiry';
    }

    public function requirements(): array
    {
        return []; // Add MailAware, OrderAware etc. if needed
    }

    public function handleFlow(StorableFlow $flow): void
    {
        $inquiry = $flow->getData('inquiry');
        if (!$inquiry) {
            file_put_contents('myfavtest.txt', 'No inquiry data found.');
            return;
        }

        $config = $flow->getConfig();
        $salesChannelContext = $flow->getSalesChannelContext();

        $recipients = [];
        $senderName = '';

        foreach ($config['tags'] ?? [] as $recipient) {
            if ($senderName === '') {
                $senderName = $recipient;
            }
            $recipients[$recipient] = $recipient;
        }

        // Optionally enrich line items
        $inquiry = $this->addAdditionalDataToInquiryLineItems($salesChannelContext, $inquiry);

        $this->inquiryMailService->sendMail(
            $recipients,
            $senderName,
            $salesChannelContext,
            ['inquiry' => $inquiry]
        );

        file_put_contents('myfavtest.txt', json_encode([
            'recipients' => $recipients,
            'config' => $config,
        ], JSON_PRETTY_PRINT));
    }

    private function addAdditionalDataToInquiryLineItems($salesChannelContext, $inquiry): mixed
    {
        $lineItems = $inquiry->getLineItems();

        foreach ($lineItems as $entry) {
            $productId = $entry->getProductId();

            if ($productId === null) {
                $extendedData = $entry->getExtendedData();

                $myfavZweidehDataLoaderService = $this->container->get(
                    'Myfav\Zweideh\Services\MyfavZweidehDataLoaderService',
                    ContainerInterface::IGNORE_ON_INVALID_REFERENCE
                );

                $myfavZweidehProductData = [];

                if ($myfavZweidehDataLoaderService !== null) {
                    $myfavZweidehProductData = $myfavZweidehDataLoaderService->loadByExtendedData(
                        $extendedData,
                        $salesChannelContext
                    );
                }

                $entry->addExtension(
                    'myfavZweidehProductData',
                    new ArrayEntity($myfavZweidehProductData)
                );
            }
        }

        return $inquiry;
    }
}
