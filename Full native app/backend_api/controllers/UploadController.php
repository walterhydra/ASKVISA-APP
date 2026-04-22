<?php
class UploadController {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function uploadFile() {
        if (!isset($_FILES['file'])) {
            jsonResponse(false, 'No file uploaded', 400);
        }

        $file = $_FILES['file'];
        $upload_dir = dirname(__DIR__) . '/uploads/';
        
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        // Generate a secure, randomized filename
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = md5(uniqid(rand(), true)) . '.' . $ext;
        $target_file = $upload_dir . $filename;

        // Basic validation
        $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
        if (!in_array(mime_content_type($file['tmp_name']), $allowedTypes)) {
            jsonResponse(false, 'Invalid file type. Only JPG, PNG, and PDF are allowed.', 400);
        }
        
        if ($file['size'] > 10 * 1024 * 1024) { // 10MB limit
            jsonResponse(false, 'File is too large', 400);
        }

        if (move_uploaded_file($file['tmp_name'], $target_file)) {
            $web_path = 'uploads/' . $filename; // Relative to the API domain
            jsonResponse(true, [
                'message' => 'File uploaded successfully',
                'file_path' => $web_path
            ]);
        } else {
            jsonResponse(false, 'Failed to save the file', 500);
        }
    }
}
