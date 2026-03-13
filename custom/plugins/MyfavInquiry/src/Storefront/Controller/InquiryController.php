<?php

declare(strict_types=1);

namespace Myfav\Inquiry\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Myfav\Inquiry\Services\InquiryCartService;
use Myfav\Inquiry\Services\InquiryService;
use Myfav\Inquiry\Storefront\Page\InquiryConfirm\InquiryConfirmPageLoader;
use Myfav\Inquiry\Storefront\Page\InquiryFinish\InquiryFinishPageLoader;
use Shopware\Core\PlatformRequest;
use Shopware\Storefront\Framework\Routing\StorefrontRouteScope;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Mail\Service\MailService;
use Shopware\Core\Content\MailTemplate\MailTemplateEntity;   
//use Shopware\Core\Framework\Struct\DataBag;
use Shopware\Core\Framework\Validation\DataBag\DataBag;

use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository; // For mail template repository
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
//use Shopware\Core\System\Mail\Aggregate\MailTemplate\MailTemplateEntity;
use Shopware\Core\Framework\Context;
use Twig\Environment;
#[RouteScope(scopes: ['storefront'])]

class InquiryController extends StorefrontController
{

private InquiryCartService $inquiryCartService;
private InquiryService $inquiryService;
private Environment $twig;
private Connection $connection; // ✅ Add this
private MailService $mailService; // ✅ Add this
private EntityRepository $mailTemplateRepository; // ✅ Add this
private EntityRepository $productRepository;

    public function __construct(
        InquiryCartService $inquiryCartService,
        InquiryService $inquiryService,
        Environment $twig,
        Connection $connection,
        MailService $mailService,
        EntityRepository $mailTemplateRepository,
        EntityRepository $productRepository

    )
    {
        $this->inquiryCartService = $inquiryCartService;
        $this->inquiryService = $inquiryService;
        $this->twig = $twig;
        $this->connection = $connection;
        $this->mailService = $mailService;
        $this->mailTemplateRepository = $mailTemplateRepository;
        $this->productRepository = $productRepository;
    }

    
    #[Route(
        '/myfav/inquiry/add',
        name: 'frontend.myfav.inquiry.add',
        methods: ['POST'],
        defaults: [
            '_routeScope' => ['storefront'], // yeh zaruri hai
            'csrf_protected' => false,       // agar XHR request aur CSRF disabled
            'XmlHttpRequest' => true          // optional, agar sirf AJAX ke liye
        ]
    )]
    #[StorefrontRoute]
    public function addLineItems(RequestDataBag $requestDataBag, SalesChannelContext $salesChannelContext): Response
    {
        $lineItemsData = $requestDataBag->get('lineItems');
        if (!$lineItemsData) {
            throw new MissingRequestParameterException('lineItems');
        }
        $addParam = [];
        /** @var  RequestDataBag $lineItemData */
        foreach ($lineItemsData as $key => $lineItemData) {
            $addParam[] = [
                'productId' => $key,
                'quantity' => $lineItemData->getInt('quantity')
            ];
        }
        $this->inquiryCartService->add($addParam, $salesChannelContext);
        $response = $this->twig->render('@MyfavInquiry/storefront/myfav-inquiry/modal.html.twig');
        return new JsonResponse([
            'success' => true,
            'html'    => $response
        ]);
        //return new JsonResponse(['success' => true, 'html' => $response->getContent()]);
    }
    #[Route('/myfav/inquiry/delete/{id}', name: 'frontend.myfav.inquiry.delete', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    #[StorefrontRoute]
    public function delete(string $id, SalesChannelContext $salesChannelContext): Response
    {
        $this->inquiryCartService->delete($id, $salesChannelContext);

        return $this->redirectToRoute('frontend.myfav.inquiry.confirm');
    }
    #[Route('/myfav/inquiry/quantity/{id}', name: 'frontend.myfav.inquiry.quantity', methods: ['POST'], defaults: ['_routeScope' => ['storefront']])]
    #[StorefrontRoute]
    public function quantity(string $id, RequestDataBag $requestDataBag, SalesChannelContext $salesChannelContext): Response
    {
        $quantity = $requestDataBag->getInt('quantity', 1);
        $this->inquiryCartService->changeQuantity($id, $quantity, $salesChannelContext);
        return $this->redirectToRoute('frontend.myfav.inquiry.confirm');
    }
    #[Route(
        '/myfav/inquiry/count',
        name: 'frontend.myfav.inquiry.count',
        methods: ['GET'],
        defaults: [
            '_routeScope' => ['storefront'], // store front scope
            'csrf_protected' => false,       // CSRF disable for simple GET requests
            'XmlHttpRequest' => true          // optional, if only for XHR
        ]
    )]
    #[StorefrontRoute]
    public function count(SalesChannelContext $salesChannelContext): Response
    {
        //die('ffffff');
        $count = $this->inquiryCartService->count($salesChannelContext);
        return new JsonResponse(['count' => $count]);
    }
    //#[Route('/myfav/inquiry/confirm', name: 'frontend.myfav.inquiry.confirm', methods: ['GET'])]
    #[Route('/myfav/inquiry/confirm', name: 'frontend.myfav.inquiry.confirm', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]

    /*public function confirm(InquiryConfirmPageLoader $pageLoader, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        //die('dddddd');
        $page = $pageLoader->load($request, $salesChannelContext);
        return $this->twig->render('@MyfavInquiry/storefront/myfav-inquiry/page/confirm/index.html.twig', [
            'page' => $page
        ]);
    }*/
    public function confirm(
    InquiryConfirmPageLoader $pageLoader, 
    Request $request, 
    SalesChannelContext $salesChannelContext
    ): Response
    {
        
        $page = $pageLoader->load($request, $salesChannelContext);
        //print_r($request->getData());die;

        // Twig render returns string, wrap in Response
        return new Response(
            $this->twig->render('@MyfavInquiry/storefront/myfav-inquiry/page/confirm/index.html.twig', [
                'page' => $page
            ])
        );
    }
    #[Route(
        '/myfav/inquiry/send', 
        name: 'frontend.myfav.inquiry.send', 
        methods: ['POST'], 
        defaults: [
            '_captcha' => true,
            '_routeScope' => ['storefront']   // <- ye zaruri hai
        ]
    )]
    #[StorefrontRoute]
    public function send(Request $request, RequestDataBag $data, SalesChannelContext $salesChannelContext): Response
    {
        $logos = $request->files->get('logo', []);
        $inquiryId = $this->inquiryService->createInquiry($data, $salesChannelContext, $logos);
        return $this->redirectToRoute('frontend.myfav.wishlisted.finish', ['inquiryId' => $inquiryId]);
    }
    #[Route('/wishlisted/success', name: 'frontend.myfav.wishlisted.finish', methods: ['GET'], defaults: ['_routeScope' => ['storefront']])]
    #[StorefrontRoute]
    public function finish(InquiryFinishPageLoader $pageLoader, Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $page = $pageLoader->load($request, $salesChannelContext, true);

        $html = $this->twig->render('@MyfavInquiry/storefront/myfav-inquiry/page/finish/index.html.twig', [
            'page' => $page
        ]);

        return new Response($html);
    }

    /*#[Route(
    '/myfav/inquiry/trigger',
    name: 'frontend.myfav.inquiry.trigger',
    methods: ['GET'],
    defaults: [
            '_routeScope' => ['storefront'],       // CSRF disable for simple GET requests
            'XmlHttpRequest' => true          // optional, if only for XHR
        ]
    )]
    public function triggerInquiryLogic(
        Request $request,
        SalesChannelContext $salesChannelContext
    ): JsonResponse {
        $inquiryId = $request->query->get('inquiryId');
        $sql = "SELECT id, extended_data FROM myfav_inquiry_line_item WHERE inquiry_id = :inquiryId";
        //$lineItems = $this->connection->fetchAllAssociative($sql, ['inquiryId' => $inquiryId]);
        $lineItems = $this->connection->createQueryBuilder()
        ->select('*')
        ->from('myfav_inquiry_line_item')
        ->where('inquiry_id = :inquiryId')
        ->setParameter('inquiryId', Uuid::fromHexToBytes($inquiryId)) // ✅ Convert HEX to binary
        ->fetchAllAssociative();
        //print_r($lineItems);die('sdasdas');
        if ($lineItems) {
            foreach ($lineItems as $item) {
            // Decode existing extended_data
            $extendedData = json_decode($item['extended_data'], true) ?? [];

            // Get the folder path from extendedData
            $modifiedProductImageFolder = $extendedData['modifiedProductImage'] ?? null;

            // Initialize new path (in case image is found)
            $newImagePath = null;

            if ($modifiedProductImageFolder) {
                // Absolute folder path (assumes path starts from public/)
                $folderPath = $_SERVER['DOCUMENT_ROOT'] . $modifiedProductImageFolder;
                $folderPath = rtrim($folderPath, '/');

                // Valid image extensions
                $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                // Find all files
                $allFiles = glob($folderPath . '/*');

                // Filter only image files
                $imageFiles = array_filter($allFiles, function ($file) use ($validExtensions) {
                    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    return in_array($ext, $validExtensions);
                });

                // Pick the first image (if any)
                $firstImagePath = reset($imageFiles);

                if ($firstImagePath) {
                    // Convert back to web-relative path
                    $newImagePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $firstImagePath);
                }
            }

            // ✅ Update extendedData
            if ($newImagePath) {
                $extendedData['modifiedProductImage'] = $newImagePath;
            }

            // Save back to DB
            $this->connection->update(
                'myfav_inquiry_line_item',
                ['extended_data' => json_encode($extendedData)],
                ['id' => $item['id']]
            );
        }

        }

        $this->connection->update(
            'myfav_inquiry',
            ['status' => 'new'],  // New status
            ['id' => Uuid::fromHexToBytes($inquiryId)]  // Target the inquiry by ID
        );

        if (!$inquiryId) {
            return new JsonResponse(['error' => 'Missing inquiryId'], 400);
        }

        // ✅ Your custom logic
        //$this->handleInquiryCompleted($inquiryId, $salesChannelContext);

        return new JsonResponse(['status' => 'triggered']);
    }*/

    #[Route(
    '/myfav/inquiry/trigger',
    name: 'frontend.myfav.inquiry.trigger',
    methods: ['GET'],
    defaults: [
        '_routeScope' => ['storefront'],       // CSRF disable for simple GET requests
        'XmlHttpRequest' => true                // optional, if only for XHR
    ]
    )]
    public function triggerInquiryLogic(
        Request $request,
        SalesChannelContext $salesChannelContext
    ): JsonResponse {
        $inquiryId = $request->query->get('inquiryId');

        if (!$inquiryId) {
            return new JsonResponse(['error' => 'Missing inquiryId'], 400);
        }

        // Step 1: Process line items, update modifiedProductImage path
        $lineItems = $this->connection->createQueryBuilder()
            ->select('*')
            ->from('myfav_inquiry_line_item')
            ->where('inquiry_id = :inquiryId')
            ->setParameter('inquiryId', Uuid::fromHexToBytes($inquiryId)) // Convert HEX to binary
            ->fetchAllAssociative();

        if ($lineItems) {
            foreach ($lineItems as $item) {
                $extendedData = json_decode($item['extended_data'], true) ?? [];
                $modifiedProductImageFolder = $extendedData['modifiedProductImage'] ?? null;
                $newImagePath = null;

                if ($modifiedProductImageFolder) {
                    $folderPath = rtrim($_SERVER['DOCUMENT_ROOT'] . $modifiedProductImageFolder, '/');
                    $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

                    $allFiles = glob($folderPath . '/*');
                    $imageFiles = array_filter($allFiles, function ($file) use ($validExtensions) {
                        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                        return in_array($ext, $validExtensions);
                    });

                    $firstImagePath = reset($imageFiles);
                    if ($firstImagePath) {
                        $newImagePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $firstImagePath);
                    }
                }

                if ($newImagePath) {
                    $extendedData['modifiedProductImage'] = $newImagePath;
                }
                $originalProductId = $extendedData['originalProductId'] ?? null;
                $productId = Uuid::fromHexToBytes($originalProductId);
                $this->connection->update(
                    'myfav_inquiry_line_item',
                    [
                        'product_id' => $productId,
                        'extended_data' => json_encode($extendedData)
                    ],

                    ['id' => $item['id']]
                );
            }
        }

        // Step 2: Update inquiry status to 'new'
        $this->connection->update(
            'myfav_inquiry',
            ['status' => 'new'],
            ['id' => Uuid::fromHexToBytes($inquiryId)]
        );

        // Step 3: Fetch inquiry (customer) data
        $sqlInquiry = "SELECT 
                HEX(id) as id,
                HEX(salutation_id) as salutation_id,
                first_name,
                last_name,
                email,
                company,
                phone_number,
                delivery_date,
                comment
            FROM myfav_inquiry
            WHERE id = :inquiryId
            LIMIT 1";

        $inquiryData = $this->connection->fetchAssociative($sqlInquiry, ['inquiryId' => Uuid::fromHexToBytes($inquiryId)]);

        if (!$inquiryData) {
            return new JsonResponse(['error' => 'Inquiry not found'], 404);
        }

        // Step 4: Fetch updated line items again for email
        $lineItemsRaw = $this->connection->createQueryBuilder()
            ->select('id, extended_data, quantity')
            ->from('myfav_inquiry_line_item')
            ->where('inquiry_id = :inquiryId')
            ->setParameter('inquiryId', Uuid::fromHexToBytes($inquiryId))
            ->fetchAllAssociative();

        $lineItemsForEmail = [];

        foreach ($lineItemsRaw as $item) {
            $extendedData = json_decode($item['extended_data'], true) ?? [];

            $productId = $extendedData['productId'] ?? null;
            $originalProductId = $extendedData['originalProductId'] ?? null;
            $product = $this->productRepository->search(new Criteria([$originalProductId]), Context::createDefaultContext())->first();
            $productName = $product ? $product->getTranslation('name') : 'N/A';
            // Base item
            $lineItem = [
                'productId' => $productId,
                'quantity'  => $item['quantity'] ?? null,
                'price'     => $extendedData['price'] ?? null,
            ];
            // If it's a custom (non-Shopware) product
            if ($productId === null) {
                $lineItem['extensions'] = [
                    'myfavZweidehProductData' => [
                        'productName' => $productName ?? '',
                        'productSize' => $extendedData['sizeName'] ?? '',
                        'imageUrl'    => $extendedData['modifiedProductImage'] ?? '', // mapped correctly
                        'color'    => $extendedData['color'] ?? '',
                        'brand'    => $extendedData['brand'] ?? '',
                    ]
                ];
            } else {
                // If you have real Shopware product data, include it here
                $lineItem['product'] = [
                    'productNumber' => $extendedData['productNumber'] ?? '',
                    'name'          => $extendedData['productName'] ?? '',
                    'media'         => [
                        ['media' => ['url' => $extendedData['imageUrl'] ?? '']]
                    ],
                    'color'          => $extendedData['color'] ?? '',
                    'brand'          => $extendedData['brand'] ?? '',
                ];
            }

            $lineItemsForEmail[] = $lineItem;
        }
        //print_r($lineItemsForEmail);die;

        // Optional: fetch salutation display name from salutation_id
        $salutation = null;
        if ($inquiryData['salutation_id']) {
            $salutation = $this->connection->fetchOne(
                "SELECT salutation_key FROM salutation WHERE id = :salutationId",
                ['salutationId' => hex2bin($inquiryData['salutation_id'])]
            );
        }

        // Step 5: Prepare template data for email
        $templateData = [
            'inquiry' => [
                'salutation' => [
                    'displayName' => $salutation ?: 'N/A',
                ],
                'firstName'   => $inquiryData['first_name'],
                'lastName'    => $inquiryData['last_name'],
                'email'       => $inquiryData['email'],
                'company'     => $inquiryData['company'],
                'phoneNumber' => $inquiryData['phone_number'],
                'deliveryDate'=> $inquiryData['delivery_date'],
                'comment'     => $inquiryData['comment'],
                'lineItems'   => $lineItemsForEmail,
            ]
        ];

        // Step 6: Prepare mail data
        $data = new DataBag();
        $recipients = [
            'ajit.jain123@emails.emizentech.com' => 'Santafetex Onlineshop',
            //'steve@mindfav.com' => 'Steve Krämer',
        ];
        $senderName = 'Santafetex Onlineshop';

        $data->set('recipients', $recipients);
        $data->set('senderName', $senderName);
        $data->set('salesChannelId', $salesChannelContext->getSalesChannel()->getId());

        $mailTemplate = $this->getMailTemplate('myfav_inquiry_request', $salesChannelContext->getContext());
        if (null === $mailTemplate) {
            throw new \Exception('E-Mail Template for InquiryMail was not found');
        }
        //echo $mailTemplate->getId();die;
        $data->set('templateId', $mailTemplate->getId());
        $data->set('subject', $mailTemplate->getSubject());
        $data->set('contentHtml', $mailTemplate->getContentHtml());
        $data->set('contentPlain', $mailTemplate->getContentPlain());

        // Step 7: Send email
        $result = $this->mailService->send(
            $data->all(),
            $salesChannelContext->getContext(),
            $templateData
        );

        if (!$result) {
            return new JsonResponse(['error' => 'Failed to send email'], 500);
        }

        // Finally, return success response
        return new JsonResponse(['status' => 'triggered and email sent']);
    }
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
