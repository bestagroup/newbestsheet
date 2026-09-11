<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MediaFile;
use App\Services\InvestmentWorkflowAccessService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MediaDownloadController extends Controller
{
    public function __invoke(
        Request $request,
        MediaFile $media,
        InvestmentWorkflowAccessService $workflowAccess
    ): StreamedResponse {
        $user = $request->user();
        $project = $media->project;
        $isOwner = $project && (int) $project->user_id === (int) $user->getKey();
        $canViewWorkflow = $project && $workflowAccess->canViewProject($user, (int) $project->getKey());
        $canManageFiles = Gate::forUser($user)->allows('can-access', ['filemanager', 'view']);

        abort_unless($isOwner || $canViewWorkflow || $canManageFiles, 403);
        abort_if($media->scan_status === 'infected', 410, 'این فایل به‌دلیل آلودگی امنیتی قرنطینه شده است.');
        abort_if($media->scan_status === 'pending', 423, 'بررسی امنیتی فایل هنوز تکمیل نشده است.');

        $disk = Storage::disk($media->disk ?: 'public');
        abort_unless($disk->exists($media->file_path), 404);

        $inlineMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'audio/mpeg', 'video/mp4'];
        $disposition = $request->boolean('inline') && in_array($media->mime, $inlineMimes, true)
            ? 'inline'
            : 'attachment';
        $response = $disk->response(
            $media->file_path,
            $media->original_name ?: $media->name,
            ['Content-Type' => $media->mime ?: 'application/octet-stream'],
            $disposition
        );

        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
