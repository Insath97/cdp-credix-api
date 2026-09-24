<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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

        return $this->storeUploadedFile($request->file($fieldName), $module, $prefix);
    }

    /**
     * Store one already-resolved UploadedFile and return its public path.
     *
     * The request-based helper above only reaches a top-level field. Nested
     * payloads -- a customer created with its documents inline -- hand the
     * file over directly, so they call this.
     */
    public function storeUploadedFile(
        UploadedFile $file,
        string $module = 'general',
        string $prefix = '',
        ?string $oldPath = null
    ): string {
        $this->deleteFile($oldPath);

        $fileName = $this->buildFileName($file, $prefix);
        $directory = "uploads/{$module}";

        // Written under public/, by explicit instruction.
        //
        // KNOWN EXPOSURE, accepted deliberately. These are identity cards,
        // bank statements and pay slips. public/ is served straight off disk
        // by the web server, so anything here is downloadable by anyone who
        // has the URL -- no token, no session, no permission check, and no
        // entry in the activity log. The 8-character token buildFileName()
        // appends makes a name hard to guess; it does not make it private, and
        // a URL once shared or leaked cannot be revoked.
        //
        // If that is ever reconsidered, the change is one line here plus
        // moving the files: everything else already works either way, because
        // the returned string is a path relative to the serving root and
        // resolveStoredFile() searches both locations.
        $this->createDirectory($directory);
        $file->move(public_path($directory), $fileName);

        return "{$directory}/{$fileName}";
    }

    /**
     * Turn a stored path into an absolute readable one, or null.
     *
     * Looks under public/ first, where uploads are written, then falls back to
     * private storage so anything uploaded while that was the target still
     * opens. Both are searched on every call, so the two eras coexist.
     *
     * Both candidates are resolved and checked to be inside their own base
     * directory. That containment check is the thing standing between a stored
     * path and an arbitrary file read: a path of "../.env" resolves cleanly and
     * exists, and without this it would be streamed to whoever asked.
     */
    public function resolveStoredFile(?string $storedPath): ?string
    {
        if (!$storedPath) {
            return null;
        }

        $candidates = [
            public_path(),
            storage_path('app/private'),
            storage_path('app'),
        ];

        foreach ($candidates as $base) {
            $real = realpath($base . DIRECTORY_SEPARATOR . $storedPath);
            $realBase = realpath($base);

            if ($real === false || $realBase === false) {
                continue;
            }

            if (!str_starts_with($real, $realBase . DIRECTORY_SEPARATOR)) {
                // Escaped its base. Not an error to report back to the caller,
                // because saying so confirms what is out there.
                continue;
            }

            if (is_file($real)) {
                return $real;
            }
        }

        return null;
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

            $uploadedPaths[] = $this->storeUploadedFile(
                $file,
                $module,
                $prefix === '' ? '' : $prefix . '_' . ($index + 1)
            );
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
    private function buildFileName(UploadedFile $file, string $prefix): string
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
        // Resolved and contained first, for the same reason reads are: this
        // used to be File::delete(public_path($path)) on a path the client
        // could choose, which is an arbitrary file DELETE rather than merely an
        // arbitrary read.
        $absolute = $this->resolveStoredFile($path);

        if ($absolute !== null) {
            File::delete($absolute);
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
