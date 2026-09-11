<form data-type="update" data-id="{{ $financialstatement->id }}" data-table-target="#financialstatement"
      class="row g-4" method="POST" action="{{ route('financialstatement.update', $financialstatement->id) }}">
    @csrf
    @method('PATCH')

    @include('panel.partials.financial-statement-fields', [
        'statement' => $financialstatement,
        'idPrefix' => 'edit_statement_'.$financialstatement->id,
    ])

    <div class="col-12 d-flex justify-content-end gap-2 statement-form-actions">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">انصراف</button>
        <button type="submit" class="btn btn-primary">
            <i class="mdi mdi-content-save-outline me-1"></i>ذخیره تغییرات
        </button>
    </div>
</form>
