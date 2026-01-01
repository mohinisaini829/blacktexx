<?php declare(strict_types=1);

namespace CustomBrand\Controller;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Uuid\Uuid;

#[RouteScope(['api'])]
class AdminBrandDataController
{
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route(
        path: '/api/_action/custombrand/branddata/save',
        name: 'api.custombrand.branddata.save',
        methods: ['POST']
    )]
    public function save(Request $request, Context $context): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $id = Uuid::randomHex();
        $this->connection->insert('branddata', [
            'id' => Uuid::fromHexToBytes($id),
            'dropdown_value' => $data['dropdown_value'] ?? '',
            'text1' => $data['text1'] ?? '',
            'text2' => $data['text2'] ?? '',
            'media_id' => isset($data['media_id']) ? Uuid::fromHexToBytes($data['media_id']) : null,
            'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s.v'),
        ]);

        return new JsonResponse(['success' => true, 'id' => $id]);
    }
}