<?php
declare(strict_types=1);

namespace App\Services;

use RuntimeException;

final class CVUploadService
{
    /** @var array<string, string> */
    private array $extensions = [
        'txt' => 'text/plain',
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];

    /** @var array<string, array<int, string>> */
    private array $allowedMimeTypes = [
        'txt' => ['text/plain', 'application/octet-stream'],
        'pdf' => ['application/pdf', 'application/octet-stream'],
        'doc' => ['application/msword', 'application/octet-stream'],
        'docx' => [
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/zip',
            'application/octet-stream',
        ],
    ];

    /** @param array<string, mixed>|null $file @return array<string, string> */
    public function validateCV(?array $file): array
    {
        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';

        if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return ['cv' => 'CV/Resume upload is required.'];
        }

        if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return ['cv' => 'CV upload failed. Please try again.'];
        }

        if ((int) ($file['size'] ?? 0) > (int) $config['cv_max_bytes']) {
            return ['cv' => 'CV must not exceed 10 MB.'];
        }

        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if (!array_key_exists($extension, $this->extensions)) {
            return ['cv' => 'CV must be a TXT, PDF, DOC, or DOCX file.'];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        if ($tmpName !== '' && is_file($tmpName)) {
            $mimeType = $this->detectMimeType($tmpName, $extension);
            if (!in_array($mimeType, $this->allowedMimeTypes[$extension], true)) {
                return ['cv' => 'CV file type does not match the uploaded extension.'];
            }
        }

        return [];
    }

    /** @param array<string, mixed> $file @return array<string, mixed> */
    public function uploadCV(array $file, int $userId): array
    {
        $errors = $this->validateCV($file);
        if ($errors !== []) {
            throw new RuntimeException(reset($errors));
        }

        $config = require BASE_PATH . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'app.php';
        $directory = (string) $config['cv_upload_path'];

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Unable to create CV upload directory.');
        }

        $extension = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
        $storedFilename = sprintf('cv_%d_%s.%s', $userId, bin2hex(random_bytes(16)), $extension);
        $destination = $directory . DIRECTORY_SEPARATOR . $storedFilename;

        if (!move_uploaded_file((string) $file['tmp_name'], $destination)) {
            throw new RuntimeException('Unable to store uploaded CV.');
        }

        return [
            'original_filename' => basename((string) $file['name']),
            'stored_filename' => $storedFilename,
            'file_path' => $destination,
            'file_size' => (int) $file['size'],
            'mime_type' => $this->detectMimeType($destination, $extension),
        ];
    }

    /** @param array<string, mixed>|null $record */
    public function deleteCV(?array $record): void
    {
        if ($record && isset($record['file_path']) && is_file((string) $record['file_path'])) {
            unlink((string) $record['file_path']);
        }
    }

    /** @param array<string, mixed>|null $record @return array<string, mixed>|null */
    public function getCV(?array $record): ?array
    {
        if (!$record) {
            return null;
        }

        return [
            'id' => (int) $record['id'],
            'originalFilename' => $record['original_filename'],
            'storedFilename' => $record['stored_filename'],
            'fileSize' => (int) $record['file_size'],
            'mimeType' => $record['mime_type'],
            'uploadedAt' => $record['uploaded_at'],
        ];
    }

    private function detectMimeType(string $path, string $extension): string
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = $finfo ? (finfo_file($finfo, $path) ?: '') : '';
        if ($finfo) {
            finfo_close($finfo);
        }

        return $mimeType !== '' ? $mimeType : $this->extensions[$extension];
    }
}
