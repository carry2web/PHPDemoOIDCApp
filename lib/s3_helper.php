<?php
require __DIR__ . '/../vendor/autoload.php';

use Aws\S3\S3Client;

function get_presigned_url_for_file($filename) {
    $env = parse_ini_file(__DIR__ . '/../.env');

    $s3 = new S3Client([
        'region'  => $env['AWS_REGION'],
        'version' => 'latest',
        'credentials' => [
            'key'    => $env['AWS_ACCESS_KEY_ID'],
            'secret' => $env['AWS_SECRET_ACCESS_KEY'],
        ]
    ]);

    try {
        $cmd = $s3->getCommand('GetObject', [
            'Bucket' => $env['AWS_BUCKET'],
            'Key'    => "pdfs/$filename"
        ]);

        $request = $s3->createPresignedRequest($cmd, '+5 minutes');

        return (string) $request->getUri();
    } catch (Exception $e) {
        error_log("S3 fout: " . $e->getMessage());
        return null;
    }
}
