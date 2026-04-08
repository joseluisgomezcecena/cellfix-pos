@extends('layouts.app')
@section('title', 'Install AffiliateDiscount Module')

@section('content')
<section class="content-header">
    <h1>Install AffiliateDiscount Module</h1>
</section>

<section class="content">
    <div class="row">
        <div class="col-md-12">
            @component('components.widget', ['class' => 'box-primary'])
                <div class="box-body">
                    <h3>AffiliateDiscount Module Installation</h3>
                    <p>This module enables management of affiliated business discounts with automatic application in POS.</p>

                    <h4>Features:</h4>
                    <ul>
                        <li>Manage affiliated businesses (business-type contacts only)</li>
                        <li>Create category-specific discount options</li>
                        <li>POS integration with modal interface</li>
                        <li>Automatic discount calculation</li>
                        <li>Transaction logging and reporting</li>
                    </ul>

                    <h4>This installation will:</h4>
                    <ul>
                        <li>Create 4 database tables</li>
                        <li>Add 4 new permissions</li>
                        <li>Set module version</li>
                    </ul>

                    <form method="POST" action="{{ url('modules/affiliatediscount/install') }}">
                        @csrf
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-download"></i> Install Module
                        </button>
                        <a href="{{ action([\App\Http\Controllers\Install\ModulesController::class, 'index']) }}" class="btn btn-default">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </form>
                </div>
            @endcomponent
        </div>
    </div>
</section>
@endsection
