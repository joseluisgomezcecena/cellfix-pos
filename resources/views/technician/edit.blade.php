<div class="modal-dialog" role="document">
    <div class="modal-content">
        {!! Form::open(['url' => route('technicians.update', [$technician->id]), 'method' => 'put', 'id' => 'technician_form']) !!}

        <div class="modal-header">
            <button type="button" class="close" data-dismiss="modal">&times;</button>
            <h4 class="modal-title">@lang('lang_v1.edit_technician')</h4>
        </div>

        <div class="modal-body">
            <div class="form-group">
                {!! Form::label('name', __('user.name') . ':*') !!}
                {!! Form::text('name', $technician->name, ['class' => 'form-control', 'required']) !!}
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('phone', __('contact.mobile') . ':') !!}
                        {!! Form::text('phone', $technician->phone, ['class' => 'form-control']) !!}
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        {!! Form::label('email', __('business.email') . ':') !!}
                        {!! Form::email('email', $technician->email, ['class' => 'form-control']) !!}
                    </div>
                </div>
            </div>

            <div class="form-group">
                {!! Form::label('location_ids', __('lang_v1.assigned_locations') . ':*') !!}
                {!! Form::select('location_ids[]', $locations, $technician->locations->pluck('id')->toArray(), [
                    'class' => 'form-control select2',
                    'multiple',
                    'required',
                    'style' => 'width: 100%;',
                ]) !!}
            </div>

            {{-- Comisión por reparación por técnico: REEMPLAZADA por comisión por producto
                 (Técnicos → botón "Comisiones por reparación"). Se deja comentada por si se necesita después.
            <div class="form-group">
                {!! Form::label('commission_per_repair', __('lang_v1.commission_per_repair') . ':') !!}
                <div class="input-group">
                    <span class="input-group-addon">$</span>
                    {!! Form::number('commission_per_repair', $technician->commission_per_repair, ['class' => 'form-control', 'min' => '0', 'step' => '0.01']) !!}
                </div>
                <small class="text-muted">@lang('lang_v1.commission_per_repair_help')</small>
            </div>
            --}}

            <div class="form-group">
                {!! Form::label('notes', __('lang_v1.notes') . ':') !!}
                {!! Form::textarea('notes', $technician->notes, ['class' => 'form-control', 'rows' => 2]) !!}
            </div>

            <div class="form-group">
                <label>
                    {!! Form::checkbox('is_active', 1, $technician->is_active) !!} <strong>@lang('lang_v1.is_active')</strong>
                </label>
            </div>
        </div>

        <div class="modal-footer">
            <button type="submit" class="tw-dw-btn tw-dw-btn-primary tw-text-white">@lang('messages.update')</button>
            <button type="button" class="tw-dw-btn tw-dw-btn-neutral tw-text-white" data-dismiss="modal">@lang('messages.close')</button>
        </div>

        {!! Form::close() !!}
    </div>
</div>
