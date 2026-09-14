<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

trait FileUploadTrait
{
    public function handleFileUpload(
        Request $request,
        string $fieldName,
        ?string $oldPath = null,
        string $module = 'general',
        string $prefix = ''
    ): ?string {
        if (!$request->hasFile($fieldName)) {
            return null;
        }

        // Delete old file if exists
        $this->deleteFile($oldPath);

        $file = $request->file($fieldName);

        $fileName = $this->buildFileName($file, $prefix);

        $directory = "uploads/{$module}";
        $filePath = "{$directory}/{$fileName}";

        // Create directory if not exists
        if (!File::exists(public_path($directory))) {
            File::makeDirectory(public_path($directory), 0755, true);
        }

        $file->move(public_path($directory), $fileName);

        return $filePath;
    }

    /**
     * Handle multiple file uploads
     */
    public function handleMultipleFileUpload(
        Request $request,
        string $fieldName,
        array $oldPaths = [],
        string $module = 'general',
        string $prefix = ''
    ): array {
        if (!$request->hasFile($fieldName)) {
            return [];
        }

        $files = $request->file($fieldName);

        // Ensure it's an array
        if (!is_array($files)) {
            $files = [$files];
        }

        $uploadedPaths = [];

        foreach ($files as $index => $file) {
            // Skip if file is not valid
            if (!$file->isValid()) {
                continue;
            }

            $fileName = $this->buildFileName($file, $prefix === '' ? '' : $prefix . '_' . ($index + 1));

            $directory = "uploads/{$module}";
            $filePath = "{$directory}/{$fileName}";

            // Create directory if not exists
            $this->createDirectory($directory);

            $file->move(public_path($directory), $fileName);
            $uploadedPaths[] = $filePath;
        }

        // Delete old files if new ones were uploaded
        if (!empty($uploadedPaths) && !empty($oldPaths)) {
            foreach ($oldPaths as $oldPath) {
                $this->deleteFile($oldPath);
            }
        }

        return $uploadedPaths;
    }

    /**
     * Build the on-disk filename for an upload.
     *
     * The caller's prefix is a human label -- a document name typed by a
     * clerk -- so it is sanitised before it reaches a path, and a random
     * token is always appended. Without the token two customers who both
     * upload a "NIC Copy" write to the same file and the second silently
     * overwrites the first, leaving both document rows pointing at one
     * scan.
     */
    private function buildFileName($file, string $prefix): string
    {
        $extension = strtolower(preg_replace('/[^A-Za-z0-9]/', '', (string) $file->getClientOriginalExtension()));
        if ($extension === '') {
            $extension = 'bin';
        }

        $clean = preg_replace('/[^\p{L}\p{N}\-_]+/u', '_', trim($prefix));
        $clean = trim((string) $clean, '._');
        $clean = mb_substr($clean, 0, 80);

        $unique = Str::random(8);

        return ($clean === '' ? $unique : $clean . '_' . $unique) . '.' . $extension;
    }

    /**
     * Helper method to create directory
     */
    private function createDirectory(string $directory): void
    {
        if (!File::exists(public_path($directory))) {
            File::makeDirectory(public_path($directory), 0755, true, true);
        }
    }

    /**
     * Delete a single file
     */
    public function deleteFile(?string $path): void
    {
        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }

    /**
     * Delete multiple files
     */
    public function deleteMultipleFiles(array $paths): void
    {
        foreach ($paths as $path) {
            $this->deleteFile($path);
        }
    }
}
