@extends('layouts.app')
@section('title', __('inventorymultilocation::lang.dashboard'))

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>{{ __('inventorymultilocation::lang.inventory_multi_location') }}
        <small>{{ __('inventorymultilocation::lang.dashboard') }}</small>
    </h1>
    <ol class="breadcrumb">
        <li><a href="{{ url('/home') }}"><i class="fa fa-dashboard"></i> {{ __('inventorymultilocation::lang.dashboard') }}</a></li>
        <li class="active">{{ __('inventorymultilocation::lang.dashboard') }}</li>
    </ol>
</section>

<!-- Main content -->
<section class="content">
    @component('components.widget', ['class' => 'box-primary'])
        <div class="row">
            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-blue"><i class="fa fa-building"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('inventorymultilocation::lang.total_locations') }}</span>
                        <span class="info-box-number">{{ $locations->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-red"><i class="fa fa-exclamation-triangle"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('inventorymultilocation::lang.low_stock_items') }}</span>
                        <span class="info-box-number">{{ array_sum(array_column($location_stats, 'low_stock_count')) }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-yellow"><i class="fa fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('inventorymultilocation::lang.pending_transfers') }}</span>
                        <span class="info-box-number">{{ $recent_transfers->where('status', 'pending')->count() }}</span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                    <span class="info-box-icon bg-green"><i class="fa fa-dollar-sign"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">{{ __('inventorymultilocation::lang.total_stock_value') }}</span>
                        <span class="info-box-number">${{ number_format(array_sum(array_column($location_stats, 'total_value')), 0) }}</span>
                    </div>
                </div>
            </div>
        </div>
    @endcomponent

    <!-- Location Cards -->
    @component('components.widget', ['class' => 'box-primary', 'title' => __('inventorymultilocation::lang.location_overview')])
        <div class="row" id="location-cards">
            @foreach($locations as $location)
                @php
                    $lowStockCount = $location_stats[$location->id]['low_stock_count'] ?? 0;
                    $outOfStockCount = $location_stats[$location->id]['out_of_stock_count'] ?? 0;
                    $totalProducts = $location_stats[$location->id]['total_products'] ?? 0;

                    // Determine card status for color coding
                    $cardStatus = 'healthy';
                    if ($outOfStockCount > 0) {
                        $cardStatus = 'critical';
                    } elseif ($lowStockCount > 0) {
                        $cardStatus = 'warning';
                    }

                    // Stock health percentage
                    $stockHealthPercentage = $totalProducts > 0 ? (($totalProducts - $outOfStockCount - $lowStockCount) / $totalProducts) * 100 : 100;
                @endphp

                <div class="col-md-4 col-sm-6 col-xs-12">
                    <div class="modern-location-card location-card-{{ $cardStatus }}" data-location-id="{{ $location->id }}">
                        <div class="card-header">
                            <div class="location-icon">
                                <i class="fa fa-building"></i>
                            </div>
                            <div class="location-info">
                                <h3 class="location-name">{{ $location->name }}</h3>
                                <p class="location-id">{{ $location->location_id ?? 'N/A' }}</p>
                            </div>
                            <div class="card-status-indicator status-{{ $cardStatus }}"></div>
                        </div>

                        <div class="card-body">
                            <!-- Stock Health Indicator -->
                            <div class="stock-health-section">
                                <div class="health-label">
                                    <span>Stock Health</span>
                                    <span class="health-percentage">{{ number_format($stockHealthPercentage, 1) }}%</span>
                                </div>
                                <div class="health-progress">
                                    <div class="progress-bar progress-bar-{{ $cardStatus }}" style="width: {{ $stockHealthPercentage }}%;"></div>
                                </div>
                            </div>

                            <!-- Primary Stats -->
                            <div class="stats-row primary-stats">
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fa fa-cube"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">{{ number_format($totalProducts) }}</div>
                                        <div class="stat-label">Products</div>
                                    </div>
                                </div>
                                <div class="stat-item">
                                    <div class="stat-icon">
                                        <i class="fa fa-dollar-sign"></i>
                                    </div>
                                    <div class="stat-content">
                                        <div class="stat-value">${{ number_format($location_stats[$location->id]['total_value'] ?? 0, 0) }}</div>
                                        <div class="stat-label">Total Value</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Alert Stats -->
                            <div class="stats-row alert-stats">
                                <div class="alert-item {{ $lowStockCount > 0 ? 'alert-active' : '' }}">
                                    <div class="alert-icon warning">
                                        <i class="fa fa-exclamation-triangle"></i>
                                    </div>
                                    <div class="alert-content">
                                        <div class="alert-count">{{ $lowStockCount }}</div>
                                        <div class="alert-label">Low Stock</div>
                                    </div>
                                </div>
                                <div class="alert-item {{ $outOfStockCount > 0 ? 'alert-active' : '' }}">
                                    <div class="alert-icon critical">
                                        <i class="fa fa-times-circle"></i>
                                    </div>
                                    <div class="alert-content">
                                        <div class="alert-count">{{ $outOfStockCount }}</div>
                                        <div class="alert-label">Out of Stock</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-footer">
                            <button class="view-inventory-btn" onclick="window.location.href='{{ route('inventory-multi.inventory') }}?location_id={{ $location->id }}'">
                                <i class="fa fa-eye"></i>
                                <span>View Inventory</span>
                                <i class="fa fa-arrow-right"></i>
                            </button>
                        </div>

                    </div>
                </div>
            @endforeach
        </div>
    @endcomponent

    <!-- Recent Transfers -->
    @if($recent_transfers->count() > 0)
        @component('components.widget', ['class' => 'box-primary', 'title' => __('inventorymultilocation::lang.recent_activities')])
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead>
                        <tr>
                            <th>{{ __('lang_v1.reference_no') }}</th>
                            <th>{{ __('lang_v1.from') }}</th>
                            <th>{{ __('lang_v1.to') }}</th>
                            <th>{{ __('lang_v1.status') }}</th>
                            <th>{{ __('lang_v1.created_by') }}</th>
                            <th>{{ __('lang_v1.date') }}</th>
                            <th>{{ __('messages.action') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($recent_transfers as $transfer)
                            <tr>
                                <td>{{ $transfer->reference_no }}</td>
                                <td>{{ $transfer->from_location }}</td>
                                <td>{{ $transfer->to_location }}</td>
                                <td>
                                    <span class="label
                                        @if($transfer->status == 'pending') label-warning
                                        @elseif($transfer->status == 'completed') label-success
                                        @elseif($transfer->status == 'cancelled') label-danger
                                        @else label-info
                                        @endif
                                    ">
                                        {{ ucfirst($transfer->status) }}
                                    </span>
                                </td>
                                <td>{{ $transfer->first_name }} {{ $transfer->last_name }}</td>
                                <td>{{ \Carbon\Carbon::parse($transfer->created_at)->format('M d, Y H:i') }}</td>
                                <td>
                                    <a href="{{ route('inventory-multi.transfers.show', $transfer->id) }}" class="btn btn-xs btn-primary">
                                        <i class="fa fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endcomponent
    @endif
</section>

@endsection

@section('javascript')
<script>
$(document).ready(function() {
    // Modern location card click handler
    $('.modern-location-card').on('click', function(e) {
        // Prevent click when clicking on buttons or links
        if ($(e.target).closest('button, a').length === 0) {
            var locationId = $(this).data('location-id');
            window.location.href = "{{ route('inventory-multi.inventory') }}?location_id=" + locationId;
        }
    });

    // Enhanced hover effects with performance optimization
    $('.modern-location-card').hover(
        function() {
            $(this).addClass('card-hovered');
        },
        function() {
            $(this).removeClass('card-hovered');
        }
    );

    // Add loading animation to view inventory buttons
    $('.view-inventory-btn').on('click', function(e) {
        e.stopPropagation();
        var $btn = $(this);
        var originalHtml = $btn.html();

        $btn.html('<i class="fa fa-spinner fa-spin"></i> <span>Loading...</span>');
        $btn.prop('disabled', true);

        // Reset button after a short delay (the page will navigate anyway)
        setTimeout(function() {
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        }, 3000);
    });

    // Animate progress bars on page load
    setTimeout(function() {
        $('.progress-bar').each(function() {
            var $bar = $(this);
            var width = $bar.css('width');
            $bar.css('width', '0%');
            setTimeout(function() {
                $bar.css('width', width);
            }, Math.random() * 500);
        });
    }, 500);

    // Add subtle animation to cards on scroll (if visible)
    function animateCardsOnScroll() {
        $('.modern-location-card').each(function() {
            var $card = $(this);
            var cardTop = $card.offset().top;
            var cardBottom = cardTop + $card.outerHeight();
            var windowTop = $(window).scrollTop();
            var windowBottom = windowTop + $(window).height();

            if (cardBottom > windowTop && cardTop < windowBottom) {
                $card.addClass('card-in-view');
            }
        });
    }

    // Run animation check on scroll and load
    $(window).on('scroll', throttle(animateCardsOnScroll, 100));
    animateCardsOnScroll();

    // Throttle function for performance
    function throttle(func, wait) {
        var timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
});
</script>
@endsection

@section('css')
<style>
/* Professional Color Palette */
:root {
    --primary-gradient: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
    --success-gradient: linear-gradient(135deg, #38a169 0%, #2f855a 100%);
    --warning-gradient: linear-gradient(135deg, #d69e2e 0%, #b7791f 100%);
    --danger-gradient: linear-gradient(135deg, #384254 0%, #384254 100%);
    --healthy-gradient: linear-gradient(135deg, #3182ce 0%, #2b6cb0 100%);

    --card-bg: #ffffff;
    --card-shadow: 0 4px 12px rgba(0, 0, 0, 0.10);
    --card-shadow-hover: 0 8px 20px rgba(0, 0, 0, 0.15);
    --border-radius: 16px;
    --transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);

    --text-primary: #1a202c;
    --text-secondary: #4a5568;
    --text-muted: #718096;
}

/* Enhanced Location Cards */
.modern-location-card {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    transition: var(--transition);
    cursor: pointer;
    margin-bottom: 30px;
    overflow: hidden;
    position: relative;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.modern-location-card:hover {
    transform: translateY(-4px);
    box-shadow: var(--card-shadow-hover);
}

.modern-location-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--primary-gradient);
    z-index: 1;
}

/* Status-based card styling */
.location-card-healthy::before {
    background: var(--success-gradient);
}

.location-card-warning::before {
    background: var(--warning-gradient);
}

.location-card-critical::before {
    background: var(--danger-gradient);
}

/* Card Header */
.card-header {
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 16px;
    position: relative;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(247, 250, 252, 0.9) 100%);
}

.location-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    box-shadow: 0 2px 8px rgba(74, 85, 104, 0.15);
}

.location-card-healthy .location-icon {
    background: var(--success-gradient);
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
}

.location-card-warning .location-icon {
    background: var(--warning-gradient);
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.location-card-critical .location-icon {
    background: var(--danger-gradient);
    box-shadow: 0 4px 12px rgba(56, 66, 84, 0.3);
}

.location-info {
    flex: 1;
}

.location-name {
    margin: 0 0 4px 0;
    font-size: 20px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1.2;
}

.location-id {
    margin: 0;
    font-size: 14px;
    color: var(--text-secondary);
    font-weight: 500;
}

.card-status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    animation: pulse 2s infinite;
}

.status-healthy {
    background: #48bb78;
    box-shadow: 0 0 0 4px rgba(72, 187, 120, 0.2);
}

.status-warning {
    background: #ed8936;
    box-shadow: 0 0 0 4px rgba(237, 137, 54, 0.2);
}

.status-critical {
    background: #384254;
    box-shadow: 0 0 0 4px rgba(56, 66, 84, 0.2);
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(72, 187, 120, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(72, 187, 120, 0); }
    100% { box-shadow: 0 0 0 0 rgba(72, 187, 120, 0); }
}

/* Card Body */
.card-body {
    padding: 24px;
    background: rgba(255, 255, 255, 0.5);
}

/* Stock Health Section */
.stock-health-section {
    margin-bottom: 24px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(237, 242, 247, 0.8) 100%);
    border-radius: 12px;
    border: 1px solid rgba(226, 232, 240, 0.6);
}

.health-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-primary);
}

.health-percentage {
    font-size: 16px;
    font-weight: 700;
    color: var(--text-primary);
}

.health-progress {
    height: 8px;
    background: rgba(226, 232, 240, 0.6);
    border-radius: 4px;
    overflow: hidden;
    position: relative;
}

.progress-bar {
    height: 100%;
    border-radius: 4px;
    transition: width 1s ease-in-out;
    position: relative;
}

.progress-bar-healthy {
    background: var(--success-gradient);
    box-shadow: 0 0 8px rgba(17, 153, 142, 0.3);
}

.progress-bar-warning {
    background: var(--warning-gradient);
    box-shadow: 0 0 8px rgba(240, 147, 251, 0.3);
}

.progress-bar-critical {
    background: var(--danger-gradient);
    box-shadow: 0 0 8px rgba(56, 66, 84, 0.3);
}

/* Stats Rows */
.stats-row {
    display: flex;
    gap: 16px;
    margin-bottom: 20px;
}

.stats-row:last-child {
    margin-bottom: 0;
}

/* Primary Stats */
.stat-item {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: linear-gradient(135deg, rgba(255, 255, 255, 0.9) 0%, rgba(247, 250, 252, 0.7) 100%);
    border-radius: 12px;
    border: 1px solid rgba(226, 232, 240, 0.4);
    transition: var(--transition);
}

.stat-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.stat-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: var(--primary-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 16px;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
    min-width: 0;
}

.stat-value {
    font-size: 20px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.2;
    margin-bottom: 2px;
}

.stat-label {
    font-size: 12px;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Alert Stats */
.alert-item {
    flex: 1;
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px;
    background: rgba(247, 250, 252, 0.5);
    border-radius: 10px;
    border: 1px solid rgba(226, 232, 240, 0.3);
    transition: var(--transition);
    opacity: 0.6;
}

.alert-item.alert-active {
    opacity: 1;
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.95) 0%, rgba(254, 242, 242, 0.9) 100%);
    border-color: rgba(245, 101, 101, 0.2);
    transform: scale(1.02);
}

.alert-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
    flex-shrink: 0;
}

.alert-icon.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}

.alert-icon.critical {
    background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
}

.alert-count {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-primary);
    line-height: 1;
}

.alert-label {
    font-size: 11px;
    color: var(--text-secondary);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.3px;
}

/* Card Footer */
.card-footer {
    padding: 20px 24px;
    background: linear-gradient(135deg, rgba(247, 250, 252, 0.6) 0%, rgba(255, 255, 255, 0.8) 100%);
    border-top: 1px solid rgba(226, 232, 240, 0.4);
}

.view-inventory-btn {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 12px 20px;
    background: var(--primary-gradient);
    color: white;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    text-decoration: none;
    box-shadow: 0 2px 8px rgba(74, 85, 104, 0.15);
}

.view-inventory-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(74, 85, 104, 0.25);
    color: white;
    text-decoration: none;
}

.location-card-healthy .view-inventory-btn {
    background: var(--success-gradient);
    box-shadow: 0 4px 12px rgba(17, 153, 142, 0.3);
}

.location-card-healthy .view-inventory-btn:hover {
    box-shadow: 0 8px 20px rgba(17, 153, 142, 0.4);
}

.location-card-warning .view-inventory-btn {
    background: var(--warning-gradient);
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

.location-card-warning .view-inventory-btn:hover {
    box-shadow: 0 8px 20px rgba(240, 147, 251, 0.4);
}

.location-card-critical .view-inventory-btn {
    background: var(--danger-gradient);
    box-shadow: 0 4px 12px rgba(56, 66, 84, 0.3);
}

.location-card-critical .view-inventory-btn:hover {
    box-shadow: 0 8px 20px rgba(56, 66, 84, 0.4);
}

/* Hover Overlay */
/* Overlay styles removed for direct clicking */

/* Enhanced Info Boxes */
.info-box {
    background: var(--card-bg);
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    transition: var(--transition);
    border: none;
    overflow: hidden;
}

.info-box:hover {
    transform: translateY(-4px);
    box-shadow: var(--card-shadow-hover);
}

.info-box-text {
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: var(--text-secondary);
}

.info-box-number {
    font-size: 32px;
    font-weight: 800;
    color: var(--text-primary);
    line-height: 1.2;
}

/* Enhanced Animations */
.card-hovered {
    z-index: 10;
}

.card-in-view {
    animation: slideInUp 0.6s ease-out forwards;
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Loading state for buttons */
.view-inventory-btn:disabled {
    opacity: 0.7;
    cursor: not-allowed;
    transform: none !important;
}

/* Table enhancements for recent transfers */
.table-responsive {
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    background: var(--card-bg);
    overflow: hidden;
    border: 1px solid rgba(226, 232, 240, 0.4);
}

.table {
    margin: 0;
    background: transparent;
}

.table thead th {
    border-bottom: 2px solid rgba(226, 232, 240, 0.6);
    background: linear-gradient(135deg, rgba(248, 250, 252, 0.9) 0%, rgba(237, 242, 247, 0.8) 100%);
    color: var(--text-primary);
    font-weight: 600;
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: 16px 12px;
}

.table tbody tr {
    transition: var(--transition);
}

.table tbody tr:hover {
    background: rgba(74, 85, 104, 0.03);
    transform: scale(1.01);
}

.table td {
    padding: 12px;
    vertical-align: middle;
    border-top: 1px solid rgba(226, 232, 240, 0.4);
    color: var(--text-primary);
}

/* Enhanced labels */
.label {
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border: none;
}

.label-warning {
    background: var(--warning-gradient);
    color: white;
}

.label-success {
    background: var(--success-gradient);
    color: white;
}

.label-danger {
    background: var(--danger-gradient);
    color: white;
}

.label-info {
    background: var(--primary-gradient);
    color: white;
}

/* Responsive Design */
@media (max-width: 768px) {
    .modern-location-card {
        margin-bottom: 20px;
    }

    .card-header {
        padding: 20px;
        flex-direction: column;
        text-align: center;
        gap: 12px;
    }

    .location-icon {
        width: 48px;
        height: 48px;
        font-size: 20px;
    }

    .location-name {
        font-size: 18px;
    }

    .stats-row {
        flex-direction: column;
        gap: 12px;
    }

    .card-body {
        padding: 20px;
    }


    .table-responsive {
        font-size: 12px;
    }

    .table td, .table th {
        padding: 8px 6px;
    }
}

@media (max-width: 576px) {
    .location-name {
        font-size: 16px;
    }

    .stat-value {
        font-size: 18px;
    }

    .stat-item, .alert-item {
        padding: 12px;
    }

    .view-inventory-btn {
        padding: 10px 16px;
        font-size: 13px;
    }
}
</style>
@endsection