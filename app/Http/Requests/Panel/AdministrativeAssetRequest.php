<?php

namespace App\Http\Requests\Panel;

use App\Models\AdministrativeAsset;
use App\Support\LocalizedInputNormalizer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdministrativeAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'asset_code' => LocalizedInputNormalizer::digits($this->input('asset_code')),
            'serial_number' => LocalizedInputNormalizer::digits($this->input('serial_number')),
            'property_tag' => LocalizedInputNormalizer::digits($this->input('property_tag')),
            'quantity' => LocalizedInputNormalizer::unsignedInteger($this->input('quantity')),
            'purchase_cost' => LocalizedInputNormalizer::unsignedInteger($this->input('purchase_cost')),
            'acquisition_date' => LocalizedInputNormalizer::jalaliDate($this->input('acquisition_date')),
        ]);
    }

    public function rules(): array
    {
        $asset = $this->route('asset');
        $assetId = $asset instanceof AdministrativeAsset ? $asset->getKey() : $asset;

        return [
            'asset_code' => ['required', 'string', 'max:80', Rule::unique('administrative_assets')->ignore($assetId)],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'serial_number' => ['nullable', 'string', 'max:150', Rule::unique('administrative_assets')->ignore($assetId)],
            'property_tag' => ['nullable', 'string', 'max:100', Rule::unique('administrative_assets')->ignore($assetId)],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000000'],
            'unit' => ['required', 'string', 'max:40'],
            'acquisition_date' => ['nullable', 'string', 'max:20'],
            'purchase_cost' => ['nullable', 'integer', 'min:0'],
            'location' => ['nullable', 'string', 'max:255'],
            'custodian_employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->whereNull('deleted_at')->where('status', 'active')],
            'condition' => ['required', Rule::in(array_keys(AdministrativeAsset::conditionLabels()))],
            'status' => ['required', Rule::in(array_keys(AdministrativeAsset::statusLabels()))],
            'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
