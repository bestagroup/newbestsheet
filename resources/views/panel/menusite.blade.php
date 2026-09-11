@extends('layouts.base')
@section('title', 'مدیریت منو سایت')
@section('style')
    <link rel="stylesheet" href="{{ asset('assets/vendor/css/rtl/dataTables.dataTables.min.css') }}"/>
@endsection
@section('content')
<div class="card"><div class="card-body">
    <div class="d-flex justify-content-between align-items-center mb-3"><h5 class="card-title mb-0">{{ $thispage['list'] }}</h5><button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">{{ $thispage['add'] }}</button></div>
    <div class="table-responsive"><table class="table table-striped table-bordered yajra-datatable"><thead><tr class="table-light"><th>اولویت</th><th>عنوان</th><th>آدرس</th><th>کلاس</th><th>کنترلر</th><th>وضعیت</th><th>عملیات</th></tr></thead></table></div>
</div></div>

<div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-dialog-centered"><div class="modal-content text-center"><div class="modal-header border-bottom-0"><h5 class="modal-title w-100">{{ $thispage['delete'] }}</h5><button type="button" class="btn-close position-absolute start-0 mx-3" data-bs-dismiss="modal"></button></div><div class="modal-body">آیا از حذف این منو مطمئن هستید؟</div><div class="modal-footer justify-content-center"><button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button><button type="button" class="btn btn-danger" id="confirmDelete">حذف</button></div></div></div></div>

@foreach(['add' => 'addModal', 'edit' => 'editModal'] as $mode => $modalId)
<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">{{ $mode === 'add' ? $thispage['add'] : $thispage['edit'] }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div><div class="modal-body"><form id="{{ $mode }}form" method="POST" @if($mode === 'add') action="{{ route('menusite.store') }}" @endif>@csrf @if($mode === 'edit') @method('PATCH') @endif @include('panel.partials.menu-site-form', ['prefix' => $mode.'_'])<div class="text-end mt-3"><button type="submit" class="btn btn-primary">ذخیره اطلاعات</button></div></form></div></div></div></div>
@endforeach
@endsection
@section('script')
<script src="{{ asset('assets/vendor/js/dataTables.min.js') }}"></script>
<script>
$(function(){
 const baseUrl=@json(url('panel/menusite')), csrf=@json(csrf_token()); let deleteId=null;
 const table=$('.yajra-datatable').DataTable({processing:true,serverSide:true,ajax:@json(route('menusite.index')),columns:[{data:'priority',name:'menus.priority'},{data:'title',name:'menus.title'},{data:'slug',name:'menus.slug'},{data:'class',name:'menus.class'},{data:'controller',name:'menus.controller'},{data:'status',name:'menus.status'},{data:'action',name:'action',orderable:false,searchable:false}],language:{url:@json(asset('assets/vendor/js/fa.json'))}});
 const editModal=new bootstrap.Modal(document.getElementById('editModal')), deleteModal=new bootstrap.Modal(document.getElementById('deleteModal'));
 const error=x=>Swal.fire('خطا',x.responseJSON?.message||'عملیات انجام نشد.','error');
 function populate(d){['title','tab_title','page_title','submenu','class','controller','status','home_show','keyword','description'].forEach(k=>{const e=document.getElementById('edit_'+k);if(e)e.value=d[k]??'';});}
 $(document).on('click','.edit-btn',function(){const id=Number($(this).data('id'));$.get(`${baseUrl}/${id}/edit`).done(r=>{populate(r.data);document.getElementById('editform').dataset.id=id;editModal.show();}).fail(error);});
 $(document).on('click','.delete-btn',function(){deleteId=Number($(this).data('id'));deleteModal.show();});
 $('#addform,#editform').on('submit',function(e){e.preventDefault();const f=this,isEdit=f.id==='editform',id=isEdit?Number(f.dataset.id):null,b=f.querySelector('button[type="submit"]'),old=b.innerHTML;b.disabled=true;b.innerHTML='<span class="spinner-border spinner-border-sm"></span> در حال ذخیره...';$.ajax({url:isEdit?`${baseUrl}/${id}`:baseUrl,method:isEdit?'PATCH':'POST',data:$(f).serialize(),headers:{'X-CSRF-TOKEN':csrf}}).done(r=>{isEdit?editModal.hide():bootstrap.Modal.getInstance(document.getElementById('addModal')).hide();if(!isEdit)f.reset();table.ajax.reload(null,false);Swal.fire('موفق',r.message||'اطلاعات ذخیره شد.','success');}).fail(error).always(()=>{b.disabled=false;b.innerHTML=old;});});
 $('#confirmDelete').on('click',function(){if(!deleteId)return;const b=this,old=b.innerHTML;b.disabled=true;b.innerHTML='<span class="spinner-border spinner-border-sm"></span> در حال حذف...';$.ajax({url:`${baseUrl}/${deleteId}`,method:'DELETE',data:{_token:csrf},headers:{'X-CSRF-TOKEN':csrf}}).done(r=>{deleteModal.hide();table.ajax.reload(null,false);Swal.fire('موفق',r.message||'منو حذف شد.','success');}).fail(error).always(()=>{b.disabled=false;b.innerHTML=old;});});
});
</script>
@endsection
