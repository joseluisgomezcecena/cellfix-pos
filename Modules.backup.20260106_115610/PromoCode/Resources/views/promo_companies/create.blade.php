@extends('layouts.app')
@section('title', __('promocode::lang.add_promo_company'))

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>{{ __('promocode::lang.add_promo_company') }}</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::open(['action' => ['\Modules\PromoCode\Http\Controllers\PromoCompanyController@store'], 'method' => 'post', 'id' => 'promo_company_form']) !!}

    <div class="box box-solid">
        <div class="box-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('business_id', __('promocode::lang.company_name') . ':*') !!}
                        {!! Form::hidden('company_name', null, ['id' => 'company_name']); !!}
                        {!! Form::select('business_id', [], null, ['class' => 'form-control select2', 'id' => 'business_id', 'placeholder' => __('promocode::lang.company_name_help'), 'required', 'style' => 'width: 100%;']); !!}
                        <p class="help-block">{{ __('promocode::lang.company_name_help') }}</p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('is_active', __('promocode::lang.is_active') . ':*') !!}
                        {!! Form::select('is_active', [1 => __('promocode::lang.active'), 0 => __('promocode::lang.inactive')], 1, ['class' => 'form-control', 'required']); !!}
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="form-group">
                        {!! Form::label('description', __('promocode::lang.description') . ':') !!}
                        {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 3, 'placeholder' => __('promocode::lang.description_help')]); !!}
                    </div>
                </div>
            </div>

            <h4>{{ __('promocode::lang.category_discounts') }}</h4>
            <p class="help-block">{{ __('promocode::lang.category_discount_help') }}</p>
            <hr>

            @foreach($categories as $categoryKey => $categoryName)
            <div class="row category-discount-row">
                <div class="col-md-3">
                    <div class="checkbox">
                        <label>
                            {!! Form::checkbox('categories[' . $categoryKey . '][enabled]', 1, false, ['id' => 'category_' . $categoryKey . '_enabled', 'class' => 'category-enable-checkbox']); !!}
                            <strong>{{ $categoryName }}</strong>
                        </label>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('categories[' . $categoryKey . '][type]', __('promocode::lang.discount_type') . ':') !!}
                        {!! Form::select('categories[' . $categoryKey . '][type]', ['percentage' => __('promocode::lang.percentage'), 'fixed' => __('promocode::lang.fixed')], 'percentage', ['class' => 'form-control category-discount-input', 'disabled' => true]); !!}
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        {!! Form::label('categories[' . $categoryKey . '][value]', __('promocode::lang.discount_value') . ':') !!}
                        {!! Form::number('categories[' . $categoryKey . '][value]', null, ['class' => 'form-control category-discount-input', 'step' => '0.01', 'min' => '0', 'placeholder' => '0.00', 'disabled' => true]); !!}
                    </div>
                </div>
            </div>
            @endforeach

        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary pull-right">{{ __('messages.save') }}</button>
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection

@section('javascript')
<script type="text/javascript">
    $(document).ready(function(){
        // Initialize Select2 for business selection with AJAX autocomplete
        $('#business_id').select2({
            ajax: {
                url: '/contacts/customers',
                dataType: 'json',
                delay: 250,
                data: function (params) {
                    return {
                        q: params.term, // search term
                        page: params.page
                    };
                },
                processResults: function (data) {
                    return {
                        results: data.map(function(contact) {
                            // Combine name and business name for display
                            var displayText = contact.text;
                            if (contact.supplier_business_name) {
                                displayText = contact.supplier_business_name + ' - ' + contact.text;
                            }
                            return {
                                id: contact.id,
                                text: displayText,
                                name: contact.text,
                                business_name: contact.supplier_business_name || contact.text
                            };
                        })
                    };
                },
                cache: true
            },
            placeholder: '{{ __("promocode::lang.company_name_help") }}',
            allowClear: true,
            minimumInputLength: 0
        });

        // When a business is selected, populate company_name hidden field
        $('#business_id').on('select2:select', function(e) {
            var data = e.params.data;
            // Use the business name or contact name as the company name
            var companyName = data.business_name || data.text;
            $('#company_name').val(companyName);
        });

        // Clear company_name when selection is cleared
        $('#business_id').on('select2:clear', function(e) {
            $('#company_name').val('');
        });

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

        // Form validation - ensure business is selected
        $('#promo_company_form').submit(function(e) {
            if (!$('#business_id').val()) {
                e.preventDefault();
                alert('{{ __("promocode::lang.please_select_business") }}');
                return false;
            }
        });
    });
</script>
@endsection
