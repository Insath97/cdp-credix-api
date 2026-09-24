<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Move every already-uploaded file out of public/uploads into private storage.
 *
 * New uploads go straight to the private disk, but everything collected before
 * that change is still sitting under the web root, where the web server hands
 * it to anyone who asks. Until this has run, those files are public.
 *
 * Safe to run more than once: a file already present privately is left alone
 * and reported as skipped, and nothing is deleted unless the copy succeeded.
 */
class MoveUploadsOutOfWebRoot extends Command
{
    protected $signature = 'uploads:move-out-of-web-root {--dry-run : List what would move without touching anything}';

    protected $description = 'Move uploaded documents from public/uploads into private storage';

    public function handle(): int
    {
        $source = public_path('uploads');

        if (! File::isDirectory($source)) {
            $this->info('Nothing to do: public/uploads does not exist.');

            return self::SUCCESS;
        }

        $destinationRoot = storage_path('app/private/uploads');
        $dryRun = (bool) $this->option('dry-run');

        $moved = 0;
        $skipped = 0;
        $failed = 0;

        foreach (File::allFiles($source) as $file) {
            $relative = $file->getRelativePathname();
            $destination = $destinationRoot . DIRECTORY_SEPARATOR . $relative;

            if (File::exists($destination)) {
                $this->line("skip   uploads/{$relative} (already private)");
                $skipped++;
                continue;
            }

            if ($dryRun) {
                $this->line("would move  uploads/{$relative}");
                $moved++;
                continue;
            }

            File::ensureDirectoryExists(dirname($destination));

            // Copy, verify, then remove. A move that half-succeeds would lose
            // the only copy of someone's identity document.
            if (! File::copy($file->getPathname(), $destination) || ! File::exists($destination)) {
                $this->error("FAILED uploads/{$relative}");
                $failed++;
                continue;
            }

            File::delete($file->getPathname());
            $this->line("moved  uploads/{$relative}");
            $moved++;
        }

        $this->newLine();
        $this->info(($dryRun ? 'Would move' : 'Moved') . ": {$moved}   skipped: {$skipped}   failed: {$failed}");

        if ($failed > 0) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
