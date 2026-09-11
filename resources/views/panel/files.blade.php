@extends('layouts.base')
@section('title', 'مدیریت فایل‌ها')
<style> table{margin: 0 auto;width: 100% !important;clear: both;border-collapse: collapse;table-layout: fixed;word-wrap:break-word;} .dt-layout-start{margin-right: 0 !important;} .dt-layout-end{margin-left: 0 !important;}</style>
<link href="{{asset('assets/vendor/css/rtl/materialdesignicons.min.css')}}" rel="stylesheet">

@section('content')
    <style>
        .file-gallery{display:flex;flex-wrap:wrap;gap:1rem}.file-item{position:relative;width:120px;text-align:center;margin:10px;border:1px solid #ddd;padding:10px;border-radius:6px}.file-actions{position:absolute;bottom:20px;left:50%;transform:translateX(-50%);display:none;flex-direction:row;gap:8px;z-index:10;justify-content:center;align-items:center}.file-item:hover .file-actions{display:flex}.file-actions a,.file-actions button{font-size:12px;padding:3px 6px;border:none;border-radius:4px;cursor:pointer}.delete-btn{background-color:#e3342f;color:#fff}.download-btn{background-color:#2ec145;color:#fff;transition:background-color .3s,color .3s}.download-btn:hover{background-color:#14771a;color:#fff}.file-icon{margin-bottom:8px;font-size:48px}.file-preview{width:100%;height:auto;border-radius:4px}.file-name{white-space:nowrap;overflow:hidden;text-overflow:ellipsis}@keyframes fadeIn{from{opacity:0;transform:scale(.95)}to{opacity:1;transform:scale(1)}}.fade-out{animation:.3s ease-in forwards fadeOut}.animate-fadeIn{animation:.3s ease-out fadeIn}.animate-fadeOut{animation:.5s ease-in forwards fadeOut}@keyframes fadeOut{to{transform:scale(.95);opacity:0;transform:translateY(10px)}}
    </style>

    <h4>فایل‌های این رکورد:</h4>

    <div class="file-gallery">
        @foreach ($files as $file)
            @php
                $extension = strtolower(pathinfo($file->file_path, PATHINFO_EXTENSION));
                $mime = $file->mime_type ?? '';
                $sizeInKB = number_format($file->size / 1024, 1);
            @endphp

            <div class="file-item" onclick="selectFile('{{ $file->file_path }}')" data-id="{{ $file->id }}">
                {{-- PREVIEW / ICON --}}
                @if ($file->mime == 'image/jpeg')
                    <img src="{{ $file->url }}" alt="{{ $file->file_name }}" class="file-preview">
                @elseif (Str::startsWith($mime, 'video/'))
                    <i class="mdi mdi-video-box mdi-48px file-icon text-blue-500"></i>
                @elseif (Str::startsWith($mime, 'audio/'))
                    <i class="mdi mdi-music mdi-48px file-icon text-pink-500"></i>
                @elseif ($file->mime == 'application/pdf')
                    <i class="mdi mdi-file-pdf-box mdi-48px file-icon text-red-500"></i>
                @elseif (Str::contains($mime, 'msword') || Str::contains($mime, 'wordprocessingml'))
                    <i class="mdi mdi-file-word mdi-48px file-icon text-blue-700"></i>
                @elseif (Str::contains($mime, 'ms-excel') || Str::contains($mime, 'spreadsheetml'))
                    <i class="mdi mdi-file-excel mdi-48px file-icon text-green-600"></i>
                @elseif (Str::contains($mime, 'powerpoint') || Str::contains($mime, 'presentationml'))
                    <i class="mdi mdi-file-powerpoint mdi-48px file-icon text-orange-500"></i>
                @elseif (in_array($extension, ['zip', 'rar', '7z']))
                    <i class="mdi mdi-folder-zip mdi-48px file-icon text-yellow-600"></i>
                @else
                    <i class="mdi mdi-file mdi-48px file-icon text-gray-500"></i>
                @endif

                {{-- ACTIONS --}}
                <div class="file-actions">
                    <button
                        type="button"
                        class="btn btn-sm btn-icon btn-outline-danger"
                        data-bs-toggle="modal"
                        data-bs-target="#deleteModal{{ $file->id }}"
                        id="deletesubmit_{{ $file->id }}"
                        data-id="{{ $file->id }}"
                        onclick="event.stopPropagation();">
                        <i class="mdi mdi-delete-outline"></i>
                    </button>

                    <a
                        href="{{ $file->url }}"
                        download
                        class="download-btn"
                        onclick="event.stopPropagation();">
                        دانلود
                    </a>
                </div>

                {{-- META --}}
                <div class="file-name mt-2 text-sm font-medium truncate" title="{{ $file->original_name }}">{{ $file->original_name }}</div>
                <div class="file-size text-xs text-gray-500">{{ $sizeInKB }} KB</div>
            </div>
        @endforeach
    </div>
    {{-- Put these AFTER the gallery, not inside file-item --}}
    @foreach ($files as $file)
        <div class="modal fade" id="deleteModal{{ $file->id }}" tabindex="-1" aria-labelledby="deleteModalLabel{{ $file->id }}" aria-hidden="true" onclick="event.stopPropagation();">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content text-center">
                    <div class="modal-header border-bottom-0">
                        <h5 class="modal-title w-100" id="deleteModalLabel{{ $file->id }}">حذف فایل</h5>
                        <button type="button" class="btn-close position-absolute start-0 mx-3" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        آیا از حذف این فایل مطمئن هستید؟
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
                        <button type="button" class="btn btn-danger js-confirm-delete" id="confirmdelete_{{ $file->id }}" data-id="{{ $file->id }}">حذف</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

@endsection
@section('script')
    <script>
        function selectFile(url) {
            if (window.opener && typeof window.opener.setFileUrl === 'function') {
                window.opener.setFileUrl(url);
                window.close();
            } else {
                alert("ارتباط با پنجره اصلی برقرار نشد.");
            }
        }
    </script>
    <script>
        jQuery(function($){

            // دکمه قرمز داخل مدال
            $('[id^=confirmdelete_]').on('click', function(e){
                e.preventDefault();
                e.stopPropagation(); // جلوگیری از بسته‌شدن پاپ‌آپ فایل‌منیجر

                var button = $(this);
                var id = button.data('id');
                var originalButtonHtml = button.html();

                button.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> در حال حذف...');

                $.ajax({
                    url: "{{ route('filemanager.destroy', ':id') }}".replace(':id', id),
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}",
                        id: id
                    },
                    success: function(resp){
                        // بستن مدال
                        var modalEl = document.getElementById('deleteModal' + id);
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        if (modal) modal.hide();

                        // حذف کارت فایل از گالری
                        $('.file-item[data-id="'+id+'"]').remove();
                    },
                    error: function(){
                        alert('مشکلی پیش آمد. لطفاً دوباره تلاش کنید.');
                    },
                    complete: function(){
                        button.prop('disabled', false).html(originalButtonHtml);
                    }
                });
            });

        });
    </script>

@endsection
