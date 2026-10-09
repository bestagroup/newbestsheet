<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use App\Models\MessageAttachment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

final class ScanDocuments extends Command
{
    protected $signature = 'documents:scan {--limit=100}';

    protected $description = 'Scan pending local private documents with ClamAV; scanner failures remain quarantined.';

    public function handle(): int
    {
        if (! config('investment.documents.antivirus_enabled')) {
            $this->info('Antivirus scanning is disabled.');

            return self::SUCCESS;
        }
        $failures = 0;
        foreach ([MediaFile::class, MessageAttachment::class] as $model) {
            $model::query()->where('scan_status', 'pending')->limit(max(1, min(1000, (int) $this->option('limit'))))->get()->each(function ($file) use (&$failures, $model): void {
                try {
                    $diskName = $file->disk ?: 'investment_documents';
                    if (config('filesystems.disks.'.$diskName.'.driver') !== 'local') {
                        throw new \RuntimeException('Only local scanning is configured.');
                    }
                    $disk = Storage::disk($diskName);
                    $path = $file instanceof MediaFile ? $file->file_path : $file->path;
                    if (! $disk->exists($path)) {
                        throw new \RuntimeException('Pending file is missing.');
                    }
                    $process = new Process([(string) config('investment.documents.scanner', 'clamscan'), '--no-summary', '--', $disk->path($path)]);
                    $process->setTimeout(120)->run();
                    $code = $process->getExitCode();
                    if (! in_array($code, [0, 1], true)) {
                        throw new \RuntimeException('Scanner unavailable or failed.');
                    }
                    $file->update(['scan_status' => $code === 0 ? 'clean' : 'infected', 'scanned_at' => now()]);
                } catch (\Throwable $exception) {
                    $failures++;
                    $this->error($model.' #'.$file->id.': '.$exception->getMessage());
                }
            });
        }

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}
