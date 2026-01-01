<?php

namespace Myfav\Inquiry\Storefront\Controller;

use Shopware\Core\Content\Mail\Service\AbstractMailService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\Routing\Annotation\Route;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Validation\DataBag\DataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\Request;

/**
 * @RouteScope(scopes={"storefront"})
 */
class MailTestController extends StorefrontController
{
    private bool $activated = true;
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
     * @Route("myfav/send/test/mail", name="frontend.myfav.send.test.mail", methods={"GET"}, defaults={"XmlHttpRequest"=true})
     */
    public function sendTestMail(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $key = $request->query->get('k');

        if($key !== 'asdfasdfasdfhk') {
            die('invalid 1');
        }

        if($this->activated === false) {
            die('invalid 2');
        }

        $this->sendMail(
            ['steve@mindfav.com' => 'Steve Krämer'],
            'Santafetex Testcontroller E-Mail Versand',
            $salesChannelContext,
            $this->getTestVariables()
        );

        die('done');
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
        $data = new DataBag();

        //basic e-mail data
        $data->set('recipients', $recipients);     //format: ['email address' => 'recipient name']
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

    /**
     * getTestVariables
     *
     * @return array
     */
    private function getTestVariables(): array
    {
        return [
            'inquiry' => [
                'salutation' => [
                    'displayName' => 'Herr',
                ],
                'firstName' => 'Steve',
                'lastName' => 'Krämer',
                'email' => 'steve@mindfav.com',
                'company' => 'Test',
                'phoneNumber' => '01794594069',
                'deliveryDate' => time(),
                'comment' => 'Kommentar',
                'lineItems' => []
                //{\"salesChannel\":{\"extensions\":{\"foreignKeys\":{\"extensions\":[],\"apiAlias\":null}},\"_uniqueIdentifier\":\"3753908b0c0e4d7ba3e63879ece65150\",\"versionId\":null,\"translated\":{\"name\":\"santafetex\",\"customFields\":[],\"homeSlotConfig\":null,\"homeEnabled\":false,\"homeName\":null,\"homeMetaTitle\":\"Textilien besticken & bedrucken | SantaFeTex\",\"homeMetaDescription\":null,\"homeKeywords\":null},\"createdAt\":\"2021-03-10T13:59:20.697+00:00\",\"updatedAt\":\"2023-07-03T07:51:35.733+00:00\",\"typeId\":\"8a243080f92e4c719546314b577cf82b\",\"languageId\":\"2fbb5fe2e29a4d70aa5854ce7ce3e20b\",\"currencyId\":\"b7d2554b0ce847cd82f3ac9bd1c0dfca\",\"paymentMethodId\":\"6dc9b6ef124547f888aa11d20c84f32a\",\"shippingMethodId\":\"06e5cdf1cafc4385b5b469099e61773f\",\"countryId\":\"c393e46e9d3e4b4eb89bdb9559aa12eb\",\"navigationCategoryId\":\"2ce440376d434e1f8f78b053aaae7a26\",\"navigationCategoryVersionId\":\"0fa91ce3e96a4bc2be4bd9ce752c3425\",\"navigationCategoryDepth\":2,\"homeSlotConfig\":null,\"homeCmsPageId\":null,\"homeCmsPageVersionId\":\"0fa91ce3e96a4bc2be4bd9ce752c3425\",\"homeCmsPage\":null,\"homeEnabled\":false,\"homeName\":null,\"homeMetaTitle\":\"Textilien besticken & bedrucken | SantaFeTex\",\"homeMetaDescription\":null,\"homeKeywords\":null,\"footerCategoryId\":\"01e41d08a7fd4e4d8897cd592cba15d1\",\"footerCategoryVersionId\":\"0fa91ce3e96a4bc2be4bd9ce752c3425\",\"serviceCategoryId\":\"01e41d08a7fd4e4d8897cd592cba15d1\",\"serviceCategoryVersionId\":\"0fa91ce3e96a4bc2be4bd9ce752c3425\",\"name\":\"santafetex\",\"shortName\":null,\"accessKey\":\"SWSC8G4N3PFF49KCPXOLSKTAMA\",\"currencies\":null,\"languages\":null,\"configuration\":null,\"active\":true,\"maintenance\":false,\"maintenanceIpWhitelist\":[\"79.252.248.47\",\"80.128.203.74\",\"84.178.145.209\",\"195.4.210.183\",\"192.168.1.85\",\"77.179.24.250\",\"195.4.206.52\",\"78.94.41.118\",\"92.217.189.183\",\"93.129.252.196\",\" 80.128.219.172\",\"80.128.219.172\",\" 91.33.82.95\",\"37.4.225.221\",\"93.241.60.106\",\"79.249.218.180\",\"84.178.159.250\",\" 79.249.218.180\",\"91.36.224.45\",\"87.152.139.205\",\"95.90.200.54\",\"77.180.89.246\",\"212.185.188.98\",\"77.181.53.105\",\"87.189.208.106\",\"94.31.80.250\",\"62.226.122.72\",\"91.61.140.243\",\"87.152.138.141\",\"77.12.40.152\",\"88.152.9.54\",\"79.252.244.102\",\"80.153.5.86\"],\"taxCalculationType\":\"horizontal\",\"type\":null,\"currency\":null,\"language\":null,\"paymentMethod\":null,\"shippingMethod\":null,\"country\":null,\"orders\":null,\"customers\":null,\"countries\":null,\"paymentMethods\":null,\"shippingMethods\":null,\"translations\":null,\"domains\":[{\"extensions\":{\"foreignKeys\":{\"extensions\":[],\"apiAlias\":null}},\"_uniqueIdentifier\":\"2d5fcbca56364ed88825f9c44a31edda\",\"versionId\":null,\"translated\":[],\"createdAt\":\"2021-03-10T13:59:20.700+00:00\",\"updatedAt\":\"2023-05-13T18:13:52.327+00:00\",\"url\":\"https:\\/\\/dev16.mindfav.com\",\"currencyId\":\"b7d2554b0ce847cd82f3ac9bd1c0dfca\",\"currency\":null,\"snippetSetId\":\"1ac9d780088549b6aab37506fc7bf17f\",\"snippetSet\":null,\"salesChannelId\":\"3753908b0c0e4d7ba3e63879ece65150\",\"salesChannel\":null,\"languageId\":\"2fbb5fe2e29a4d70aa5854ce7ce3e20b\",\"language\":null,\"productExports\":null,\"salesChannelDefaultHreflang\":null,\"hreflangUseOnlyLocale\":false,\"id\":\"2d5fcbca56364ed88825f9c44a31edda\",\"customFields\":null},{\"extensions\":{\"foreignKeys\":{\"extensions\":[],\"apiAlias\":null}},\"_uniqueIdentifier\":\"429decb6d3b24fa1acd7ef2212611657\",\"versionId\":null,\"translated\":[],\"createdAt\":\"2021-03-10T13:59:20.700+00:00\",\"updatedAt\":\"2023-05-13T18:13:52.331+00:00\",\"url\":\"http:\\/\\/dev16.mindfav.com\",\"currencyId\":\"b7d2554b0ce847cd82f3ac9bd1c0dfca\",\"currency\":null,\"snippetSetId\":\"1ac9d780088549b6aab37506fc7bf17f\",\"snippetSet\":null,\"salesChannelId\":\"3753908b0c0e4d7ba3e63879ece65150\",\"salesChannel\":null,\"languageId\":\"2fbb5fe2e29a4d70aa5854ce7ce3e20b\",\"language\":null,\"productExports\":null,\"salesChannelDefaultHreflang\":null,\"hreflangUseOnlyLocale\":false,\"id\":\"429decb6d3b24fa1acd7ef2212611657\",\"customFields\":null}],\"systemConfigs\":null,\"navigationCategory\":null,\"footerCategory\":null,\"serviceCategory\":null,\"productVisibilities\":null,\"mailHeaderFooterId\":null,\"numberRangeSalesChannels\":null,\"mailHeaderFooter\":null,\"customerGroupId\":\"cfbd5018d38d41d8adca10d94fc8bdd6\",\"customerGroup\":null,\"newsletterRecipients\":null,\"promotionSalesChannels\":null,\"documentBaseConfigSalesChannels\":null,\"productReviews\":null,\"seoUrls\":null,\"seoUrlTemplates\":null,\"mainCategories\":null,\"paymentMethodIds\":[\"42836b5ac71f46938f77603509c2a59a\",\"6dc9b6ef124547f888aa11d20c84f32a\",\"a83c4fda24bd460bb8017ca51a8bc5fd\",\"b3d30da6cf5c4eb785a9774ef06c22f3\",\"efe15108a28a433c87aca9a2bff73bd0\"],\"productExports\":null,\"hreflangActive\":false,\"hreflangDefaultDomainId\":null,\"hreflangDefaultDomain\":null,\"analyticsId\":null,\"analytics\":null,\"customerGroupsRegistrations\":null,\"eventActions\":null,\"boundCustomers\":null,\"wishlists\":null,\"landingPages\":null,\"id\":\"3753908b0c0e4d7ba3e63879ece65150\",\"customFields\":[]}}\n"}} []
            ]
        ];
    }
}