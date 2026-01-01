<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Event;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\EventData\EntityType;
use Shopware\Core\Framework\Event\EventData\EventDataCollection;
use Shopware\Core\Framework\Event\MailAware;
use Shopware\Core\Framework\Event\SalesChannelAware;
use Shopware\Core\Framework\Event\FlowEventAware;
//use Shopware\Core\Framework\Event\MailRecipientStruct;
use Shopware\Core\Framework\Event\EventData\MailRecipientStruct; // ✅ CORRECT
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Contracts\EventDispatcher\Event;
use Myfav\Inquiry\Entity\Inquiry\InquiryDefinition;
use Myfav\Inquiry\Entity\Inquiry\InquiryEntity;

class InquirySendEvent extends Event implements FlowEventAware, MailAware, SalesChannelAware
{
    public const EVENT_NAME = 'inquiry.send';

    private SalesChannelContext $salesChannelContext;
    private InquiryEntity $inquiry;
    private string $salesChannelId;
    private ?MailRecipientStruct $mailRecipientStruct;

    public function __construct(
        SalesChannelContext $salesChannelContext,
        InquiryEntity $inquiry,
        string $salesChannelId,
        ?MailRecipientStruct $mailRecipientStruct = null
    ) {
        $this->salesChannelContext = $salesChannelContext;
        $this->inquiry = $inquiry;
        $this->salesChannelId = $salesChannelId;
        $this->mailRecipientStruct = $mailRecipientStruct;
    }

    public static function getAvailableData(): EventDataCollection
    {
        return (new EventDataCollection())
            ->add('inquiry', new EntityType(InquiryDefinition::class));
    }

    public function getName(): string
    {
        //die('ggggggggggggggggggggggggggggggg');
        return self::EVENT_NAME;
    }

    public function getMailStruct(): MailRecipientStruct
    {
        if (!$this->mailRecipientStruct instanceof MailRecipientStruct) {
            $this->mailRecipientStruct = new MailRecipientStruct([
                $this->inquiry->getEmail() => $this->inquiry->getFirstName() . ' ' . $this->inquiry->getLastName(),
            ]);
        }

        return $this->mailRecipientStruct;
    }

    public function getContext(): Context
    {
        return $this->salesChannelContext->getContext();
    }

    public function getSalesChannelId(): string
    {
        return $this->salesChannelId;
    }

    public function getSalesChannelContext(): SalesChannelContext
    {
        return $this->salesChannelContext;
    }

    public function getInquiry(): InquiryEntity
    {
        return $this->inquiry;
    }
}
