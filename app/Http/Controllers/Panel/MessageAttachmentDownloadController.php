<?php

namespace App\Http\Controllers\Panel;

use App\Http\Controllers\Controller;
use App\Models\MessageAttachment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MessageAttachmentDownloadController extends Controller
{
    public function __invoke(Request $request, MessageAttachment $attachment): StreamedResponse
    {
        $allowed = $attachment->message()
            ->whereHas('conversation.users', fn ($query) => $query->whereKey($request->user()->getKey()))
            ->exists();
        abort_unless($allowed, 403);
        abort_if($attachment->scan_status === 'infected', 410);
        abort_if($attachment->scan_status === 'pending', 423);

        $disk = Storage::disk($attachment->disk ?: 'public');
        abort_unless($disk->exists($attachment->path), 404);
        $inlineMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'audio/mpeg', 'video/mp4'];
        $disposition = $request->boolean('inline') && in_array($attachment->mime_type, $inlineMimes, true)
            ? 'inline'
            : 'attachment';
        $response = $disk->response(
            $attachment->path,
            $attachment->original_name,
            ['Content-Type' => $attachment->mime_type ?: 'application/octet-stream'],
            $disposition
        );
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        return $response;
    }
}
