@php
    use App\Enums\Payment\PaymentMethodEnum;
    use App\Enums\Trip\RideTypeEnum;
    use App\Models\Order;
    use App\Models\Payment;
    use App\Models\Trip;
    use App\Models\TripLocation;

    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $textAlign = $isArabic ? 'right' : 'left';
    $textAlignOpposite = $isArabic ? 'left' : 'right';
    $direction = $isArabic ? 'rtl' : 'ltr';

    $order = $trip->order;
    $customer = $trip->customer;
    $rider = $trip->rider;
    $locations = $trip->locations->sortBy(TripLocation::COLUMN_SEQUENCE);
    $payment = $order?->paidPayment;
    $hasPayment = $payment !== null;
    $isCashPayment = $order?->{Order::COLUMN_PAYMENT_METHOD} === PaymentMethodEnum::CASH;

    // Determine view type (for rider or customer)
    $isRiderView = $viewType ?? 'customer' === 'rider';

    // Calculate fare breakdown
    $baseFare = $trip->{Trip::COLUMN_BASE_FARE} ?? 0;
    $roundTripPrice = $trip->{Trip::COLUMN_ROUND_TRIP_PRICE} ?? 0;
    $accessibilityPrice = $trip->{Trip::COLUMN_ACCESSIBILITY_PRICE} ?? 0;
    $waitingPrice = $trip->{Trip::COLUMN_WAITING_PRICE} ?? 0;
    $totalPrice = $trip->{Trip::COLUMN_TOTAL_PRICE} ?? 0;

    // Get ride type and vehicle type
    $rideType = $trip->{Trip::COLUMN_RIDE_TYPE};
    $vehicleType = $trip->{Trip::COLUMN_VEHICLE_TYPE_ID};
    $isRoundTrip = $rideType === RideTypeEnum::ROUND_TRIP || $rideType === RideTypeEnum::ROUND_TRIP_WAIT;

    // Get accessibility requirements
    $accessibility = $trip->accessibility ?? collect();

    // Brand color
    $brandColor = '#FAB902';
    $brandColorLight = '#FEF7E0';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ trans('receipts.trip_receipt') }} - {{ tripNumberFormat($trip) }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'dejavu sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            background: #fff;
            direction: {{ $direction }};
            padding: 15px 20px;
        }

        .container {
            max-width: 100%;
            margin: 0 auto;
            background: #fff;
        }

        /* Header Styles */
        .header {
            border-bottom: 2px solid {{ $brandColor }};
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .logo {
            font-size: 22px;
            font-weight: bold;
            color: {{ $brandColor }};
            margin-bottom: 3px;
        }

        .receipt-title {
            font-size: 14px;
            color: #555;
            margin-bottom: 8px;
        }

        .receipt-meta {
            background: {{ $brandColorLight }};
            padding: 8px 10px;
            border-radius: 4px;
        }

        .receipt-meta table {
            width: 100%;
            border-collapse: collapse;
        }

        .receipt-meta td {
            padding: 2px 0;
            vertical-align: middle;
        }

        .receipt-meta .label {
            color: #666;
            width: 35%;
        }

        .receipt-meta .value {
            font-weight: 600;
            color: #333;
        }

        /* Section Styles */
        .section {
            margin-bottom: 10px;
        }

        .section-header {
            background: {{ $brandColor }};
            color: #000;
            padding: 5px 10px;
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-bottom: 0;
        }

        .section-content {
            border: 1px solid #e0e0e0;
            border-top: none;
            padding: 8px 10px;
        }

        /* Two Column Layout */
        .two-column {
            width: 100%;
        }

        .two-column td {
            width: 50%;
            vertical-align: top;
            padding: 0 8px;
        }

        .two-column td:first-child {
            padding-{{ $textAlign }}: 0;
            border-{{ $textAlignOpposite }}: 1px solid #e0e0e0;
        }

        .two-column td:last-child {
            padding-{{ $textAlignOpposite }}: 0;
        }

        /* Info Block */
        .info-block {
            margin-bottom: 6px;
        }

        .info-block:last-child {
            margin-bottom: 0;
        }

        .info-label {
            color: #666;
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 0.2px;
            margin-bottom: 1px;
        }

        .info-value {
            color: #333;
            font-weight: 500;
            font-size: 10px;
        }

        /* Inline Info for Trip Details */
        .inline-info {
            display: inline-block;
            margin-{{ $textAlignOpposite }}: 15px;
            margin-bottom: 4px;
        }

        .inline-info:last-child {
            margin-{{ $textAlignOpposite }}: 0;
        }

        /* Location Styles */
        .location-item {
            margin-bottom: 6px;
            padding-{{ $textAlign }}: 10px;
            border-{{ $textAlign }}: 2px solid {{ $brandColor }};
        }

        .location-item:last-child {
            margin-bottom: 0;
        }

        .location-item.destination {
            border-{{ $textAlign }}-color: #34a853;
        }

        .location-type {
            font-size: 8px;
            text-transform: uppercase;
            color: {{ $brandColor }};
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .location-item.destination .location-type {
            color: #34a853;
        }

        .location-title {
            font-weight: 600;
            color: #333;
            font-size: 10px;
        }

        .location-subtitle {
            font-size: 9px;
            color: #666;
        }

        /* Price Table */
        .price-table {
            width: 100%;
            border-collapse: collapse;
        }

        .price-table tr {
            border-bottom: 1px solid #f0f0f0;
        }

        .price-table tr:last-child {
            border-bottom: none;
        }

        .price-table td {
            padding: 6px 10px;
        }

        .price-table .item-name {
            color: #333;
        }

        .price-table .item-price {
            text-align: {{ $textAlignOpposite }};
            font-weight: 500;
        }

        .price-table .total-row {
            background: {{ $brandColorLight }};
            border-top: 2px solid {{ $brandColor }};
        }

        .price-table .total-row td {
            padding: 8px 10px;
            font-weight: 700;
            font-size: 11px;
        }

        .price-table .total-row .item-name {
            color: #333;
        }

        .price-table .total-row .item-price {
            color: #333;
        }

        /* Payment Badge */
        .payment-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .payment-badge.paid {
            background: #e6f4ea;
            color: #1e8e3e;
        }

        .payment-badge.cash {
            background: {{ $brandColorLight }};
            color: #b38600;
        }

        .payment-badge.pending {
            background: #fce8e6;
            color: #d93025;
        }

        /* Accessibility Tag */
        .accessibility-tag {
            display: inline-block;
            background: #f0f0f0;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            margin-{{ $textAlignOpposite }}: 4px;
            margin-bottom: 2px;
        }

        /* Footer */
        .footer {
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #e0e0e0;
            text-align: center;
        }

        .footer-text {
            color: #666;
            font-size: 9px;
            margin-bottom: 3px;
        }

        .footer-brand {
            color: {{ $brandColor }};
            font-weight: 600;
            font-size: 10px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="logo">{{ config('app.name') }}</div>
        <div class="receipt-title">{{ trans('receipts.trip_receipt') }}</div>
        <div class="receipt-meta">
            <table>
                <tr>
                    <td class="label">{{ trans('receipts.receipt_number') }}:</td>
                    <td class="value">{{ tripNumberFormat($trip) }}</td>
                    <td class="label" style="width: 25%;">{{ trans('receipts.date_time') }}:</td>
                    <td class="value">{{ $trip->{Trip::COLUMN_CREATED_AT}->format('M d, Y - H:i') }}</td>
                </tr>
                <tr>
                    @if($hasPayment)
                        <td class="label">{{ trans('receipts.payment_reference') }}:</td>
                        <td class="value">{{ $payment->{Payment::COLUMN_PAYMENT_NUMBER} }}</td>
                    @else
                        <td class="label"></td>
                        <td class="value"></td>
                    @endif
                    <td class="label">{{ trans('receipts.status') }}:</td>
                    <td class="value">
                        @if($hasPayment)
                            <span class="payment-badge paid">{{ trans('receipts.paid') }}</span>
                        @elseif($isCashPayment)
                            <span class="payment-badge cash">{{ trans('receipts.cash') }}</span>
                        @else
                            <span class="payment-badge pending">{{ trans('receipts.pending') }}</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Customer & Rider Information -->
    <div class="section">
        <div class="section-header">{{ trans('receipts.trip_participants') }}</div>
        <div class="section-content">
            <table class="two-column">
                <tr>
                    <td>
                        <div class="info-block">
                            <div class="info-label">{{ trans('receipts.customer') }}</div>
                            <div class="info-value">{{ $customer->first_name }} {{ $customer->last_name }}</div>
                        </div>
                        <div class="info-block">
                            <div class="info-label">{{ trans('receipts.phone') }}</div>
                            <div class="info-value">{{ defaultPrefixPhoneNumber() }} {{ $customer->phone_number }}</div>
                        </div>
                    </td>
                    <td>
                        @if($rider)
                            <div class="info-block">
                                <div class="info-label">{{ trans('receipts.driver') }}</div>
                                <div class="info-value">{{ $rider->full_name }}</div>
                            </div>
                            <div class="info-block">
                                <div class="info-label">{{ trans('receipts.phone') }}</div>
                                <div class="info-value">{{ defaultPrefixPhoneNumber() }} {{ $rider->phone_number }}</div>
                            </div>
                        @else
                            <div class="info-block">
                                <div class="info-label">{{ trans('receipts.driver') }}</div>
                                <div class="info-value" style="color: #999;">{{ trans('receipts.not_assigned') }}</div>
                            </div>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Trip Route -->
    <div class="section">
        <div class="section-header">{{ trans('receipts.trip_route') }}</div>
        <div class="section-content">
            @foreach($locations as $index => $location)
                @php
                    $isOrigin = $location->{TripLocation::COLUMN_TYPE} === \App\Enums\Trip\TripLocationTypeEnum::ORIGIN;
                @endphp
                <div class="location-item {{ $isOrigin ? 'origin' : 'destination' }}">
                    <div class="location-type">
                        {{ $location->{TripLocation::COLUMN_TYPE}?->getLabel() ?? trans('receipts.stop') . ' ' . ($index + 1) }}
                    </div>
                    <div class="location-title">{{ $location->{TripLocation::COLUMN_LOCATION_TITLE} ?? '-' }}</div>
                    @if($location->{TripLocation::COLUMN_LOCATION_SUB_TITLE})
                        <div class="location-subtitle">{{ $location->{TripLocation::COLUMN_LOCATION_SUB_TITLE} }}</div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

    <!-- Trip Details - Inline Layout -->
    <div class="section">
        <div class="section-header">{{ trans('receipts.trip_details') }}</div>
        <div class="section-content">
            <span class="inline-info">
                <span class="info-label">{{ trans('receipts.ride_type') }}:</span>
                <span class="info-value">{{ $rideType?->getLabel() ?? '-' }}</span>
            </span>
            <span class="inline-info">
                <span class="info-label">{{ trans('receipts.vehicle_type') }}:</span>
                <span class="info-value">{{ $vehicleType?->getLabel() ?? '-' }}</span>
            </span>
            <span class="inline-info">
                <span class="info-label">{{ trans('receipts.passengers') }}:</span>
                <span class="info-value">{{ $trip->{Trip::COLUMN_PASSENGER_COUNT} ?? 1 }}</span>
            </span>
            <span class="inline-info">
                <span class="info-label">{{ trans('receipts.payment_method') }}:</span>
                <span class="info-value">{{ $order?->{Order::COLUMN_PAYMENT_METHOD}?->getLabel() ?? '-' }}</span>
            </span>
            @if($trip->{Trip::COLUMN_WAITING_TIME} && $trip->{Trip::COLUMN_WAITING_TIME} > 0)
                <span class="inline-info">
                    <span class="info-label">{{ trans('receipts.waiting_time') }}:</span>
                    <span class="info-value">{{ $trip->{Trip::COLUMN_WAITING_TIME} }} {{ trans('trips.admin.timeline.minutes') }}</span>
                </span>
            @endif
            @if($accessibility->isNotEmpty())
                <div style="margin-top: 4px;">
                    <span class="info-label">{{ trans('receipts.accessibility') }}:</span>
                    @foreach($accessibility as $req)
                        <span class="accessibility-tag">{{ $req->{\App\Models\TripAccessibility::COLUMN_ACCESSIBILITY_REQUIREMENT}->getLabel() }}</span>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Price Breakdown -->
    <div class="section">
        <div class="section-header">{{ trans('receipts.fare_breakdown') }}</div>
        <div class="section-content" style="padding: 0;">
            <table class="price-table">
                @if($baseFare > 0)
                    <tr>
                        <td class="item-name">{{ trans('receipts.base_fare') }}</td>
                        <td class="item-price">{{ priceFormat($baseFare) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
                    </tr>
                @endif
                @if($isRoundTrip && $roundTripPrice > 0)
                    <tr>
                        <td class="item-name">{{ trans('receipts.return_fare') }}</td>
                        <td class="item-price">{{ priceFormat($roundTripPrice) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
                    </tr>
                @endif
                @if($accessibilityPrice > 0)
                    <tr>
                        <td class="item-name">{{ trans('receipts.accessibility_fee') }}</td>
                        <td class="item-price">{{ priceFormat($accessibilityPrice) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
                    </tr>
                @endif
                @if($waitingPrice > 0)
                    <tr>
                        <td class="item-name">{{ trans('receipts.waiting_fee') }}</td>
                        <td class="item-price">{{ priceFormat($waitingPrice) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td class="item-name">{{ trans('receipts.total_amount') }}</td>
                    <td class="item-price">{{ priceFormat($totalPrice) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <div class="footer-text">{{ trans('receipts.thank_you_message') }}</div>
        <div class="footer-text">{{ trans('receipts.copyright', ['year' => now()->year, 'app_name' => config('app.name')]) }}</div>
        <div class="footer-brand">{{ config('app.name') }}</div>
    </div>
</div>
</body>
</html>
