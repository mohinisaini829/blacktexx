<?php declare(strict_types=1);

namespace Myfav\Inquiry\Services;

use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;

class InquiryMailService
{
    private AbstractMailService $mailService;
    private EntityRepositoryInterface $mailTemplateRepository;
    
    /**
     * __construct
     */
    public function __construct(
        AbstractMailService $mailService,
        EntityRepositoryInterface $mailTemplateRepository
    )
    {
        $this->mailService = $mailService;
        $this->mailTemplateRepository = $mailTemplateRepository;
    }

    /**
     * Method for sending an email notification
     *
     * @param array $recipients
     * @param string $senderName
     * @param SalesChannelContext|null $salesChannelContext
     * @return bool
     */
    public function sendMail(
        array $recipients,
        string $senderName,
        SalesChannelContext $salesChannelContext,
        $templateData
    ) : bool
    {
        //die('dasdsdasd');
        $data = new DataBag();

        //basic e-mail data
        // $data->set('recipients', $recipients);     //format: ['email address' => 'recipient name']
        $recipients = [
            //'steve@mindfav.com' => 'Steve Krämer',
            //'anfrage@santafetex.com' => 'anfrage@santafetex.com'
            'mohini.saini@emails.emizentech.com' => 'mohini.saini@emails.emizentech.com'

        ];
        $senderName = 'Santafetex Onlineshop';

        $data->set('recipients', $recipients);
        $data->set('senderName', $senderName);

        //set sales channel context
        $data->set('salesChannelId', $salesChannelContext->getSalesChannel()->getId());

        //set the template (not mandatory)
        $mailTemplate = $this->getMailTemplate(
            'myfav_inquiry_request',
            $salesChannelContext->getContext()
        );

        if(null === $mailTemplate) {
            throw new \Exception('E-Mail Template for InquiryMail was not found');
        }

        $data->set('templateId', $mailTemplate->getId());
        $data->set('subject', $mailTemplate->getSubject());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());

        //send the e-mail
        $result = $this->mailService->send(
            $data->all(),
            $salesChannelContext->getContext(),
            $templateData
        );

        if ($result) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Method for getting an email template by its ID or the first one available, if no ID is supplied
     *
     * @param string $technicalName
     * @param Context $context
     * @return MailTemplateEntity|null
     */
    private function getMailTemplate(string $technicalName, Context $context): ?MailTemplateEntity
    {
        //set the criteria for searching in the mail template repository
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('mailTemplateType.technicalName', $technicalName));
        $criteria->addAssociation('media.media');
        $criteria->addAssociation('mailTemplateType');
        $criteria->setLimit(1);

        //get and return one template
        return $this->mailTemplateRepository->search($criteria, $context)->first();
    }

}