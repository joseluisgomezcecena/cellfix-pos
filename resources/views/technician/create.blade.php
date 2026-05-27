<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => route('technicians.store'), 'method' => 'post', 'id' => 'technician_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">@lang('lang_v1.add_technician')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('user.name') . ':*') !!}
                {!! Form::text('name', null, ['class' => 'form-control', 'required', 'placeholder' => __('user.name')]) !!}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('phone', __('contact.mobile') . ':') !!}
                        {!! Form::text('phone', null, ['class' => 'form-control', 'placeholder' => __('contact.mobile')]) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email') . ':') !!}
                        {!! Form::email('email', null, ['class' => 'form-control', 'placeholder' => __('business.email')]) !!}
                    </div>
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('location_ids', __('lang_v1.assigned_locations') . ':*') !!}
                {!! Form::select('location_ids[]', $locations, null, [
                    'class' => 'form-control select2',
                    'multiple',
                    'required',
                    'style' => 'width: 100%;',
                ]) !!}
                <small class="text-muted">@lang('lang_v1.technician_locations_help')</small>
            </div>

            {{-- Comisión por reparación por técnico: REEMPLAZADA por comisión por producto
                 (Técnicos → botón "Comisiones por reparación"). Se deja comentada por si se necesita después.
            <div class="form-group">
                {!! Form::label('commission_per_repair', __('lang_v1.commission_per_repair') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    {!! Form::number('commission_per_repair', 0, ['class' => 'form-control', 'min' => '0', 'step' => '0.01']) !!}
                </div>
                <small class="text-muted">@lang('lang_v1.commission_per_repair_help')</small>
            </div>
            --}}

            <div class="form-group">
                {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>

            <div class="form-group">
                <label>
                    {!! Form::checkbox('is_active', 1, true) !!} <strong>@lang('lang_v1.is_active')</strong>
                </label>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.save')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
