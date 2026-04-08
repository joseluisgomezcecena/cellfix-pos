@extends('layouts.app')
@section('title', __('promocode::lang.edit_promo_company'))

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>{{ __('promocode::lang.edit_promo_company') }}</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['action' => ['\Modules\PromoCode\Http\Controllers\PromoCompanyController@update', $promoCompany->id], 'method' => 'put', 'id' => 'promo_company_form']) !!}

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('company_name_display', __('promocode::lang.company_name') . ':*') !!}
                        {!! Form::text('company_name', $promoCompany->company_name, ['class' => 'form-control', 'required', 'id' => 'company_name']); !!}
                        <p class="help-block">{{ __('promocode::lang.company_name_readonly') }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('is_active', __('promocode::lang.is_active') . ':*') !!}
                        {!! Form::select('is_active', [1 => __('promocode::lang.active'), 0 => __('promocode::lang.inactive')], $promoCompany->is_active, ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('description', __('promocode::lang.description') . ':') !!}
                        {!! Form::textarea('description', $promoCompany->description, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('promocode::lang.description_help')]); !!}
                    </div>
                </div>
            </div>

            <h4>{{ __('promocode::lang.category_discounts') }}</h4>
            <p class="help-block">{{ __('promocode::lang.category_discount_help') }}</p>
            <hr>

            @foreach($categories as $categoryKey => $categoryName)
            @php
                $discount = $categoryDiscounts[$categoryKey] ?? null;
                $enabled = $discount ? true : false;
            @endphp
            <div class="row category-discount-row">
                <div class="col-md-3">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('categories[' . $categoryKey . '][enabled]', 1, $enabled, ['id' => 'category_' . $categoryKey . '_enabled', 'class' => 'category-enable-checkbox']); !!}
                            <strong>{{ $categoryName }}</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('categories[' . $categoryKey . '][type]', __('promocode::lang.discount_type') . ':') !!}
                        {!! Form::select('categories[' . $categoryKey . '][type]', ['percentage' => __('promocode::lang.percentage'), 'fixed' => __('promocode::lang.fixed')], $discount['type'] ?? 'percentage', ['class' => 'form-control category-discount-input', 'disabled' => !$enabled]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('categories[' . $categoryKey . '][value]', __('promocode::lang.discount_value') . ':') !!}
                        {!! Form::number('categories[' . $categoryKey . '][value]', $discount['value'] ?? null, ['class' => 'form-control category-discount-input', 'step' => '0.01', 'min' => '0', 'placeholder' => '0.00', 'disabled' => !$enabled]); !!}
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">{{ __('messages.update') }}</button>
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Enable/disable category discount inputs based on checkbox
        $('.category-enable-checkbox').change(function(){
            var $row = $(this).closest('.category-discount-row');
            var $inputs = $row.find('.category-discount-input');

            if($(this).is(':checked')) {
                $inputs.prop('disabled', false);
            } else {
                $inputs.prop('disabled', true);
            }
        });

        // Form validation - ensure company name is provided
        $('#promo_company_form').submit(function(e) {
            if (!$('#company_name').val()) {
                e.preventDefault();
                alert('{{ __("promocode::lang.please_enter_company_name") }}');
                return false;
            }
        });
    });
</script>
@endsection
