<?php
// AWS S3 Helper for S-Cape Travel Document Management
require_once 'vendor/autoload.php';
require_once 'lib/config_helper.php';
require_once 'lib/logger.php';

use Aws\S3\S3Client;
use Aws\Exception\AwsException;

class DocumentManager {
    private $s3Client;
    private $bucket;
    private $logger;
    
    public function __construct() {
        $this->logger = ScapeLogger::getInstance();
        $config = get_app_config();
        
        $this->bucket = $config['aws']['bucket'];
        
        $this->s3Client = new S3Client([
            'version' => 'latest',
            'region' => $config['aws']['region'],
            'credentials' => [
                'key' => $_ENV['AWS_ACCESS_KEY_ID'],
                'secret' => $_ENV['AWS_SECRET_ACCESS_KEY']
            ]
        ]);
    }
    
    /**
     * Upload a file to S3 based on user type
     */
    public function uploadDocument($userType, $fileName, $fileContent, $contentType = 'application/octet-stream') {
        try {
            // Determine folder based on user type
            $folder = ($userType === 'customer') ? 'customers' : 'agents';
            $key = "$folder/" . sanitize_filename($fileName);
            
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'Metadata' => [
                    'uploaded-by' => $userType,
                    'upload-date' => date('Y-m-d H:i:s')
                ]
            ]);
            
            $this->logger->info("Document uploaded successfully", [
                'user_type' => $userType,
                'key' => $key,
                'etag' => $result['ETag']
            ]);
            
            return [
                'success' => true,
                'key' => $key,
                'etag' => $result['ETag'],
                'url' => $this->generatePresignedUrl($key)
            ];
            
        } catch (AwsException $e) {
            $this->logger->error("S3 upload failed", [
                'error' => $e->getMessage(),
                'user_type' => $userType,
                'filename' => $fileName
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * List documents for a user type
     */
    public function listDocuments($userType) {
        try {
            $folder = ($userType === 'customer') ? 'customers' : 'agents';
            
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => "$folder/"
            ]);
            
            $documents = [];
            if (isset($result['Contents'])) {
                foreach ($result['Contents'] as $object) {
                    $documents[] = [
                        'key' => $object['Key'],
                        'size' => $object['Size'],
                        'modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                        'download_url' => $this->generatePresignedUrl($object['Key'])
                    ];
                }
            }
            
            return [
                'success' => true,
                'documents' => $documents
            ];
            
        } catch (AwsException $e) {
            $this->logger->error("S3 list failed", [
                'error' => $e->getMessage(),
                'user_type' => $userType
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Generate a presigned URL for document download
     */
    public function generatePresignedUrl($key, $expiration = '+1 hour') {
        try {
            $cmd = $this->s3Client->getCommand('GetObject', [
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);
            
            $request = $this->s3Client->createPresignedRequest($cmd, $expiration);
            return (string) $request->getUri();
            
        } catch (AwsException $e) {
            $this->logger->error("Presigned URL generation failed", [
                'error' => $e->getMessage(),
                'key' => $key
            ]);
            return null;
        }
    }
    
    /**
     * Delete a document
     */
    public function deleteDocument($key, $userType) {
        try {
            // Verify the key belongs to the user type's folder
            $folder = ($userType === 'customer') ? 'customers' : 'agents';
            if (!str_starts_with($key, "$folder/")) {
                return [
                    'success' => false,
                    'error' => 'Access denied: Invalid document path'
                ];
            }
            
            $this->s3Client->deleteObject([
                'Bucket' => $this->bucket,
                'Key' => $key
            ]);
            
            $this->logger->info("Document deleted successfully", [
                'user_type' => $userType,
                'key' => $key
            ]);
            
            return ['success' => true];
            
        } catch (AwsException $e) {
            $this->logger->error("S3 delete failed", [
                'error' => $e->getMessage(),
                'user_type' => $userType,
                'key' => $key
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Check if AWS credentials are available
     */
    public static function isConfigured() {
        return !empty($_ENV['AWS_ACCESS_KEY_ID']) && !empty($_ENV['AWS_SECRET_ACCESS_KEY']);
    }
}

/**
 * Sanitize filename for S3
 */
function sanitize_filename($filename) {
    // Remove or replace problematic characters
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Ensure we don't have double underscores
    $filename = preg_replace('/_+/', '_', $filename);
    return trim($filename, '_');
}

/**
 * Get file extension from filename
 */
function get_file_extension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Get MIME type from file extension
 */
function get_mime_type($filename) {
    $extension = get_file_extension($filename);
    
    $mimeTypes = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'zip' => 'application/zip',
        'csv' => 'text/csv',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
    ];
    
    return $mimeTypes[$extension] ?? 'application/octet-stream';
}
?>
