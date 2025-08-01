<?php
// File: lib/aws_helper.php
require_once __DIR__ . '/../vendor/autoload.php';

use Aws\Sts\StsClient;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;

/**
 * Get AWS credentials using STS assume role based on user role
 */
function get_aws_credentials($userRole, $userEmail) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    // Map user role to AWS role
    $awsRole = ($userRole === 'agent') ? $env['AWS_ROLE_AGENT'] : $env['AWS_ROLE_CUSTOMER'];
    
    try {
        $stsClient = new StsClient([
            'region' => $env['AWS_REGION'],
            'version' => 'latest'
        ]);
        
        $result = $stsClient->assumeRole([
            'RoleArn' => $awsRole,
            'RoleSessionName' => 'scape-' . $userRole . '-' . time(),
            'DurationSeconds' => 3600 // 1 hour
        ]);
        
        return $result['Credentials'];
        
    } catch (AwsException $e) {
        error_log("AWS STS Error: " . $e->getMessage());
        return null;
    }
}

/**
 * List PDFs available to user based on their role
 */
function list_available_pdfs($userRole, $credentials) {
    if (!$credentials) return [];
    
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    try {
        $s3Client = new S3Client([
            'region' => $env['AWS_REGION'],
            'version' => 'latest',
            'credentials' => [
                'key' => $credentials['AccessKeyId'],
                'secret' => $credentials['SecretAccessKey'],
                'token' => $credentials['SessionToken']
            ]
        ]);
        
        // List objects with role-based prefix
        $prefix = ($userRole === 'agent') ? 'agents/' : 'customers/';
        
        $result = $s3Client->listObjectsV2([
            'Bucket' => $env['AWS_S3_BUCKET'],
            'Prefix' => $prefix
        ]);
        
        $pdfs = [];
        if (isset($result['Contents'])) {
            foreach ($result['Contents'] as $object) {
                if (str_ends_with($object['Key'], '.pdf')) {
                    $pdfs[] = [
                        'key' => $object['Key'],
                        'name' => basename($object['Key']),
                        'size' => $object['Size'],
                        'modified' => $object['LastModified']
                    ];
                }
            }
        }
        
        return $pdfs;
        
    } catch (AwsException $e) {
        error_log("AWS S3 Error: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate presigned URL for PDF download
 */
function get_pdf_download_url($fileKey, $credentials, $expiresIn = 300) {
    if (!$credentials) return null;
    
    $env = parse_ini_file(__DIR__ . '/../.env');
    
    try {
        $s3Client = new S3Client([
            'region' => $env['AWS_REGION'],
            'version' => 'latest',
            'credentials' => [
                'key' => $credentials['AccessKeyId'],
                'secret' => $credentials['SecretAccessKey'],
                'token' => $credentials['SessionToken']
            ]
        ]);
        
        $command = $s3Client->getCommand('GetObject', [
            'Bucket' => $env['AWS_S3_BUCKET'],
            'Key' => $fileKey
        ]);
        
        $request = $s3Client->createPresignedRequest($command, "+{$expiresIn} seconds");
        
        return (string) $request->getUri();
        
    } catch (AwsException $e) {
        error_log("AWS S3 Presign Error: " . $e->getMessage());
        return null;
    }
}
?>
