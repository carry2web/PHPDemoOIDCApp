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
     * Upload a file to S3 based on user type and permissions
     */
    public function uploadDocument($userType, $fileName, $fileContent, $contentType = 'application/octet-stream', $user = null, $targetCustomer = null) {
        try {
            // Determine upload permissions and target folder
            $uploadTarget = $this->determineUploadTarget($userType, $user, $targetCustomer);
            
            if (!$uploadTarget['allowed']) {
                return [
                    'success' => false,
                    'error' => $uploadTarget['error'] ?? 'Upload not permitted'
                ];
            }
            
            $key = $uploadTarget['folder'] . '/' . sanitize_filename($fileName);
            
            $result = $this->s3Client->putObject([
                'Bucket' => $this->bucket,
                'Key' => $key,
                'Body' => $fileContent,
                'ContentType' => $contentType,
                'Metadata' => [
                    'uploaded-by' => $userType,
                    'uploaded-by-email' => $user['email'] ?? 'unknown',
                    'upload-date' => date('Y-m-d H:i:s'),
                    'target-customer' => $targetCustomer ?? 'self'
                ]
            ]);
            
            $this->logger->info("Document uploaded successfully", [
                'user_type' => $userType,
                'key' => $key,
                'etag' => $result['ETag'],
                'target_customer' => $targetCustomer,
                'uploaded_by' => $user['email'] ?? 'unknown'
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
     * Determine where a user can upload based on their role
     */
    private function determineUploadTarget($userType, $user, $targetCustomer = null) {
        $userRole = $this->getUserRole($user);
        
        switch ($userRole) {
            case 'customer':
                // Customers can only upload to their own folder (but currently disabled)
                return [
                    'allowed' => false,
                    'error' => 'Customers have read-only access. Contact your agent for document uploads.',
                    'folder' => null
                ];
                
            case 'agent':
                // Agents can upload to any customer folder they manage
                if ($targetCustomer) {
                    // Upload to specific customer folder
                    $customerFolder = "customers/" . sanitize_filename($targetCustomer);
                    return [
                        'allowed' => true,
                        'folder' => $customerFolder
                    ];
                } else {
                    // Default to agents folder
                    return [
                        'allowed' => true,
                        'folder' => 'agents'
                    ];
                }
                
            case 'admin':
                // Admins can upload anywhere
                if ($targetCustomer) {
                    $customerFolder = "customers/" . sanitize_filename($targetCustomer);
                    return [
                        'allowed' => true,
                        'folder' => $customerFolder
                    ];
                } else {
                    return [
                        'allowed' => true,
                        'folder' => $userType === 'customer' ? 'customers' : 'agents'
                    ];
                }
                
            default:
                return [
                    'allowed' => false,
                    'error' => 'Unknown user role',
                    'folder' => null
                ];
        }
    }
    
    /**
     * List documents based on user role and permissions
     */
    public function listDocuments($userType, $user = null) {
        try {
            $userRole = $this->getUserRole($user);
            $foldersToList = $this->getFoldersForUser($userRole, $userType, $user);
            
            $documents = [];
            
            foreach ($foldersToList as $folder) {
                $result = $this->s3Client->listObjectsV2([
                    'Bucket' => $this->bucket,
                    'Prefix' => "$folder/"
                ]);
                
                if (isset($result['Contents'])) {
                    foreach ($result['Contents'] as $object) {
                        // Skip folder markers (keys ending with /)
                        if (substr($object['Key'], -1) === '/') {
                            continue;
                        }
                        
                        $documents[] = [
                            'key' => $object['Key'],
                            'folder' => $folder,
                            'filename' => basename($object['Key']),
                            'size' => $object['Size'],
                            'modified' => $object['LastModified']->format('Y-m-d H:i:s'),
                            'download_url' => $this->generatePresignedUrl($object['Key']),
                            'can_delete' => $this->canUserDeleteFile($userRole, $object['Key'], $user)
                        ];
                    }
                }
            }
            
            // Sort documents by modified date (newest first)
            usort($documents, function($a, $b) {
                return strtotime($b['modified']) - strtotime($a['modified']);
            });
            
            return [
                'success' => true,
                'documents' => $documents,
                'folders' => $foldersToList,
                'user_role' => $userRole,
                'can_upload' => $this->canUserUpload($userRole)
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
     * Get folders that a user can access based on their role
     */
    private function getFoldersForUser($userRole, $userType, $user) {
        switch ($userRole) {
            case 'customer':
                // Customers can only see their own folder
                $customerEmail = $user['email'] ?? 'unknown';
                $customerFolder = 'customers/' . sanitize_filename($customerEmail);
                return [$customerFolder];
                
            case 'agent':
                // Agents can see all customer folders they manage + their own agent folder
                // For now, agents see all customer folders (relationship not implemented yet)
                $folders = ['agents'];
                
                // Get all customer folders
                $customerFolders = $this->getCustomerFolders();
                return array_merge($folders, $customerFolders);
                
            case 'admin':
                // Admins can see everything
                $folders = ['agents'];
                $customerFolders = $this->getCustomerFolders();
                return array_merge($folders, $customerFolders);
                
            default:
                return [];
        }
    }
    
    /**
     * Get all customer folders from S3
     */
    private function getCustomerFolders() {
        try {
            $result = $this->s3Client->listObjectsV2([
                'Bucket' => $this->bucket,
                'Prefix' => 'customers/',
                'Delimiter' => '/'
            ]);
            
            $folders = [];
            if (isset($result['CommonPrefixes'])) {
                foreach ($result['CommonPrefixes'] as $prefix) {
                    $folder = rtrim($prefix['Prefix'], '/');
                    $folders[] = $folder;
                }
            }
            
            return $folders;
            
        } catch (AwsException $e) {
            $this->logger->error("Failed to get customer folders", [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
    
    /**
     * Determine user role from session data
     */
    private function getUserRole($user) {
        if (!$user) {
            return 'unknown';
        }
        
        // Check for admin role (you can extend this logic)
        if (isset($user['roles']) && in_array('admin', $user['roles'])) {
            return 'admin';
        }
        
        // Check explicit user_role
        if (isset($user['user_role'])) {
            return $user['user_role'];
        }
        
        // Fallback to user_type
        return $user['user_type'] ?? 'customer';
    }
    
    /**
     * Check if user can upload files
     */
    private function canUserUpload($userRole) {
        return in_array($userRole, ['agent', 'admin']);
    }
    
    /**
     * Check if user can delete a specific file
     */
    private function canUserDeleteFile($userRole, $fileKey, $user) {
        switch ($userRole) {
            case 'customer':
                return false; // Customers can't delete anything
                
            case 'agent':
                // Agents can delete files they uploaded or files in customer folders they manage
                return true; // For now, allow all deletions (refine later with relationships)
                
            case 'admin':
                return true; // Admins can delete anything
                
            default:
                return false;
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
