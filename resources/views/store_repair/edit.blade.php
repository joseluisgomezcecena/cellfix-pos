@extends('layouts.app')
@section('title', 'Editar reparación de tienda')

@section('content')

<section class="content-header">
    <h1 class="tw-text-xl md:tw-text-2xl tw-font-bold tw-text-black">Editar reparación
        <small>#{{ $repair->id }} — IMEI {{ $repair->imei }}</small>
    </h1>
</section>

<section class="content">
    {!! Form::model($repair, ['route' => ['store-repairs.update', $repair->id], 'method' => 'PUT']) !!}

    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Sucursal *</label>
                    {!! Form::select('location_id', $locations, $repair->location_id, ['class' => 'form-control select2', 'required', 'style' => 'width:100%', 'disabled' => !$can_manage]) !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Técnico *</label>
                    {!! Form::select('technician_id', $technicians, $repair->technician_id, ['class' => 'form-control select2', 'required', 'style' => 'width:100%', 'disabled' => !$can_manage]) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>IMEI del equipo *</label>
                    {!! Form::text('imei', $repair->imei, ['class' => 'form-control', 'required', 'maxlength' => 191, 'disabled' => !$can_manage]) !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Estado *</label>
                    {!! Form::select('status', \App\StoreRepair::STATUSES, $repair->status, ['class' => 'form-control', 'required']) !!}
                    <small class="text-muted">Al pasar a "Entregada" se registra la fecha de entrega.</small>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nombre del cliente</label>
                    {!! Form::text('customer_name', $repair->customer_name, ['class' => 'form-control', 'maxlength' => 191, 'disabled' => !$can_manage]) !!}
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Teléfono del cliente</label>
                    {!! Form::text('customer_mobile', $repair->customer_mobile, ['class' => 'form-control', 'maxlength' => 50, 'disabled' => !$can_manage]) !!}
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="form-group">
                    <label>Descripción del daño / notas</label>
                    {!! Form::textarea('notes', $repair->notes, ['class' => 'form-control', 'rows' => 4, 'disabled' => !$can_manage]) !!}
                </div>
            </div>
        </div>

        {{-- Comisión: solo editable con permiso celfix.store_repairs.manage_commissions o admin/gerente --}}
        <div class="row" style="background-color: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid #f0ad4e;">
            <div class="col-md-6">
                <div class="form-group" style="margin-bottom:0;">
                    <label>Comisión al técnico (MXN)</label>
                    @if($can_edit_commission)
                        {!! Form::number('commission', $repair->commission, ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) !!}
                    @else
                        <input type="text" class="form-control" value="${{ number_format((float)$repair->commission, 2) }}" disabled>
                        <small class="text-muted">Solo administradores o gerentes pueden modificar la comisión.</small>
                    @endif
                </div>
            </div>
            <div class="col-md-6">
                @if($repair->delivered_at)
                    <p><strong>Fecha de entrega:</strong> {{ $repair->delivered_at->format('d/m/Y H:i') }}</p>
                @endif
                <p><strong>Registrada:</strong> {{ $repair->created_at->format('d/m/Y H:i') }}</p>
            </div>
        </div>
    @endcomponent

    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fa fa-save"></i> Actualizar
            </button>
            <a href="{{ route('store-repairs.index') }}" class="btn btn-default btn-lg">Volver</a>
        </div>
    </div>

    {!! Form::close() !!}
</section>

@endsection
