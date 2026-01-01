<?php declare(strict_types=1);

namespace Santafatex\Brands\Controller\Admin;

use Santafatex\Brands\Service\BrandService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[RouteScope(['api'])]
class BrandController extends AbstractController
{
    private BrandService $brandService;
    private string $publicPath;

    public function __construct(BrandService $brandService, string $projectDir)
    {
        $this->brandService = $brandService;
        $this->publicPath = $projectDir . '/public/uploads/brands';
    }

    
    #[Route(
        path: '/santafatex-brands',
        name: 'api.santafatex.brands.list',
        methods: ['GET']
    )]
    public function listBrands(Request $request, Context $context): JsonResponse
    {
        $limit = $request->query->getInt('limit', 25);
        $offset = $request->query->getInt('offset', 0);

        $brands = $this->brandService->getAllBrands($context, $limit, $offset);

        return new JsonResponse([
            'data' => $brands->getElements(),
            'total' => $brands->getTotal(),
        ]);
    }

    
    #[Route(
        path: '/santafatex-brands/{brandId}',
        name: 'api.santafatex.brands.detail',
        methods: ['GET']
    )]
    public function getBrand(string $brandId, Context $context): JsonResponse
    {
        $brand = $this->brandService->getBrand($brandId, $context);

        if (!$brand) {
            return new JsonResponse(['message' => 'Brand not found'], Response::HTTP_NOT_FOUND);
        }

        return new JsonResponse(['data' => $brand]);
    }

    
    #[Route(
        path: '/santafatex-brands',
        name: 'api.santafatex.brands.create',
        methods: ['POST']
    )]
    public function createBrand(Request $request, Context $context): JsonResponse
    {
        $data = $this->prepareData($request, $context);

        $brandId = $this->brandService->createBrand($data, $context);

        return new JsonResponse([
            'message' => 'Brand created successfully',
            'id' => $brandId,
        ], Response::HTTP_CREATED);
    }

    #[Route(
        path: '/santafatex-brands/{brandId}',
        name: 'api.santafatex.brands.update',
        methods: ['PATCH', 'PUT']
    )]
    public function updateBrand(string $brandId, Request $request, Context $context): JsonResponse
    {
        $data = $this->prepareData($request, $context);

        $this->brandService->updateBrand($brandId, $data, $context);

        return new JsonResponse(['message' => 'Brand updated successfully']);
    }

    
    #[Route(
        path: '/santafatex-brands/{brandId}',
        name: 'api.santafatex.brands.delete',
        methods: ['DELETE']
    )]
    public function deleteBrand(string $brandId, Context $context): JsonResponse
    {
        $this->brandService->deleteBrand($brandId, $context);

        return new JsonResponse(['message' => 'Brand deleted successfully']);
    }

    private function prepareData(Request $request, Context $context): array
    {
        $data = $request->request->all();

        // Handle file uploads
        if ($request->files->has('sizeChartFile')) {
            $file = $request->files->get('sizeChartFile');
            if ($file instanceof UploadedFile) {
                $data['sizeChartPath'] = $this->uploadFile($file, 'size-charts');
            }
        }

        if ($request->files->has('catalogPdfFile')) {
            $file = $request->files->get('catalogPdfFile');
            if ($file instanceof UploadedFile) {
                $data['catalogPdfPath'] = $this->uploadFile($file, 'catalogs');
            }
        }

        return $data;
    }

    private function uploadFile(UploadedFile $file, string $subfolder): string
    {
        // Validate file
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower($file->getClientOriginalExtension());

        if (!in_array($fileExtension, $allowedExtensions)) {
            throw new \Exception('Invalid file type');
        }

        // Create directory if not exists
        $uploadDir = $this->publicPath . '/' . $subfolder;
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Generate unique filename
        $filename = uniqid() . '_' . time() . '.' . $fileExtension;
        $filepath = $uploadDir . '/' . $filename;

        // Move file
        $file->move($uploadDir, $filename);

        // Return relative path for storage
        return '/uploads/brands/' . $subfolder . '/' . $filename;
    }
}
