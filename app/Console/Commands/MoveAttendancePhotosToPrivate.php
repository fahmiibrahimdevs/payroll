<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('attendances:move-photos-private {--dry-run : Count public photos without moving them}')]
#[Description('Move legacy attendance selfies out of public storage after deployment')]
class MoveAttendancePhotosToPrivate extends Command
{
    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $moved = 0;
        $failed = 0;

        foreach ($public->allFiles('attendances') as $path) {
            if ($this->option('dry-run')) {
                $moved++;

                continue;
            }

            try {
                $source = $public->get($path);
                if ($private->exists($path) && $private->get($path) !== $source) {
                    throw new \RuntimeException('Private destination already contains different content.');
                }
                if (! $private->exists($path) && ! $private->put($path, $source)) {
                    throw new \RuntimeException('Private write failed.');
                }
                if ($private->get($path) !== $source || ! $public->delete($path)) {
                    throw new \RuntimeException('Verification or public deletion failed.');
                }
                $moved++;
            } catch (\Throwable $exception) {
                $failed++;
                $this->error("{$path}: {$exception->getMessage()}");
            }
        }

        $this->info($this->option('dry-run') ? "Public photos to move: {$moved}" : "Photos moved: {$moved}; failed: {$failed}");

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
