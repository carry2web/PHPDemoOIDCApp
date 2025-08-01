<?php
session_start();
require_once 'vendor/autoload.php';
require_once 'lib/config_helper.php';
require_once 'lib/document_manager.php';
require_once 'lib/logger.php';
require_once 'lib/security_helper.php';

$logger = ScapeLogger::getInstance();
$security = SecurityHelper::getInstance();

// Check if user is logged in (check OIDC authentication)
if (!isset($_SESSION['email']) || !isset($_SESSION['authenticated_at'])) {
    header('Location: index.php?login=1&type=customer');
    exit;
}

// Create scape_user array from OIDC session for compatibility
$user = [
    'email' => $_SESSION['email'],
    'name' => $_SESSION['name'] ?? '',
    'user_type' => $_SESSION['user_type'] ?? 'customer'
];
$_SESSION['scape_user'] = $user; // Set for compatibility

$userType = $user['user_type'];

// Handle file upload and delete operations
$uploadResult = null;
$listResult = null;
$deleteResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting check
    $rateLimitKey = 'documents_' . ($user['email'] ?? session_id());
    if (!$security->checkRateLimit($rateLimitKey, 10, 300)) {
        $uploadResult = $deleteResult = [
            'success' => false,
            'error' => 'Too many requests. Please try again later.'
        ];
    } else {
        // CSRF protection
        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!$security->validateCSRFToken($csrfToken)) {
            $uploadResult = $deleteResult = [
                'success' => false,
                'error' => 'Security token validation failed. Please refresh the page.'
            ];
        } else {
            // Validate action
            $actionValidation = $security->validateAction($_POST['action'] ?? '', ['upload', 'delete']);
            if (!$actionValidation['valid']) {
                $uploadResult = $deleteResult = [
                    'success' => false,
                    'error' => $actionValidation['error']
                ];
            } else {
                $action = $actionValidation['value'];
                
                if ($action === 'upload' && isset($_FILES['document'])) {
                    // Enhanced file upload validation
                    $fileValidation = $security->validateFileUpload($_FILES['document'], [
                        'maxSize' => 15 * 1024 * 1024, // 15MB for documents
                        'allowedMimeTypes' => [
                            'application/pdf',
                            'image/jpeg',
                            'image/png',
                            'image/gif',
                            'text/plain',
                            'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                        ],
                        'allowedExtensions' => ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'txt', 'doc', 'docx', 'xls', 'xlsx']
                    ]);
                    
                    if (!$fileValidation['valid']) {
                        $uploadResult = [
                            'success' => false,
                            'error' => $fileValidation['error']
                        ];
                        $logger->warning('File upload validation failed', [
                            'user_email' => $user['email'],
                            'error' => $fileValidation['error'],
                            'filename' => $_FILES['document']['name'] ?? 'unknown'
                        ]);
                    } else {
                        if (DocumentManager::isConfigured()) {
                            $docManager = new DocumentManager();
                            $fileContent = file_get_contents($fileValidation['tmp_name']);
                            
                            $uploadResult = $docManager->uploadDocument(
                                $userType, 
                                $fileValidation['filename'], 
                                $fileContent, 
                                $fileValidation['mime_type']
                            );
                            
                            $logger->info("Document upload attempt", [
                                'user_email' => $user['email'],
                                'user_type' => $userType,
                                'filename' => $fileValidation['filename'],
                                'original_filename' => $fileValidation['original_filename'],
                                'size' => $fileValidation['size'],
                                'success' => $uploadResult['success']
                            ]);
                        } else {
                            $uploadResult = [
                                'success' => false,
                                'error' => 'Document storage not configured'
                            ];
                        }
                    }
                } elseif ($action === 'delete' && isset($_POST['document_key'])) {
                    // Validate document key
                    $documentKey = trim($_POST['document_key']);
                    if (empty($documentKey) || strlen($documentKey) > 500) {
                        $deleteResult = [
                            'success' => false,
                            'error' => 'Invalid document identifier'
                        ];
                    } elseif (strpos($documentKey, '..') !== false || strpos($documentKey, '/') === 0) {
                        $deleteResult = [
                            'success' => false,
                            'error' => 'Invalid document path'
                        ];
                        $logger->security('Attempted path traversal in document delete', [
                            'user_email' => $user['email'],
                            'document_key' => $documentKey,
                            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                        ]);
                    } else {
                        if (DocumentManager::isConfigured()) {
                            $docManager = new DocumentManager();
                            $deleteResult = $docManager->deleteDocument($documentKey, $userType);
                            $logger->info("Document delete attempt", [
                                'user_email' => $user['email'],
                                'user_type' => $userType,
                                'key' => $documentKey,
                                'success' => $deleteResult['success']
                            ]);
                        } else {
                            $deleteResult = [
                                'success' => false,
                                'error' => 'Document storage not configured'
                            ];
                        }
                    }
                }
            }
        }
    }
}

// Get document list
if (DocumentManager::isConfigured()) {
    $docManager = new DocumentManager();
    $listResult = $docManager->listDocuments($userType);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>S-Cape Travel - Document Management</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .upload-area {
            border: 2px dashed #007bff;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            background: #f8f9fa;
        }
        .document-list {
            margin: 20px 0;
        }
        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            margin: 10px 0;
            background: white;
        }
        .document-info {
            flex-grow: 1;
        }
        .document-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-danger { background: #dc3545; color: white; }
        .btn-success { background: #28a745; color: white; }
        .btn:hover { opacity: 0.8; }
        .alert {
            padding: 15px;
            border-radius: 4px;
            margin: 10px 0;
        }
        .alert-success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .alert-danger { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .alert-warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .user-info {
            background: #e7f3ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🏢 S-Cape Travel - Document Management</h1>
            <div class="user-info">
                <strong>👤 Logged in as:</strong> <?php echo htmlspecialchars($user['name']); ?> 
                (<?php echo htmlspecialchars($user['email']); ?>)<br>
                <strong>🎭 User Type:</strong> <?php echo ucfirst($userType); ?><br>
                <strong>📁 Document Folder:</strong> <?php echo $userType === 'customer' ? 'customers/' : 'agents/'; ?>
            </div>
            <nav>
                <a href="dashboard.php">← Back to Dashboard</a> | 
                <a href="logout.php">Logout</a>
            </nav>
        </div>

        <?php if (!DocumentManager::isConfigured()): ?>
            <div class="alert alert-warning">
                <strong>⚠️ AWS Configuration Required</strong><br>
                AWS credentials are not configured. This is normal for local development.<br>
                On Azure Web Apps, add AWS_ACCESS_KEY_ID and AWS_SECRET_ACCESS_KEY to Application Settings.
            </div>
        <?php endif; ?>

        <?php if ($uploadResult): ?>
            <?php if ($uploadResult['success']): ?>
                <div class="alert alert-success">
                    <strong>✅ Upload Successful!</strong><br>
                    Document uploaded to: <?php echo htmlspecialchars($uploadResult['key']); ?>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>❌ Upload Failed:</strong> <?php echo htmlspecialchars($uploadResult['error']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($deleteResult): ?>
            <?php if ($deleteResult['success']): ?>
                <div class="alert alert-success">
                    <strong>✅ Document Deleted Successfully!</strong>
                </div>
            <?php else: ?>
                <div class="alert alert-danger">
                    <strong>❌ Delete Failed:</strong> <?php echo htmlspecialchars($deleteResult['error']); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <div class="card">
            <h2>📤 Upload Document</h2>
            <?php if (DocumentManager::isConfigured()): ?>
                <form method="post" enctype="multipart/form-data">
                    <?= csrf_input() ?>
                    <input type="hidden" name="action" value="upload">
                    <div class="upload-area">
                        <input type="file" name="document" required accept=".pdf,.doc,.docx,.txt,.jpg,.jpeg,.png,.gif,.xls,.xlsx">
                        <br><br>
                        <button type="submit" class="btn btn-primary">📤 Upload Document</button>
                    </div>
                </form>
                <p><strong>Supported formats:</strong> PDF, DOC, DOCX, TXT, JPG, PNG, GIF, XLS, XLSX (Max 15MB)</p>
                <p><strong>Security:</strong> All files are scanned for malicious content before upload.</p>
            <?php else: ?>
                <div class="upload-area">
                    <p>📋 Upload functionality requires AWS configuration</p>
                    <p>This feature will be available once AWS credentials are configured in the application.</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>📂 Your Documents</h2>
            <?php if (DocumentManager::isConfigured() && $listResult && $listResult['success']): ?>
                <?php if (empty($listResult['documents'])): ?>
                    <p>No documents found in your folder.</p>
                <?php else: ?>
                    <div class="document-list">
                        <?php foreach ($listResult['documents'] as $doc): ?>
                            <div class="document-item">
                                <div class="document-info">
                                    <strong><?php echo htmlspecialchars(basename($doc['key'])); ?></strong><br>
                                    <small>
                                        Size: <?php echo number_format($doc['size'] / 1024, 1); ?> KB | 
                                        Modified: <?php echo $doc['modified']; ?>
                                    </small>
                                </div>
                                <div class="document-actions">
                                    <a href="<?php echo htmlspecialchars($doc['download_url']); ?>" 
                                       class="btn btn-success" target="_blank">📥 Download</a>
                                    <form method="post" style="display: inline;" 
                                          onsubmit="return confirm('Are you sure you want to delete this document?')">
                                        <?= csrf_input() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="document_key" value="<?php echo htmlspecialchars($doc['key']); ?>">
                                        <button type="submit" class="btn btn-danger">🗑️ Delete</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif (DocumentManager::isConfigured() && $listResult && !$listResult['success']): ?>
                <div class="alert alert-danger">
                    <strong>❌ Error loading documents:</strong> <?php echo htmlspecialchars($listResult['error']); ?>
                </div>
            <?php else: ?>
                <p>📋 Document listing requires AWS configuration</p>
                <p>Your documents will appear here once AWS credentials are configured.</p>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>🔧 System Information</h2>
            <ul>
                <li><strong>AWS Configuration:</strong> <?php echo DocumentManager::isConfigured() ? '✅ Ready' : '❌ Not configured'; ?></li>
                <li><strong>S3 Bucket:</strong> scape-travel-docs</li>
                <li><strong>AWS Region:</strong> eu-west-1</li>
                <li><strong>Your Folder:</strong> <?php echo $userType === 'customer' ? 'customers/' : 'agents/'; ?></li>
                <li><strong>Account ID:</strong> 955654668431</li>
            </ul>
        </div>
    </div>
</body>
</html>
