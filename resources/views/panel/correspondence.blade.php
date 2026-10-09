@extends('layouts.base')

@section('title', 'مکاتبات')

@section('style')
    <link rel="stylesheet" href="{{ asset('assets/css/page-correspondence.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')

    {{-- Skeleton --}}
    <div id="correspondence-skeleton" class="card mb-3">
        <div class="card-body">
            <div class="skeleton line w-25 mb-2"></div>
            <div class="skeleton line w-100 mb-3"></div>
            <div class="skeleton line w-75 mb-2"></div>
            <div class="skeleton line w-100" style="height:300px"></div>
        </div>
    </div>

    {{-- Body --}}
    <div id="correspondence-body" class="d-none">

        <div class="card">
            <div class="card-body">

                {{-- Search --}}
                <div class="mb-3 d-flex align-items-center gap-2">
                    <input id="searchConversation" type="text"
                           class="form-control"
                           placeholder="جستجوی مکالمه">
                    <button id="btnOpenCompose"
                            class="btn btn-primary btn-compose-inline"
                            data-bs-toggle="modal"
                            data-bs-target="#composeModal"
                            title="پیام جدید">
                        <span class="mdi mdi-plus"></span>
                    </button>
                </div>

                {{-- Filters --}}
                <div id="filterChips" class="mb-3 filter-chips">
                    <span class="chip active" data-filter="all">همه</span>
                    <span class="chip" data-filter="sent">ارسالی</span>
                    <span class="chip" data-filter="received">دریافتی</span>
                </div>

                {{-- List --}}
                <div id="conversationList"></div>

            </div>
        </div>

    </div>

    {{-- Compose Button (moved inline next to search) --}}

    {{-- Compose Modal --}}
    <div class="modal fade" id="composeModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">پیام جدید</h5>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <form id="composeForm" enctype="multipart/form-data">
                        @csrf

                        <div class="mb-3">
                            <label>موضوع</label>
                            <input id="composeSubject" name="subject" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label>گیرندگان</label>
                            <select id="composeRecipients" data-select2-manual
                                    name="recipients[]"
                                    class="form-control select2"
                                    multiple>
                                @foreach($users as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="mb-3">
                            <label>متن پیام</label>
                            <textarea id="composeBody" name="body"
                                      class="form-control"></textarea>
                        </div>

                        <div class="mb-3">
                            <label>ضمیمه</label>
                            <input id="composeAttachment"
                                   type="file"
                                   name="attachments[]"
                                   class="form-control"
                                   multiple>
                        </div>

                        <button type="button"
                                id="btnComposeSend"
                                class="btn btn-primary">
                            ارسال
                        </button>

                    </form>
                </div>

            </div>
        </div>
    </div>

    {{-- View Conversation Modal --}}
    <div class="modal fade" id="viewConversationModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <div>
                        <h5 id="viewSubject"></h5>
                        <div id="viewParticipants"></div>
                    </div>
                    <button class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <div id="viewMessages"
                         style="max-height:65vh;overflow:auto"></div>
                </div>

            </div>
        </div>
    </div>

@endsection

@section('script')

    {{-- Pusher Config --}}
    <script>
        window.PUSHER_CONFIG = {
            key: "{{ config('broadcasting.connections.pusher.key') }}",
            cluster: "{{ config('broadcasting.connections.pusher.options.cluster') }}"
        };

        window.CORRESPONDENCE_POST_URL = "{{ route('correspondence.store') }}";
        window.CORRESPONDENCE_REFRESH_URL = "{{ route('correspondence.data') }}";
        window.CORRESPONDENCE_READ_URL = "{{ url('correspondence') }}/__CONVERSATION_ID__/read";
    </script>

    {{-- DATA --}}
    <script>
        window.CORRESPONDENCE_DATA = {
            authUserId: {{ auth()->id() }},
            users: {!! json_encode(
                $users->mapWithKeys(fn($u) => [
                    $u->id => [
                        'id' => $u->id,
                        'name' => $u->name,
                    ]
                ]),
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) !!},
            conversations: {!! json_encode(
                $conversations->map(function ($c) {
                    $messages = $c->messages->sortBy('created_at')->values();
                    $rootMsg = $messages->firstWhere('parent_id', null) ?? $messages->first();
                    $replies = $rootMsg
                        ? $messages->where('id', '!=', $rootMsg->id)->values()
                        : collect();
                    $lastMsg = $messages->last();

                    $mapMessage = function ($m) {
                        return [
                            'id' => 'm'.$m->id,
                            'senderId' => $m->sender_id,
                            'body' => $m->body,
                            'time' => $m->created_at,
                            'attachments' => $m->attachments->map(fn($a) => [
                                'id' => $a->id,
                                'name' => $a->original_name,
                                'url' => $a->url,
                            ])->toArray()
                        ];
                    };

                    return [
                        'id' => $c->id,
                        'subject' => $c->subject,
                        'participants' => $c->users->pluck('id')->toArray(),
                        'unread' => (int) ($c->current_unread_count ?? 0),
                        'readUrl' => route('correspondence.read', $c->id),
                        'lastActivity' => optional($lastMsg)->created_at ?? optional($c->lastMessage)->created_at,
                        'messages' => [
                            'root' => $rootMsg ? $mapMessage($rootMsg) : null,
                            'replies' => $replies->map($mapMessage)->values()->toArray()
                        ]
                    ];
                })->values(),
                JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            ) !!}
        };
    </script>

    <script src="{{ asset('js/pusher.min.js') }}"></script>
    <script src="{{ asset('js/correspondence.js') }}"></script>

@endsection
