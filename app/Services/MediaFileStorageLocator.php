<?php

namespace App\Services;

use App\Models\MediaFile;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MediaFileStorageLocator
{
    /**
     * Resolve both current private paths and legacy public paths without
     * changing the stored metadata or moving the underlying file.
     *
     * @return array{disk: FilesystemAdapter, path: string}|null
     */
    public function locate(MediaFile $media): ?array
    {
        foreach ($this->candidateDisks($media) as $diskName) {
            try {
                $disk = Storage::disk($diskName);
            } catch (Throwable) {
                continue;
            }

            foreach ($this->candidatePaths((string) $media->file_path) as $path) {
                try {
                    if ($disk->exists($path)) {
                        return ['disk' => $disk, 'path' => $path];
                    }
                } catch (Throwable) {
                    continue 2;
                }
            }
        }

        return null;
    }

    /** @return list<string> */
    private function candidateDisks(MediaFile $media): array
    {
        return collect([
            $media->disk ?: 'public',
            'public',
            config('investment.documents.disk', 'investment_documents'),
        ])->filter()->unique()->values()->all();
    }

    /** @return list<string> */
    private function candidatePaths(string $storedPath): array
    {
        $path = ltrim(str_replace('\\', '/', trim($storedPath)), '/');
        $paths = collect([$path]);

        foreach (['storage/app/public/', 'public/storage/', 'storage/', 'public/'] as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $paths->push(substr($path, strlen($prefix)));
            }
        }

        return $paths->filter()->unique()->values()->all();
    }
}
