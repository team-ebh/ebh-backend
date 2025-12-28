@php
    use App\Models\Payment;
    use App\Models\Trip;
    use App\Models\TripLocation;

    $locale = app()->getLocale();
    $isArabic = $locale === 'ar';
    $textAlign = $isArabic ? 'right' : 'left';
    $direction = $isArabic ? 'rtl' : 'ltr';

    $trip = $payment->trip;
    $customer = $trip->customer;
    $rider = $trip->rider;
    $locations = $trip->locations->sortBy(TripLocation::COLUMN_SEQUENCE);
@endphp

    <!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $locale) }}" dir="{{ $direction }}">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>{{ trans('receipts.invoice') }} #{{ tripNumberFormat($trip) }}</title>

    <style>
        body {
            padding: 20px;
            border: 1px solid #ddd;
            max-width: 800px;
            margin: auto;
            font-size: 12px;
            font-family: 'dejavu sans', sans-serif;
            direction: {{ $direction }};
        }

        h2, h3 {
            text-align: center;
            margin: 0;
        }

        .no-border {
            border: none !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            vertical-align: top;
            text-align: {{ $textAlign }};
        }

        th {
            background-color: #f4f4f4;
        }

        .logo {
            text-align: {{ $textAlign }};
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }

        .header-table {
            width: 100%;
            border-bottom: 1px solid #ddd;
            margin-bottom: 10px;
        }

        .header-table p {
            text-align: {{ $textAlign }};
            margin: 5px 0;
        }

        .header-table td {
            vertical-align: middle;
            padding: 5px;
        }

        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            padding: 10px 0;
        }

        .section-title {
            background-color: #f4f4f4;
            padding: 10px;
            margin-top: 20px;
            font-weight: bold;
            text-align: {{ $textAlign }};
        }
    </style>
</head>
<body>

<!-- Header -->
<table class="header-table no-border">
    <tr>
        <td class="logo no-border" style="width:50%;">
            {{ config('app.name') }}
        </td>
        <td class="no-border" style="width:50%; text-align: {{ $textAlign }};">
            <h2></h2>
            <p>
                <strong>{{ trans('receipts.invoice') }} #</strong>
                {{ tripNumberFormat($trip) }}
            </p>
            <p>
                <strong>{{ trans('receipts.payment_number') }} #</strong>
                {{ $payment->{Payment::COLUMN_PAYMENT_NUMBER} }}
            </p>
        </td>
    </tr>
</table>

<!-- Customer & Payment Details -->
@php
    $detailsRows = [
        [
            'label' => trans('receipts.customer_name'),
            'value' => $customer->first_name . ' ' . $customer->last_name
        ],
        [
            'label' => trans('receipts.email'),
            'value' => $customer->email ?? '-'
        ],
        [
            'label' => trans('receipts.phone_number'),
            'value' => defaultPrefixPhoneNumber() . ' ' . $customer->phone_number
        ],
        [
            'label' => trans('receipts.date_time'),
            'value' => $trip->updated_at->format('M d, Y H:i')
        ],
        [
            'label' => trans('receipts.payment_method'),
            'value' => $trip->{Trip::COLUMN_PAYMENT_METHOD}?->getLabel() ?? '-'
        ],
        [
            'label' => trans('receipts.payment_status'),
            'value' => $payment->{Payment::COLUMN_STATUS}?->getFrontendLabel() ?? '-'
        ],
    ];
@endphp

<table class="header-table">
    <tr>
        <td class="no-border" style="width:50%;">
            @for($i = 0; $i < 3; $i++)
                @php $row = $detailsRows[$i] @endphp
                <p>
                    <strong>{{ $row['label'] }}:</strong>
                    <span>{{ $row['value'] }}</span>
                </p>
            @endfor
        </td>
        <td class="no-border" style="width:50%;">
            @for($i = 3; $i < 6; $i++)
                @php $row = $detailsRows[$i] @endphp
                <p>
                    <strong>{{ $row['label'] }}:</strong>
                    <span>{{ $row['value'] }}</span>
                </p>
            @endfor
        </td>
    </tr>
</table>

<!-- Trip Details Section -->
<div class="section-title">{{ trans('receipts.trip_details') }}</div>

<table class="header-table">
    <tr>
        <td class="no-border" style="width:50%;">
            @foreach($locations as $index => $location)
                <p>
                    <strong>{{ $location->{TripLocation::COLUMN_TYPE}?->getLabel() ?? trans('receipts.location') . ' ' . ($index + 1) }}
                        :</strong>
                    {{ $location->{TripLocation::COLUMN_LOCATION_TITLE} ?? '-' }}
                    @if($location->{TripLocation::COLUMN_LOCATION_SUB_TITLE})
                        <br>
                        <span
                            style="margin-{{ $textAlign === 'right' ? 'right' : 'left' }}: 20px; font-size: 10px; color: #666;">
                            {{ $location->{TripLocation::COLUMN_LOCATION_SUB_TITLE} }}
                        </span>
                    @endif
                </p>
            @endforeach
        </td>
        <td class="no-border" style="width:50%;">
            @if($rider)
                <p>
                    <strong>{{ trans('receipts.rider_name') }}:</strong>
                    {{ $rider->full_name }}
                </p>
                <p>
                    <strong>{{ trans('receipts.rider_phone') }}:</strong>
                    {{ defaultPrefixPhoneNumber() }} {{ $rider->phone_number }}
                </p>
            @endif
        </td>
    </tr>
</table>

<!-- Payment Summary -->
<div class="section-title">{{ trans('receipts.payment_summary') }}</div>

<table>
    <thead>
    <tr>
        <th>{{ trans('receipts.description') }}</th>
        <th>{{ trans('receipts.amount') }}</th>
    </tr>
    </thead>
    <tbody>
    <tr>
        <td>{{ trans('receipts.trip_fare') }}</td>
        @php
            $fareBase = $trip->{Trip::COLUMN_TOTAL_PRICE} - ($trip->{Trip::COLUMN_ACCESSIBILITY_PRICE} ?? 0) - ($trip->{Trip::COLUMN_WAITING_PRICE} ?? 0);
        @endphp
        <td>{{ priceFormat($fareBase) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
    </tr>
    @if($trip->{Trip::COLUMN_ACCESSIBILITY_PRICE} && $trip->{Trip::COLUMN_ACCESSIBILITY_PRICE} > 0)
        <tr>
            <td>{{ trans('receipts.accessibility_fee') }}</td>
            <td>{{ priceFormat($trip->{Trip::COLUMN_ACCESSIBILITY_PRICE}) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
        </tr>
    @endif
    @if($trip->{Trip::COLUMN_WAITING_PRICE} && $trip->{Trip::COLUMN_WAITING_PRICE} > 0)
        <tr>
            <td>{{ trans('receipts.waiting_fee') }}</td>
            <td>{{ priceFormat($trip->{Trip::COLUMN_WAITING_PRICE}) }} {{ $trip->{Trip::COLUMN_CURRENCY}?->getLabel() }}</td>
        </tr>
    @endif
    <tr style="background-color: #f4f4f4; font-weight: bold;">
        <td>{{ trans('receipts.total_paid') }}</td>
        <td>{{ priceFormat($payment->{Payment::COLUMN_AMOUNT}) }} {{ $payment->{Payment::COLUMN_CURRENCY}?->getLabel() }}</td>
    </tr>
    </tbody>
</table>

<!-- Footer -->
<div class="footer">
    <p>{{ trans('receipts.copyright', ['year' => now()->year, 'app_name' => config('app.name')]) }}</p>
    <p>{{ trans('receipts.thank_you') }}</p>
</div>

</body>
</html>
