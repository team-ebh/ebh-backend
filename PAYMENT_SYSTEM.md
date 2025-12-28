# Payment System Documentation

## Overview

This document describes the payment system implementation for the EBH application. The system handles **trip payments** using online payment gateways (KNET via UPayments) with support for cash payments. The architecture is designed with flexibility to easily switch between payment gateways without changing business logic.

## Architecture

### Design Patterns

The payment system uses multiple design patterns:

1. **Strategy Pattern** - For payment gateway abstraction
2. **Factory Pattern** - For gateway instantiation
3. **Pipeline Pattern** - For payment link generation flow
4. **Repository Pattern** - For database operations

```
Customer → PaymentController → GetPaymentLinkAction
                                      ↓
                                Pipeline Pattern
                    (LoadTrip → Validate → CheckExisting →
                     CreatePayment → GenerateLink → UpdatePayment)
                                      ↓
                              PaymentService (Facade)
                                      ↓
                        PaymentGatewayFactory (Factory)
                                      ↓
                        PaymentGatewayInterface (Contract)
                                      ↓
                        UPaymentsGateway (Concrete Implementation)
```

### Key Components

1. **Payment Gateway Interface** (`PaymentGatewayInterface`)
   - Contract for all payment gateway implementations
   - Methods: `createPaymentLink()`, `createCustomerToken()`, `verifyPayment()`

2. **Payment Gateway Factory** (`PaymentGatewayFactory`)
   - Creates gateway instances based on configuration
   - Supports dynamic gateway switching
   - Maintains gateway registry

3. **Concrete Gateways** (e.g., `UPaymentsGateway`)
   - Specific implementation for each payment provider
   - HTTP request handling with logging
   - Request/response sanitization
   - API integration

4. **Payment Service** (`PaymentService`)
   - Main facade for payment operations
   - Gateway resolution and switching
   - Unified API for all gateways

5. **Pipeline Pattern** (Payment Link Generation)
   - `LoadLastTripPipe` - Loads customer's last completed trip
   - `ValidatePaymentEligibilityPipe` - Validates trip can be paid
   - `CheckExistingPendingPaymentPipe` - Checks for reusable payment
   - `CreatePaymentRecordPipe` - Creates new payment record
   - `GeneratePaymentLinkPipe` - Generates link from gateway
   - `UpdatePaymentLinkPipe` - Updates payment with gateway response

## Database Schema

### Tables

#### `payments`
Stores all payment records for trips.

```sql
- id: bigint (primary key)
- payment_number: string (unique) - Unique identifier for the payment
- customer_id: bigint (foreign key to customers)
- trip_id: bigint (foreign key to trips)
- status: tinyint (1=pending, 2=paid, 3=failed, 4=expired)
- gateway_reference_id: string (nullable) - Gateway's trackId/transaction reference
- gateway: string (upayments, myfatoorah)
- link: text (nullable) - Payment link URL from gateway
- expires_at: timestamp (nullable) - Payment link expiration time
- attempt: integer (default: 1) - Number of payment attempts
- amount: decimal(12,3) - Payment amount
- currency: string(4) - Currency code (KWD, USD, etc.)
- created_at, updated_at: timestamps
```

**Status Enum Values:**
- `1` (PENDING) - Payment link created, awaiting payment
- `2` (PAID) - Payment successfully completed
- `3` (FAILED) - Payment failed or was declined
- `4` (EXPIRED) - Payment link expired (if expiration enabled)

**Gateway Enum Values:**
- `upayments` - UPayments gateway
- `myfatoorah` - MyFatoorah gateway (future)

#### `payment_logs`
Stores all payment gateway API requests and responses for debugging.

```sql
- id: bigint (primary key)
- payment_id: bigint (nullable, foreign key to payments)
- method: string (nullable) - HTTP method (GET, POST, etc.)
- url: text (nullable) - Request URL
- request_headers: json (nullable) - HTTP request headers
- request_body: json (nullable) - HTTP request payload
- response_headers: json (nullable) - HTTP response headers
- response_body: json (nullable) - HTTP response data
- status_code: integer (nullable) - HTTP status code
- response_time: decimal(10,2) (nullable) - Response time in milliseconds
- error: text (nullable) - Error message if request failed
- created_at, updated_at: timestamps
```

#### `trips` (Payment Method Field)
Added field to track payment method for each trip.

```sql
- payment_method: tinyint (nullable) - Selected payment method (1=KNET, 2=CASH)
```

**Payment Method Enum Values:**
- `1` (KNET) - Online payment via KNET
- `2` (CASH) - Cash payment to driver

## Environment Configuration

Add these variables to your `.env` file:

```env
# Payment Gateway Selection
PAYMENT_GATEWAY=upayments

# Payment Link Configuration
PAYMENT_ENABLE_EXPIRATION=false                    # Enable/disable link expiration
PAYMENT_LINK_EXPIRATION_MINUTES=15                 # Link validity duration
PAYMENT_LINK_REUSE_THRESHOLD_MINUTES=1            # Minimum time to reuse existing link
PAYMENT_DEEPLINK_BASE_URL=ebhapp://payment        # Deep link base URL for mobile app

# UPayments Configuration
UPAYMENTS_TEST_MODE=true                           # Use sandbox/production environment
UPAYMENTS_API_KEY=your_api_key_here               # Your UPayments API key
UPAYMENTS_TEST_URL=https://sandboxapi.upayments.com
UPAYMENTS_LIVE_URL=https://api.upayments.com
UPAYMENTS_CALLBACK_URL=${APP_URL}/api/v1/customers/payments/callback
UPAYMENTS_WEBHOOK_URL=${APP_URL}/api/v1/customers/payments/webhook

# MyFatoorah Configuration (Future)
MYFATOORAH_TEST_MODE=true
MYFATOORAH_API_KEY=your_api_key_here
MYFATOORAH_TEST_URL=https://apitest.myfatoorah.com
MYFATOORAH_LIVE_URL=https://api.myfatoorah.com
```

**Important Configuration Notes:**

1. **Payment Link Expiration:**
   - When `PAYMENT_ENABLE_EXPIRATION=false` (default), payment links never expire and can be reused
   - When `PAYMENT_ENABLE_EXPIRATION=true`, links expire after the specified duration
   - Reuse threshold prevents generating new links when existing ones are still valid

2. **Deep Link URL:**
   - Used to redirect users back to mobile app after payment
   - Format: `{base_url}?result={success|error}&trip_id={id}&payment_number={number}`

3. **Callback vs Webhook:**
   - **Callback**: Used for redirecting users back to app after payment (web browser)
   - **Webhook**: Used for gateway server-to-server notifications (recommended for reliability)

## API Endpoints

### Customer Payment APIs

Base URL: `/api/v1/customers/payments`

All endpoints require `Authorization: Bearer {token}` and `Language: en|ar` headers except callback/webhook.

---

#### 1. Get Payment Link
**POST** `/api/v1/customers/payments/link`

Generates a payment link for the customer's last completed trip that requires KNET payment.

**Authentication:** Required (Bearer token)

**Business Rules:**
- Only works for trips with `payment_method = KNET` (1)
- Trip must be in COMPLETED status
- Customer must not have any existing pending payment for this trip (unless expired/failed)
- If a pending payment exists and hasn't expired, returns the existing link (no new payment created)
- Automatically generates unique `payment_number` for tracking

**Request Body:**
```json
{}
```
*No request body required - uses authenticated customer's last completed trip*

**Success Response (200):**
```json
{
  "data": {
    "payment_link": "https://sandboxapi.upayments.com/payment/knet?token=abc123..."
  }
}
```

**Error Responses:**

**404 - Trip Not Found:**
```json
{
  "success": false,
  "message": "No eligible trip found for payment"
}
```

**422 - Payment Not Allowed:**
```json
{
  "success": false,
  "message": "This trip cannot be paid online",
  "errors": {
    "trip": ["Trip payment method is not KNET"]
  }
}
```

**500 - Gateway Error:**
```json
{
  "success": false,
  "message": "Failed to generate payment link"
}
```

---

#### 2. Payment Callback
**ANY** `/api/v1/customers/payments/callback`

Handles payment gateway redirect after customer completes payment. Returns deep link redirect to mobile app.

**Authentication:** Not required (public endpoint)

**Query Parameters:**
- `payment_number` (string, required) - Unique payment identifier
- `trackId` (string, required) - Gateway reference ID from UPayments

**Flow:**
1. Validates pending payment exists
2. Verifies payment status with gateway API
3. Updates payment status (PAID/FAILED)
4. Generates deep link with result
5. Redirects to mobile app

**Success Redirect:**
```
ebhapp://payment?result=success&trip_id=123&payment_number=PAY_20251208_000001
```

**Failure Redirect:**
```
ebhapp://payment?result=error&trip_id=123&payment_number=PAY_20251208_000001
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Payment not found",
  "errors": {
    "payment_number": ["The provided payment number is invalid"]
  }
}
```

---

#### 3. Payment Webhook
**ANY** `/api/v1/customers/payments/webhook`

Handles payment gateway server-to-server notifications (recommended for reliability).

**Authentication:** Not required (public endpoint)

**Request Body (from gateway):**
```json
{
  "payment_number": "PAY_20251208_000001",
  "trackId": "upay_track_12345",
  "status": "captured"
}
```

**Flow:**
1. Validates pending payment exists
2. Verifies payment status with gateway API
3. Updates payment status (PAID/FAILED)
4. Returns success response

**Success Response (200):**
```json
{
  "success": true,
  "message": "Success"
}
```

**Error Response (422):**
```json
{
  "success": false,
  "message": "Payment has already been processed"
}
```

## Admin Panel

Access admin panel resources via Filament at `http://admin.localhost:9000/admin/`

### Payment Logs
**URL:** `http://admin.localhost:9000/admin/payment-logs`

View all payment gateway API requests and responses for debugging.

**Features:**
- Read-only resource (no create/edit/delete)
- Filterable by payment, HTTP method, status code
- Searchable by URL, error message
- Sortable columns (newest first by default)
- JSON viewer for request/response data

**Columns:**
- **ID** - Log identifier
- **Payment** - Related payment number (if available)
- **Method** - HTTP method (GET, POST)
- **URL** - Gateway endpoint called
- **Status Code** - HTTP response status (color-coded: green=2xx, yellow=3xx, red=4xx/5xx)
- **Response Time** - Request duration in milliseconds
- **Error** - Error message (if failed)
- **Created At** - Timestamp

**Use Cases:**
- Debug payment gateway integration issues
- Monitor API response times
- Track failed payment requests
- Analyze gateway communication patterns

---

### Payments
**URL:** `http://admin.localhost:9000/admin/payments`

View and manage all payment records.

**Features:**
- View payment details
- Filter by status, gateway, customer, trip
- Search by payment number, gateway reference
- View related trip and customer info
- Track payment attempts and history

**Columns:**
- **Payment Number** - Unique identifier (e.g., PAY_20251208_000001)
- **Customer** - Customer name and ID
- **Trip** - Related trip ID
- **Status** - Payment status badge (Pending/Paid/Failed/Expired)
- **Gateway** - Payment gateway used
- **Amount** - Payment amount and currency
- **Gateway Reference** - Gateway's transaction ID
- **Attempts** - Number of payment attempts
- **Expires At** - Link expiration time (if enabled)
- **Created At** - Payment creation timestamp

---

### Trips
**URL:** `http://admin.localhost:9000/admin/trips`

Trip management includes payment method field.

**Payment Related Fields:**
- **Payment Method** - Shows KNET or Cash
- **Payments** - Relation to payment records
- **Total Amount** - Trip cost that needs to be paid

## Adding a New Payment Gateway

The system is designed to easily add new payment gateways (e.g., MyFatoorah) without changing business logic.

### Step 1: Create Gateway Implementation

```php
// app/Services/Payment/Gateways/MyFatoorahGateway.php
<?php

declare(strict_types=1);

namespace App\Services\Payment\Gateways;

use App\Interfaces\Repositories\Payment\PaymentLogRepositoryInterface;
use App\Interfaces\Repositories\Payment\PaymentRepositoryInterface;
use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\DTOs\CreatePaymentLinkDTO;
use App\Services\Payment\DTOs\PaymentResponseDTO;
use App\Services\Payment\Traits\LogsPaymentRequests;
use App\Services\Payment\Traits\MakesHttpRequests;

class MyFatoorahGateway implements PaymentGatewayInterface
{
    use LogsPaymentRequests;
    use MakesHttpRequests;

    private const string GATEWAY_NAME = 'myfatoorah';

    private readonly string $baseUrl;
    private readonly string $apiKey;
    private readonly bool $testMode;

    public function __construct(
        private readonly PaymentLogRepositoryInterface $paymentLogRepository,
        private readonly PaymentRepositoryInterface $paymentRepository
    ) {
        $this->loadConfiguration();
    }

    public function createPaymentLink(CreatePaymentLinkDTO $dto): PaymentResponseDTO
    {
        // Implement MyFatoorah API integration
        // Return PaymentResponseDTO::success() or PaymentResponseDTO::error()
    }

    public function verifyPayment(int $paymentId, string $gatewayReferenceId): PaymentResponseDTO
    {
        // Implement payment verification
    }

    public function getGatewayName(): string
    {
        return self::GATEWAY_NAME;
    }

    public function getConfiguration(): array
    {
        return [
            'name' => self::GATEWAY_NAME,
            'test_mode' => $this->testMode,
            'base_url' => $this->baseUrl,
            'has_api_key' => !empty($this->apiKey),
        ];
    }

    protected function getPaymentLogRepository(): PaymentLogRepositoryInterface
    {
        return $this->paymentLogRepository;
    }

    private function loadConfiguration(): void
    {
        $this->testMode = (bool)config('payment.myfatoorah.test_mode', true);
        $this->baseUrl = $this->testMode
            ? config('payment.myfatoorah.test_url')
            : config('payment.myfatoorah.live_url');
        $this->apiKey = config('payment.myfatoorah.api_key');
    }
}
```

### Step 2: Register Gateway in Factory

```php
// app/Services/Payment/PaymentGatewayFactory.php
use App\Services\Payment\Gateways\MyFatoorahGateway;

private function resolveGateway(string $gatewayName): PaymentGatewayInterface
{
    return match ($gatewayName) {
        'upayments' => app(UPaymentsGateway::class),
        'myfatoorah' => app(MyFatoorahGateway::class),  // Add new gateway
        default => throw new InvalidArgumentException("Unsupported payment gateway: {$gatewayName}"),
    };
}

private function getRegisteredGateways(): array
{
    return ['upayments', 'myfatoorah'];  // Add to list
}
```

### Step 3: Add Configuration

```php
// config/payment.php (already exists, just configure)
'myfatoorah' => [
    'test_mode' => env('MYFATOORAH_TEST_MODE', true),
    'api_key' => env('MYFATOORAH_API_KEY'),
    'test_url' => env('MYFATOORAH_TEST_URL', 'https://apitest.myfatoorah.com'),
    'live_url' => env('MYFATOORAH_LIVE_URL', 'https://api.myfatoorah.com'),
],
```

### Step 4: Update Environment

```env
# Switch to new gateway
PAYMENT_GATEWAY=myfatoorah

# Add credentials
MYFATOORAH_TEST_MODE=true
MYFATOORAH_API_KEY=your_api_key_here
```

### Step 5: Update Gateway Enum

```php
// app/Enums/Payment/PaymentGatewayEnum.php
enum PaymentGatewayEnum: string implements HasLabel
{
    case UPAYMENTS = 'upayments';
    case MYFATOORAH = 'myfatoorah';  // Add new case

    public function getLabel(): ?string
    {
        return match ($this) {
            self::UPAYMENTS => 'UPayments',
            self::MYFATOORAH => 'MyFatoorah',  // Add label
        };
    }
}
```

**That's it!** No changes needed to:
- Controllers
- Actions
- DTOs
- Repositories
- Database schema
- API endpoints

The Strategy Pattern ensures complete decoupling between business logic and gateway implementation.

## Code Structure

Following the project's **Customer/Rider** architectural separation:

```
app/
├── Actions/Api/V1/Customer/Payment/
│   ├── GetPaymentLinkAction.php           # Generate payment link
│   └── ProcessPaymentAction.php           # Process callback/webhook
├── DTOs/
│   ├── Api/V1/Customer/Payment/
│   │   └── ProcessPaymentDTO.php          # Process payment data transfer
│   └── Services/Payment/
│       ├── CreatePaymentLinkDTO.php       # Payment link creation data
│       ├── CreateCustomerTokenDTO.php     # Customer token data
│       ├── PaymentResponseDTO.php         # Gateway response data
│       ├── PaymentLogDTO.php              # Payment log data
│       └── WebhookDTO.php                 # Webhook data
├── Enums/Payment/
│   ├── PaymentStatusEnum.php              # PENDING, PAID, FAILED, EXPIRED
│   ├── PaymentGatewayEnum.php             # UPAYMENTS, MYFATOORAH
│   └── PaymentMethodEnum.php              # KNET, CASH
├── Exceptions/Payment/
│   ├── PaymentLinkGenerationException.php
│   ├── PaymentNotFoundException.php
│   ├── PaymentAlreadyProcessedException.php
│   └── PaymentVerificationFailedException.php
├── Http/
│   ├── Controllers/Api/V1/Customer/
│   │   └── PaymentController.php
│   ├── Requests/Api/V1/Customer/Payment/
│   │   ├── ProcessPaymentCallbackRequest.php
│   │   └── ProcessPaymentWebhookRequest.php
│   └── Resources/Api/V1/Customer/Payment/
│       └── PaymentLinkResource.php
├── Interfaces/
│   ├── Repositories/Payment/
│   │   ├── PaymentRepositoryInterface.php
│   │   └── PaymentLogRepositoryInterface.php
│   └── Services/Payment/Contracts/
│       ├── PaymentGatewayInterface.php
│       └── PaymentGatewayCustomerTokenInterface.php
├── Pipelines/Api/V1/Customer/Payment/GetPaymentLink/
│   ├── PaymentLinkContext.php              # Pipeline context/state
│   ├── LoadLastTripPipe.php                # Load customer's last trip
│   ├── ValidatePaymentEligibilityPipe.php  # Validate trip eligibility
│   ├── CheckExistingPendingPaymentPipe.php # Check for reusable payment
│   ├── CreatePaymentRecordPipe.php         # Create new payment record
│   ├── GeneratePaymentLinkPipe.php         # Generate gateway link
│   └── UpdatePaymentLinkPipe.php           # Update payment with link
├── Services/Payment/
│   ├── PaymentService.php                  # Main facade
│   ├── PaymentGatewayFactory.php           # Gateway factory
│   ├── Gateways/
│   │   └── UPaymentsGateway.php           # UPayments implementation
│   └── Traits/
│       ├── LogsPaymentRequests.php         # HTTP logging trait
│       └── MakesHttpRequests.php           # HTTP client trait
├── Repositories/Payment/
│   ├── PaymentRepository.php
│   └── PaymentLogRepository.php
└── Models/
    ├── Payment.php
    ├── PaymentLog.php
    └── Trip.php (payment_method field)

config/
└── payment.php                             # Payment system configuration

database/migrations/
├── 2025_12_08_083230_create_payments_table.php
├── 2025_12_08_083231_create_payment_logs_table.php
└── 2025_12_09_092529_add_payment_method_to_trips_table.php

lang/
├── en/
│   └── payments.php                        # English translations
└── ar/
    └── payments.php                        # Arabic translations

routes/api/v1/customers/
└── payment.php                             # Payment routes
```

### Key Architectural Points

1. **Customer Context:** All payment functionality is under `Api/V1/Customer/` following the project's module separation
2. **Pipeline Pattern:** Payment link generation uses Laravel Pipeline for clean, testable flow
3. **Strategy Pattern:** Gateway abstraction allows easy switching between payment providers
4. **Repository Pattern:** All database operations go through repositories
5. **Service Layer:** PaymentService provides unified API, gateways handle provider-specific logic
6. **DTOs:** Data transfer objects ensure type safety throughout the system
7. **Enums:** Strict type checking for statuses, gateways, and payment methods

## Payment Flow (Complete Journey)

### 1. Trip Completion
```
Customer → Rider completes trip → Trip status = COMPLETED
→ Trip has payment_method = KNET (1)
```

### 2. Customer Requests Payment Link
```http
POST /api/v1/customers/payments/link
Authorization: Bearer {customer_token}
Language: en
```

**Pipeline Processing:**
1. **LoadLastTripPipe**: Loads customer's last completed trip
2. **ValidatePaymentEligibilityPipe**:
   - Checks trip status = COMPLETED
   - Checks payment_method = KNET
   - Checks trip hasn't been paid yet
3. **CheckExistingPendingPaymentPipe**:
   - If pending payment exists and not expired → Reuse existing link
   - If expired → Continue to create new payment
4. **CreatePaymentRecordPipe**:
   - Generates unique payment_number (PAY_YYYYMMDD_XXXXXX)
   - Creates payment record with status = PENDING
5. **GeneratePaymentLinkPipe**:
   - Calls PaymentService → UPaymentsGateway
   - Creates customer token (if needed)
   - Creates payment link via gateway API
   - Logs all HTTP requests/responses
6. **UpdatePaymentLinkPipe**:
   - Updates payment with link and gateway_reference_id
   - Sets expiration time (if enabled)

**Response:**
```json
{
  "data": {
    "payment_link": "https://sandboxapi.upayments.com/payment/knet?token=..."
  }
}
```

### 3. Customer Pays via Gateway
```
Customer opens payment_link in browser
→ Enters KNET card details
→ Completes payment on UPayments hosted page
```

### 4. Gateway Processes Payment
```
UPayments processes payment
→ Customer sees success/failure page
→ Gateway redirects to callback URL
→ Gateway sends webhook notification (parallel)
```

### 5. Callback Processing (User Redirect)
```http
GET /api/v1/customers/payments/callback?payment_number=PAY_xxx&trackId=upay_xxx
```

**Processing:**
1. Finds payment by payment_number
2. Verifies payment is PENDING
3. Calls gateway verify API with trackId
4. Updates payment status (PAID or FAILED)
5. Generates deeplink with result
6. Redirects to mobile app

**Redirect:**
```
→ 302 Redirect to: ebhapp://payment?result=success&trip_id=123&payment_number=PAY_xxx
```

### 6. Webhook Processing (Server-to-Server)
```http
POST /api/v1/customers/payments/webhook
Content-Type: application/json

{
  "payment_number": "PAY_xxx",
  "trackId": "upay_xxx",
  "status": "captured"
}
```

**Processing:**
1. Finds payment by payment_number
2. Verifies payment is PENDING (prevents double processing)
3. Calls gateway verify API
4. Updates payment status
5. Returns success response

**Response:**
```json
{
  "success": true,
  "message": "Success"
}
```

### 7. Mobile App Receives Result
```
Deep link opens app
→ App parses result parameter
→ Shows success/failure UI to customer
→ Updates trip payment status in app
```

---

## Testing

### Run Payment Tests

```bash
# Run all payment tests
php artisan test --filter Payment

# Run specific test file
php artisan test tests/Feature/Api/V1/Customer/Payment/

# Run with coverage
php artisan test --filter Payment --coverage
```

### Manual Testing Flow

1. **Setup:**
   ```bash
   php artisan serve --port=9000
   ```

2. **Create test customer and trip:**
   ```bash
   php artisan tinker
   # Create customer, trip with payment_method = KNET (1)
   ```

3. **Get payment link:**
   ```bash
   curl -X POST http://api.localhost:9000/api/v1/customers/payments/link \
     -H "Authorization: Bearer {token}" \
     -H "Language: en"
   ```

4. **Test payment:** Open returned link in browser

5. **Verify logs:** Check admin panel → Payment Logs

---

## Security Considerations

1. **Data Protection:**
   - All gateway communication uses HTTPS
   - Sensitive data (card details) never touches our servers
   - Payment logs sanitize sensitive information
   - Customer tokens used for tokenization

2. **Authentication:**
   - Payment link endpoint requires customer authentication
   - Callback/webhook endpoints are public but validated
   - Payment can only be processed once (prevents replay attacks)

3. **Validation:**
   - Payment number format validation
   - Gateway reference ID validation
   - Status transition validation (only PENDING → PAID/FAILED)
   - Trip ownership validation

4. **Rate Limiting:**
   - Apply rate limiting to payment link endpoint
   - Prevent abuse of callback/webhook endpoints
   - Consider per-customer payment attempt limits

5. **Logging:**
   - All gateway API calls logged with timestamps
   - Request/response bodies stored for debugging
   - Error tracking for failed payments
   - No sensitive data in application logs

---

## UPayments Integration

### Test Environment
- **Base URL:** https://sandboxapi.upayments.com
- **Documentation:** https://developers.upayments.com
- **Support:** Contact UPayments technical support

### Test Credentials
```
Test Mode: Enabled
Test Card: Any test card provided by UPayments
```

### Supported Payment Methods
- **KNET** (Kuwait Net) - Main payment method
- More methods can be added via gateway configuration

### Gateway Response Codes
```php
'captured' => Payment successful (PAID status)
// Other statuses map to FAILED
```

---

## Troubleshooting

### Common Issues

#### 1. "No eligible trip found for payment"
**Cause:** No completed trip exists for customer
**Solution:**
- Verify trip status is COMPLETED
- Check trip has payment_method = KNET (1)
- Ensure trip belongs to authenticated customer

#### 2. "Payment link generation failed"
**Cause:** Gateway API error
**Solution:**
- Check Payment Logs in admin panel for detailed error
- Verify UPayments API credentials in `.env`
- Confirm test mode setting matches environment
- Check network connectivity to gateway

#### 3. Payment callback not working
**Cause:** Callback URL not accessible
**Solution:**
- Ensure callback URL in config is publicly accessible
- For local testing, use ngrok or similar tunneling service
- Verify no firewall blocking gateway requests
- Check payment_logs table for received callbacks

#### 4. Webhook not received
**Cause:** Gateway cannot reach webhook URL
**Solution:**
- Configure public webhook URL in UPayments dashboard
- Use webhook.site for testing
- Verify webhook URL returns 200 OK
- Check server logs for incoming requests

#### 5. Payment already processed error
**Cause:** Trying to process same payment twice
**Solution:**
- This is expected behavior (prevents double-charging)
- Check payment status in database
- If stuck in PENDING, investigate gateway response

#### 6. Deep link not opening app
**Cause:** Mobile app not handling deep link
**Solution:**
- Verify deep link scheme in mobile app configuration
- Check PAYMENT_DEEPLINK_BASE_URL in `.env`
- Test deep link format matches app expectations
- Review mobile app deep link handler logs

### Debug Steps

1. **Check Payment Logs:**
   ```
   Admin Panel → Payment Logs → Filter by payment_id
   ```

2. **Check Payment Status:**
   ```sql
   SELECT * FROM payments WHERE payment_number = 'PAY_xxx';
   ```

3. **Check Laravel Logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Check Gateway Dashboard:**
   - Login to UPayments dashboard
   - Review transaction status
   - Check webhook delivery logs

5. **Test Gateway Connection:**
   ```bash
   curl -H "Authorization: Bearer {api_key}" \
     https://sandboxapi.upayments.com/api/v1/test-connection
   ```

---

## Production Checklist

Before going live:

- [ ] Change `UPAYMENTS_TEST_MODE=false`
- [ ] Update to production API key
- [ ] Configure production callback/webhook URLs
- [ ] Test with real KNET card (small amount)
- [ ] Verify deep link works on production app
- [ ] Enable payment link expiration if needed
- [ ] Set up monitoring/alerts for failed payments
- [ ] Configure rate limiting on payment endpoints
- [ ] Review payment logs regularly
- [ ] Set up backup webhook endpoint
- [ ] Document support escalation process
- [ ] Train support team on payment troubleshooting

---

## Support

For issues or questions:

1. **Check Payment Logs:** Admin Panel → Payment Logs
2. **Check Payment Status:** Admin Panel → Payments
3. **Check Laravel Logs:** `storage/logs/laravel.log`
4. **Check Gateway Dashboard:** UPayments portal
5. **Review Documentation:** https://developers.upayments.com
6. **Contact Gateway Support:** UPayments technical support
