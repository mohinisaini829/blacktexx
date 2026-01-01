<?php
declare(strict_types=1);

namespace Myfav\Inquiry\Administration\Controllers;

use InvalidArgumentException;
use Shopware\Core\Content\Media\File\FileLoader;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route; // ✅ for #[Route(...)]
use Shopware\Core\Content\Media\MediaRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\DBAL\Connection;
#[Route(defaults: ['_routeScope' => ['api']])]

class InquiryMediaController extends AbstractController
{
    private EntityRepository $mediaRepository;
    private FileLoader $fileLoader;
    private EntityRepository $inquiryRepository;
    private Connection $connection;

    public function __construct(
        EntityRepository $mediaRepository,
        FileLoader                $fileLoader,
        EntityRepository $inquiryRepository,
        Connection $connection
    )
    {
        $this->mediaRepository = $mediaRepository;
        $this->fileLoader = $fileLoader;
        $this->inquiryRepository = $inquiryRepository;
        $this->connection = $connection;
    }
    #[Route(
        path: '/api/_action/myfav_inquiry_media/media/{mediaId}',
        name: 'api.myfav_inquiry.media',
        methods: ['GET'],
        requirements: ['mediaId' => '[a-f0-9]{32}']
    )]
    public function getMedia(string $mediaId, Context $context): Response
    {
        //echo $mediaId;die('fsdfsdfsdf');
        /** @var ?MediaEntity $media */
        $media = null;
        // fetch media
        $media = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($mediaId) {
            return $this->mediaRepository->search(new Criteria([$mediaId]), $context)->first();
        });
        if ($media === null) {
            throw new InvalidArgumentException(sprintf('"%s" isn\'t a valid media id!', $mediaId));
        }

        // get file content
        $content = null;
        $content = $context->scope(Context::SYSTEM_SCOPE, function (Context $context) use ($mediaId) {
            return $this->fileLoader->loadMediaFile($mediaId, $context);
        });

        // build response
        $filename = $media->getFileName();
        $response = new Response($content);
        $disposition = HeaderUtils::makeDisposition(
            HeaderUtils::DISPOSITION_INLINE,
            $filename,
            // only printable ascii
            preg_replace('/[\x00-\x1F\x7F-\xFF]/', '_', $filename)
        );

        $response->headers->set('Content-Type', $media->getMimeType());
        $response->headers->set('Content-Disposition', $disposition);
        return $response;
    }
    #[Route(path: '/api/_action/inquiry/update-status', name: 'api.designer.inquiry.update_status', methods: ['POST'])]
    public function updateInquiryStatus(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $userId = $context->getSource()->getUserId();

        if (empty($data['id']) || empty($data['status'])) {
            return new JsonResponse(['success' => false, 'message' => 'Invalid input'], 400);
        }

        try {
            $userName = $this->getUserNameById($userId);

            $this->connection->update(
                'myfav_inquiry',
                [
                    'status' => $data['status'],
                    'admin_user' => $userName,
                ],
                [
                    'id' => hex2bin($data['id'])
                ]
            );

            $this->connection->insert(
                'status_history',
                [
                    'enq_id' => hex2bin($data['id']), // Direct UUID binary
                    'status' => $data['status'],
                    'admin_user' => $userName,
                    'created_at' => (new \DateTime())->format('Y-m-d H:i:s')
                ]
            );

            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }




    public function getUserNameById(string $userId): ?string
    {
        // $userId is hex string, need to convert to binary for query
        $userIdBinary = hex2bin($userId);

        $sql = 'SELECT username FROM user WHERE id = :id';
        $username = $this->connection->fetchOne($sql, ['id' => $userIdBinary]);

        return $username ?: null;
    }

}
