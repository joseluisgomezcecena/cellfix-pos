@extends('layouts.app')
@section('title', 'Manage Discount Options - ' . $affiliate->business_name)

@section('content')

<!-- Content Header -->
<section class="content-header">
    <h1>Manage Discount Options
        <small>{{ $affiliate->business_name }}</small>
    </h1>
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="nav-tabs-custom">
                    <ul class="nav nav-tabs">
                        @foreach($categories as $key => $label)
                            <li class="{{ $loop->first ? 'active' : '' }}">
                                <a href="#tab_{{ $key }}" data-toggle="tab">{{ $label }}</a>
                            </li>
                        @endforeach
                    </ul>

                    <div class="tab-content">
                        @foreach($categories as $key => $label)
                            <div class="tab-pane {{ $loop->first ? 'active' : '' }}" id="tab_{{ $key }}">
                                <h4>{{ $label }} Discounts</h4>

                                <button type="button" class="btn btn-success btn-sm add-option-btn mb-3" data-category="{{ $key }}">
                                    <i class="fa fa-plus"></i> Add New Option
                                </button>

                                <table class="table table-bordered" id="options_table_{{ $key }}">
                                    <thead>
                                        <tr>
                                            <th>Label</th>
                                            <th>Type</th>
                                            <th>Value</th>
                                            <th>Active</th>
                                            <th>Order</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if(isset($optionsByCategory[$key]))
                                            @foreach($optionsByCategory[$key] as $option)
                                                <tr data-option-id="{{ $option->id }}">
                                                    <td>{{ $option->discount_label }}</td>
                                                    <td>{{ ucfirst($option->discount_type) }}</td>
                                                    <td>{{ $option->discount_value }}</td>
                                                    <td>
                                                        <span class="label label-{{ $option->is_active ? 'success' : 'default' }}">
                                                            {{ $option->is_active ? 'Active' : 'Inactive' }}
                                                        </span>
                                                    </td>
                                                    <td>{{ $option->display_order }}</td>
                                                    <td>
                                                        <button class="btn btn-xs btn-primary edit-option-btn" data-id="{{ $option->id }}">
                                                            <i class="fa fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-xs btn-danger delete-option-btn" data-id="{{ $option->id }}">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        @else
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No discount options yet</td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="row">
                    <div class="col-sm-12">
                        <a href="{{ route('affiliate-discounts.index') }}" class="btn btn-default">
                            <i class="fa fa-arrow-left"></i> Back to List
                        </a>
                    </div>
                </div>
            @endcomponent
        </div>
    </div>
</section>

<!-- Add/Edit Option Modal -->
<div class="modal fade" id="optionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add Discount Option</h4>
            </div>
            <div class="modal-body">
                <form id="optionForm">
                    <input type="hidden" name="option_id" id="option_id">
                    <input type="hidden" name="category_name" id="category_name">

                    <div class="form-group">
                        <label>Discount Label:*</label>
                        <input type="text" name="discount_label" id="discount_label" class="form-control" placeholder="e.g., $100 PESOS or 10% DESC." required>
                    </div>

                    <div class="form-group">
                        <label>Discount Type:*</label>
                        <select name="discount_type" id="discount_type" class="form-control" required>
                            <option value="fixed">Fixed Amount</option>
                            <option value="percentage">Percentage</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Discount Value:*</label>
                        <input type="number" step="0.01" name="discount_value" id="discount_value" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label>Display Order:</label>
                        <input type="number" name="display_order" id="display_order" class="form-control" value="0">
                    </div>

                    <div class="form-group">
                        <label>
                            <input type="checkbox" name="is_active" id="is_active" value="1" checked> Active
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" id="saveOptionBtn">Save</button>
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('javascript')
<script>
$(document).ready(function(){
    var affiliateId = {{ $affiliate->id }};
    var currentCategory = '';

    // Add option button
    $('.add-option-btn').click(function(){
        currentCategory = $(this).data('category');
        $('#optionForm')[0].reset();
        $('#option_id').val('');
        $('#category_name').val(currentCategory);
        $('.modal-title').text('Add Discount Option');
        $('#optionModal').modal('show');
    });

    // Save option
    $('#saveOptionBtn').click(function(){
        var optionId = $('#option_id').val();
        var url = optionId ?
            '/affiliate-discounts/options/' + optionId :
            '/affiliate-discounts/' + affiliateId + '/options';
        var method = optionId ? 'PUT' : 'POST';

        var formData = {
            category_name: $('#category_name').val(),
            discount_label: $('#discount_label').val(),
            discount_type: $('#discount_type').val(),
            discount_value: $('#discount_value').val(),
            display_order: $('#display_order').val(),
            is_active: $('#is_active').is(':checked') ? 1 : 0,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        $.ajax({
            url: url,
            method: method,
            data: formData,
            success: function(response){
                if(response.success){
                    toastr.success(response.msg);
                    $('#optionModal').modal('hide');
                    location.reload();
                }
            },
            error: function(){
                toastr.error('Failed to save option');
            }
        });
    });

    // Delete option
    $('.delete-option-btn').click(function(){
        var optionId = $(this).data('id');
        swal({
            title: "Are you sure?",
            icon: "warning",
            buttons: true,
            dangerMode: true,
        }).then((willDelete) => {
            if (willDelete) {
                $.ajax({
                    url: '/affiliate-discounts/options/' + optionId,
                    method: 'DELETE',
                    data: { _token: $('meta[name="csrf-token"]').attr('content') },
                    success: function(response){
                        if(response.success){
                            toastr.success(response.msg);
                            location.reload();
                        }
                    }
                });
            }
        });
    });
});
</script>
@endsection
