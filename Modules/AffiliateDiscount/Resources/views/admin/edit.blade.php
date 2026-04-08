@extends('layouts.app')
@section('title', 'Edit Affiliated Business')

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Edit Affiliated Business</h1>
</section>

<!-- Main content -->
<section class="content">
    {!! Form::model($affiliate, ['url' => route('affiliate-discounts.update', $affiliate->id), 'method' => 'PUT']) !!}

    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary', 'title' => 'Business Information'])
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('contact_id', 'Business Contact:*') !!}
                            {!! Form::select('contact_id', $contacts, null, ['class' => 'form-control select2', 'placeholder' => 'Select business contact', 'required']) !!}
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label>
                                {!! Form::checkbox('is_active', 1, $affiliate->is_active, ['class' => 'input-icheck']) !!}
                                Active
                            </label>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('contact_phone', 'Contact Phone:') !!}
                            {!! Form::text('contact_phone', null, ['class' => 'form-control', 'placeholder' => 'e.g., +52 123 456 7890']) !!}
                        </div>
                    </div>

                    <div class="col-sm-6">
                        <div class="form-group">
                            {!! Form::label('contact_email', 'Contact Email:') !!}
                            {!! Form::email('contact_email', null, ['class' => 'form-control', 'placeholder' => 'e.g., contact@business.com']) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('contract_number', 'Contract/Agreement Number:') !!}
                            {!! Form::text('contract_number', null, ['class' => 'form-control', 'placeholder' => 'e.g., CTR-2025-001']) !!}
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('start_date', 'Agreement Start Date:') !!}
                            {!! Form::text('start_date', null, ['class' => 'form-control datepicker', 'placeholder' => 'YYYY-MM-DD']) !!}
                        </div>
                    </div>

                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('end_date', 'Agreement End Date:') !!}
                            {!! Form::text('end_date', null, ['class' => 'form-control datepicker', 'placeholder' => 'YYYY-MM-DD (Optional)']) !!}
                            <p class="help-block"><small>Leave empty for indefinite</small></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <div class="form-group">
                            {!! Form::label('notes', 'Notes:') !!}
                            {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary">@lang('messages.save')</button>
                        <a href="{{ route('affiliate-discounts.options', $affiliate->id) }}" class="btn btn-success">
                            <i class="fa fa-percent"></i> Manage Discount Options
                        </a>
                        <a href="{{ route('affiliate-discounts.index') }}" class="btn btn-default">@lang('messages.cancel')</a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection
