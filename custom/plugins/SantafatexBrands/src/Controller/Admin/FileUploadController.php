<?php declare(strict_types=1);

namespace Santafatex\Brands\Controller\Admin;

use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class FileUploadController
{
    private string $publicPath;

    public function __construct(string $projectDir)
    {
        $this->publicPath = $projectDir . '/public/uploads/brands';
    }

    public function uploadFile(Request $request): JsonResponse
    {
        error_log('=== Upload File API Called ===');
        
        $file = $request->files->get('file');
        $subfolder = $request->request->get('subfolder', 'sizeChartPaths');
        
        error_log('File received: ' . ($file ? 'Yes' : 'No'));
        error_log('Subfolder: ' . $subfolder);
        
        if (!$file instanceof UploadedFile) {
            error_log('ERROR: No file uploaded');
            return new JsonResponse([
                'success' => false,
                'message' => 'No file uploaded'
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $uploadedPath = $this->uploadFileInternal($file, $subfolder);
            error_log('File uploaded successfully: ' . $uploadedPath);
            
            return new JsonResponse([
                'success' => true,
                'path' => $uploadedPath,
                'message' => 'File uploaded successfully'
            ]);
        } catch (\Exception $e) {
            error_log('ERROR: ' . $e->getMessage());
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function uploadFileInternal(UploadedFile $file, string $subfolder): string
    {
        error_log('=== Upload File Internal Function Called ===');
        error_log('Subfolder: ' . $subfolder);
        error_log('Original filename: ' . $file->getClientOriginalName());
        
        // Validate file
        $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif'];
        $fileExtension = strtolower($file->getClientOriginalExtension());
        
        error_log('File extension: ' . $fileExtension);

        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log('ERROR: Invalid file type');
            throw new \Exception('Invalid file type');
        }

        // Create directory if not exists
        $uploadDir = $this->publicPath . '/' . $subfolder;
        error_log('Upload directory: ' . $uploadDir);
        
        if (!is_dir($uploadDir)) {
            error_log('Directory does not exist. Creating...');
            if (!mkdir($uploadDir, 0777, true)) {
                error_log('ERROR: Failed to create directory');
                throw new \Exception('Failed to create upload directory: ' . $uploadDir);
            }
            error_log('Directory created successfully');
        } else {
            error_log('Directory already exists');
        }
        
        // Check if directory is writable
        if (!is_writable($uploadDir)) {
            error_log('ERROR: Directory is not writable');
            throw new \Exception('Upload directory is not writable: ' . $uploadDir);
        }

        // Generate unique filename
        $timestamp = time();
        $filename = $subfolder . '-' . $timestamp . '.' . $fileExtension;
        error_log('Generated filename: ' . $filename);
        
        // Full path where file will be saved
        $fullPath = $uploadDir . '/' . $filename;
        error_log('Full path: ' . $fullPath);

        // Move file
        try {
            error_log('Attempting to move file...');
            $file->move($uploadDir, $filename);
            error_log('File move successful');
        } catch (\Exception $e) {
            error_log('ERROR: File move failed - ' . $e->getMessage());
            throw new \Exception('Failed to upload file to: ' . $fullPath . ' - Error: ' . $e->getMessage());
        }

        // Verify file was uploaded
        if (!file_exists($fullPath)) {
            error_log('ERROR: File does not exist after move');
            throw new \Exception('File upload failed. File not found at: ' . $fullPath);
        }
        
        error_log('File verified at: ' . $fullPath);

        // Return relative path for storage in database (without leading slash)
        $relativePath = 'uploads/brands/' . $subfolder . '/' . $filename;
        error_log('Relative path for database: ' . $relativePath);
        error_log('=== Upload Complete ===');
        
        return $relativePath;
    }
}
