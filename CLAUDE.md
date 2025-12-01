# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel 12 application with a dual-panel architecture featuring:

- **Filament 4 Admin Panel** (admin.localhost) - for backend administration
- **API with Auto-Documentation** (api.localhost) - RESTful API with Scramble-generated docs
- **Multi-subdomain support** for panel separation
- **Strict architectural patterns** for API development (Request → DTO → Action → Resource)

## Development Commands

### Essential Commands

```bash
# Start development server (runs on port 9000)
php artisan serve --port=9000

# Run tests
php artisan test

# Fix code style (Laravel Pint)
./vendor/bin/pint

# Check code style without fixing
./vendor/bin/pint --test

# Run tests with coverage
php artisan test --coverage

# Run specific test
php artisan test --filter TestName
```

### Pre-Commit Requirements (MANDATORY)

Before **EVERY** commit, you MUST run these commands in order and ensure they pass:

```bash
# 1. Fix code style
./vendor/bin/pint

# 2. Run all tests
php artisan test
```

**DO NOT commit if either command fails.**

### Development Tools

```bash
# Access Telescope (development monitoring)
# URL: http://admin.localhost:9000/telescope

# Access API Documentation
# URL: http://api.localhost:9000/docs/v1/riders
# URL: http://api.localhost:9000/docs/v1/customers

# Clear application cache
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Run database migrations
php artisan migrate

# Run database migrations with seeders
php artisan migrate --seed

# Create new migration
php artisan make:migration create_table_name

# Create Filament resource
php artisan make:filament-resource ResourceName --generate
```

## Architecture Overview

### Dual-Panel System

The application uses subdomain-based routing configured in `bootstrap/providers.php`:

1. **Admin Panel** (`admin.localhost`)
    - Filament 4 admin interface
    - Resource management through `app/Filament/Resources/`
    - Admin authentication via Filament's built-in auth

2. **API Panel** (`api.localhost`)
    - RESTful API endpoints
    - Auto-generated documentation via Scramble
    - Multiple API versions registered (v1-riders, v1-customers)
    - API routes in `routes/api/v1/{module}/`

### API Architecture Pattern

This project follows a **strict** Request → DTO → Action → Resource pattern. See `CODE_STRUCTURE.md` for comprehensive
details.

#### Core Flow

```
Request (validation) → DTO (data transfer) → Action (business logic) → Resource (response formatting)
```

#### Quick Pattern Example

```php
// Controller method signature
public function signUp(
    SignUpRequest $request,      // 1. Validates input
    SignUpDTO $dto,              // 2. Type-safe data container
    SignUpAction $action         // 3. Business logic
): SignUpResource {              // 4. Response formatter
    $dto->getDataFromRequest($request);
    return new SignUpResource($action($dto));
}
```

#### When to Use Each Component

- **Request**: Always when accepting input data
- **DTO**: Always when processing/transferring data
- **Action**: Always (contains all business logic)
- **Resource**: Always when returning formatted data (skip for simple JSON responses)

### Module Organization (Customer/Rider)

**🚨 CRITICAL ARCHITECTURAL RULE 🚨**

All API-related code is organized under two main contexts: **Customer** and **Rider**.

#### Decision Flow - FOLLOW THIS EVERY TIME

Before creating ANY file (Action, DTO, Request, Resource, Repository, Test), ask yourself:

```
┌─────────────────────────────────────────┐
│  What are you implementing?             │
└────────────────┬────────────────────────┘
                 │
        ┌────────┴────────┐
        │                 │
    Customer           Rider
    features?          features?
        │                 │
        ▼                 ▼
   Customer/          Rider/
   folder            folder
```

**Examples of Customer features:**

- Customer authentication (sign up, sign in, verify OTP)
- Customer profile management
- Customer trip booking
- Customer trip history
- Customer notifications

**Examples of Rider features:**

- Rider authentication (sign up, sign in, verify OTP)
- Rider profile management
- Rider trip acceptance
- Rider earnings
- Rider availability

#### File Structure Requirements

**✅ CORRECT - All files under Customer/ or Rider/:**

```
app/Actions/Api/V1/Customer/Auth/SignUpAction.php
app/Actions/Api/V1/Rider/Auth/SignUpAction.php

app/DTOs/Api/V1/Customer/Auth/SignUpDTO.php
app/DTOs/Api/V1/Rider/Auth/SignUpDTO.php

app/Http/Requests/Api/V1/Customer/Auth/SignUpRequest.php
app/Http/Requests/Api/V1/Rider/Auth/SignUpRequest.php

app/Http/Resources/Api/V1/Customer/Auth/SignUpResource.php
app/Http/Resources/Api/V1/Rider/Auth/SignUpResource.php

app/Repositories/Customer/CustomerRepository.php
app/Repositories/Rider/RiderRepository.php

tests/Feature/Api/V1/Customer/Auth/SignUpTest.php
tests/Feature/Api/V1/Rider/Auth/SignUpTest.php
```

**❌ WRONG - Files directly under Api/V1/:**

```
app/Actions/Api/V1/Auth/SignUpAction.php           # ❌ Missing Customer or Rider
app/DTOs/Api/V1/Auth/SignUpDTO.php                 # ❌ Missing Customer or Rider
app/Http/Requests/Api/V1/Auth/SignUpRequest.php    # ❌ Missing Customer or Rider
```

#### Module Examples

```
Customer/
├── Auth/              # Customer authentication
│   ├── SignUpAction.php
│   ├── SignInAction.php
│   └── VerifyOtpAction.php
├── Profile/           # Customer profile management
│   ├── UpdateProfileAction.php
│   └── GetProfileAction.php
└── Trip/              # Customer trip operations
    ├── BookTripAction.php
    └── GetTripHistoryAction.php

Rider/
├── Auth/              # Rider authentication (future)
│   ├── SignUpAction.php
│   ├── SignInAction.php
│   └── VerifyOtpAction.php
├── Profile/           # Rider profile management (future)
│   ├── UpdateProfileAction.php
│   └── GetProfileAction.php
└── Trip/              # Rider trip operations (future)
    ├── AcceptTripAction.php
    └── CompleteTripAction.php
```

#### Separation Rules

**Never mix Customer and Rider code.** Each context has its own:

- Authentication system
- Business logic
- Data validation rules
- API endpoints
- Database repositories
- Test suites

**If you need similar functionality for both:**

1. Create separate implementations in each folder
2. Extract shared logic to `app/Services/` if needed
3. Never share Requests, DTOs, Actions, or Resources between Customer and Rider

### File Organization

```
app/
├── Actions/              # Business logic (Action classes)
│   └── Api/V1/
│       ├── Customer/     # Customer-related actions
│       │   └── Auth/     # e.g., SignUpAction, SignInAction
│       └── Rider/        # Rider-related actions
│           └── Auth/     # e.g., RiderSignUpAction (future)
├── DTOs/                 # Data Transfer Objects
│   └── Api/V1/
│       ├── Customer/     # Customer-related DTOs
│       │   └── Auth/     # e.g., SignUpDTO, SignInDTO
│       └── Rider/        # Rider-related DTOs
│           └── Auth/     # e.g., RiderSignUpDTO (future)
├── Filament/             # Admin panel resources
│   ├── Resources/        # CRUD resources
│   ├── Pages/            # Custom pages
│   └── Widgets/          # Dashboard widgets
├── Http/
│   ├── Controllers/      # API controllers
│   │   └── Api/V1/
│   │       ├── Customer/ # Customer controllers
│   │       └── Rider/    # Rider controllers
│   ├── Requests/         # Form request validation
│   │   └── Api/V1/
│   │       ├── Customer/ # Customer requests
│   │       │   └── Auth/ # e.g., SignUpRequest
│   │       └── Rider/    # Rider requests
│   │           └── Auth/ # e.g., RiderSignUpRequest (future)
│   └── Resources/        # API response resources
│       └── Api/V1/
│           ├── Customer/ # Customer resources
│           │   └── Auth/ # e.g., SignUpResource
│           └── Rider/    # Rider resources
│               └── Auth/ # e.g., RiderSignUpResource (future)
├── Models/               # Eloquent models
├── Repositories/         # Data access layer
│   ├── Customer/         # Customer repositories
│   └── Rider/            # Rider repositories
├── Services/             # Reusable services
├── Traits/               # Shared traits
│   ├── Model/            # Model-specific traits
│   │   ├── Aggregates/   # Relationship aggregator traits
│   │   │   ├── VehicleAggregate.php
│   │   │   ├── RiderAggregate.php
│   │   │   ├── TripAggregate.php
│   │   │   └── CompanyAggregate.php
│   │   ├── HasTranslatable.php
│   │   ├── HasMediaTrait.php
│   │   └── HasDefaultColumnModelTrait.php
│   └── Filament/         # Filament-specific traits
├── Interfaces/           # Contracts and interfaces
└── Helpers/              # Helper functions (app/Helpers/general.php)
```

**Key Points**:

- All API V1 code MUST be organized under `Customer/` or `Rider/` folders
- Each context (Customer/Rider) contains its own modules (Auth, Profile, etc.)
- Never create files directly under `Api/V1/` - always use `Customer/` or `Rider/` subfolder
- Routes are also separated: `routes/api/v1/customer/` and `routes/api/v1/rider/`

### Key Architectural Rules

1. **Use `safeProcess()` for business logic** (never raw try-catch):
   ```php
   return safeProcess()
       ->withTransaction()
       ->onFailed(fn ($e) => throw $e)
       ->do(function () use ($dto) {
           // Business logic here
       });
   ```

2. **API Documentation Requirements**:
   ```php
   /**
    * @tags ModuleName
    */
   class Controller extends Controller {
       /**
        * Description of what this does
        *
        * @authenticated  // or @unauthenticated
        * @throws \Throwable
        */
       public function method() {}
   }
   ```

3. **All DTOs must implement `RequestDataTransferObject`** interface

4. **All Actions must use `__invoke()` method**

5. **Resource fields must be documented** with description, type, and example

6. **Model relationships should be aggregated in traits** for better organization:
    - Create relationship aggregate traits in `app/Traits/Model/Aggregates/`
    - Name pattern: `{ModelName}Aggregate`
    - Keep all `BelongsTo`, `HasMany`, `BelongsToMany` relationships in the aggregate trait

7. **Socket Events must extend `BaseSocketEvent`** for consistent real-time broadcasting:
   ```php
   use App\Events\Socket\BaseSocketEvent;
   use Illuminate\Broadcasting\PrivateChannel;

   class NewTripRequestEvent extends BaseSocketEvent
   {
       public function __construct(
           public readonly int $riderId,
           public readonly Trip $trip,
       ) {}

       public function getEventName(): string
       {
           return 'trip.new_request';
       }

       public function getEventData(): array
       {
           return [
               'trip_id' => $this->trip->id,
               // ... event data
           ];
       }

       public function broadcastOn(): array
       {
           return [
               new PrivateChannel("rider.{$this->riderId}"),
           ];
       }
   }
   ```
    - All socket events go in `app/Events/Socket/{Customer|Rider}/`
    - Use descriptive event names: `trip.new_request`, `trip.accepted`,
    - Always use PrivateChannel for user-specific events
    - Event data structure: `{ event: string, data: object, timestamp: string }`
    - Broadcast channels must be defined in `routes/channels.php`
    - Dispatch events using `broadcast()` helper: `broadcast(new TripAcceptedEvent(...))`

   Example aggregate trait pattern:
   ```php
   // app/Traits/Model/Aggregates/VehicleAggregate.php
   /**
    * Vehicle Aggregate Trait
    *
    * Contains all relationship methods for the Vehicle model
    */
   trait VehicleAggregate {
       public function rider(): BelongsTo {
           return $this->belongsTo(Rider::class, Vehicle::COLUMN_RIDER_ID);
       }

       public function carType(): BelongsTo {
           return $this->belongsTo(VehicleSetting::class, Vehicle::COLUMN_CAR_TYPE_ID);
       }
   }

   // app/Models/Vehicle.php
   use App\Traits\Model\Aggregates\VehicleAggregate;

   class Vehicle extends Model {
       use HasDefaultColumnModelTrait;
       use VehicleAggregate;
   }
   ```

8. **ALWAYS select only required fields when eager loading relationships** to optimize query performance:
   ```php
   // ❌ BAD - Loads all columns from all tables
   Trip::query()
       ->with(['rider', 'rider.vehicle', 'rider.vehicle.carMake'])
       ->first();

   // ✅ GOOD - Only loads required fields
   Trip::query()
       ->with([
           'rider:id,first_name,last_name,phone_number,rating',
           'rider.vehicle:id,rider_id,car_make_id,plate_number',
           'rider.vehicle.carMake:id,name',
       ])
       ->first();
   ```

   **Key Points:**
   - Always specify columns after `:` in the relationship name
   - Include foreign keys (e.g., `rider_id`, `car_make_id`) to maintain relationships
   - Include primary keys (`id`) for all models in the chain
   - Only include fields that are actually used in Resources/responses
   - This significantly reduces database query size and memory usage

## Special Features

### Translation System

Models supporting multi-language content must:

1. Implement `TranslatableInterface`
2. Use `HasTranslatable` trait
3. Define column constants: `COLUMN_NAME` and `COLUMN_NAME_AR`
4. Implement `getTranslatableColumns()` method
5. Use `translated()` method in API resources

```php
// Model
class Company extends Model implements TranslatableInterface {
    use HasTranslatable;

    public const string COLUMN_NAME = 'name';
    public const string COLUMN_NAME_AR = 'name_ar';

    public function getTranslatableColumns(): array {
        return [self::COLUMN_NAME];
    }
}

// Resource
'name' => $company->translated(Company::COLUMN_NAME),
```

### Media Management

Models supporting media (images/files) must:

1. Implement `HasMedia` interface
2. Use `HasMediaTrait` trait
3. Define media constants: `IMAGE`, `IMAGE_AR`, `MEDIA_COLLECTION_NAME`

Available methods:

- `getFirstMediaLink()` - Single image (non-translatable)
- `getMediaLinks()` - Multiple images (non-translatable)
- `getFirstTranslatedMediaLink()` - Single image (language-specific)
- `getTranslatedMediaLinks()` - Multiple images (language-specific)
- `getFirstTranslatedMediaLinkWithFallback()` - Single image with fallback
- `getTranslatedMediaLinksWithFallback()` - Multiple images with fallback

### Helper Functions

Available global helpers (see `app/Helpers/general.php`):

- `safeProcess()` - Safe transaction handling
- `translated($model, $field)` - Get translated field
- `getDefaultImageUrl()` - Default image fallback
- `generateOtpCode()` - Generate 4-digit OTP
- `defaultPrefixPhoneNumber()` - Default phone prefix (+965)

## Code Style Conventions

### PHP Standards (Laravel Pint)

- **Strict types**: Always use `declare(strict_types=1);`
- **Strict comparison**: Use `===` and `!==`
- **Type hints**: All parameters and return types must be typed
- **Visibility**: Always declare visibility (public/private/protected)
- **String concatenation**: Single space around `.` operator

### Naming Conventions

- **Request**: `{Action}Request` (e.g., `SignUpRequest`)
- **DTO**: `{Action}DTO` (e.g., `SignUpDTO`)
- **Action**: `{Action}Action` (e.g., `SignUpAction`)
- **Resource**: `{Action}Resource` (e.g., `SignUpResource`)
- **Model constants**: `COLUMN_{NAME}`, `COLUMN_{NAME}_AR`
- **Media constants**: `IMAGE`, `IMAGE_AR`, `MEDIA_COLLECTION_NAME`

## Testing Requirements

### Required Tests for Every Feature

#### Filament Resource Tests

- Index page access
- Create page access
- Edit page access
- List/table functionality
- Search functionality
- Create operation with validation
- Update operation with validation
- Delete operation (if applicable)
- Permission/access control

#### API Endpoint Tests

- Success response with valid data
- Validation error responses
- Authentication requirements
- Authorization checks
- Database state changes
- Response structure validation
- Error handling scenarios
- Edge cases

#### Socket Event Tests (CRITICAL - NEVER SKIP!)

**🚨 MANDATORY: Every socket event MUST have tests! 🚨**

When you create or modify ANY socket event that extends `BaseSocketEvent`, you MUST:

1. **Create event dispatch tests** in the corresponding feature test file
2. **Test event data structure** to ensure correct payload
3. **Test event channels** to verify correct recipients
4. **Test event timing** to ensure it fires after database commits

**Example Socket Event Tests:**

```php
// Test that event is dispatched
test('trip accepted event is dispatched when rider accepts trip', function () {
    Event::fake([TripAcceptedEvent::class]);

    // ... perform action that should trigger event

    Event::assertDispatched(
        TripAcceptedEvent::class,
        fn ($event) => $event->customerId === $expectedCustomerId
            && $event->tripId === $expectedTripId
            && $event->riderId === $expectedRiderId
    );
});

// Test that event is NOT dispatched when it shouldn't be
test('trip completed event is NOT dispatched when trip has more locations', function () {
    Event::fake([TripCompletedEvent::class]);

    // ... perform action with incomplete trip

    Event::assertNotDispatched(TripCompletedEvent::class);
});
```

**Socket Events That Must Be Tested:**

Customer Events (`app/Events/Socket/Customer/`):
- `TripAcceptedEvent` - When rider accepts trip ✅ (AcceptTripBusyStatusTest.php:125-151)
- `TripArrivedEvent` - When rider arrives at pickup location ✅ (ArriveTripTest.php:261-286)
- `TripPickedUpEvent` - When rider picks up customer ✅ (PickUpTripTest.php:268-293)
- `TripCompletedEvent` - When trip is completed ✅ (CompleteTripTest.php:394-419)
- `TripCancelledByRiderEvent` - When rider cancels trip ✅ (CancelTripTest.php)

Rider Events (`app/Events/Socket/Rider/`):
- `NewTripRequestEvent` - When new trip request is sent to rider
- `TripRequestLockedEvent` - When trip is locked (accepted by another rider) ✅ (AcceptTripBusyStatusTest.php:153-254)
- `TripCancelledByCustomerEvent` - When customer cancels trip

**How to Find Event Tests:**

Use these patterns to locate existing event tests:
```bash
# Find all socket event test files
find tests -name "*Test.php" -exec grep -l "Event::fake" {} \;

# Search for specific event tests
grep -r "TripArrivedEvent" tests/
grep -r "TripPickedUpEvent" tests/
grep -r "TripCompletedEvent" tests/
```

**Testing Checklist for New Socket Events:**

- [ ] Event is dispatched with correct data
- [ ] Event is sent to correct channel(s)
- [ ] Event is NOT dispatched when conditions aren't met
- [ ] Event data matches expected structure
- [ ] Event fires AFTER database transaction commits
- [ ] Test file is in correct location: `tests/Feature/Api/V1/{Customer|Rider}/`

### Test Structure

```
tests/
├── Feature/
│   ├── Api/V1/
│   │   ├── Customer/       # Customer API feature tests
│   │   │   └── Auth/       # e.g., SignUpTest, SignInTest
│   │   └── Rider/          # Rider API feature tests
│   │       └── Auth/       # e.g., RiderSignUpTest (future)
│   ├── Filament/           # Filament resource tests
│   └── Integration/        # Integration tests
└── Unit/
    ├── Models/
    ├── Services/
    └── DTOs/
```

**Important**: All API tests must be organized under `Customer/` or `Rider/` folders to match the application structure.

### Testing Patterns

```php
// Filament tests
beforeEach(function () {
    adminPanelLogin();
});

// API tests
beforeEach(function () {
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => "Bearer {$this->token}"];
});
```

## Environment Configuration

### Required Environment Variables

```env
# Application
APP_URL=http://admin.localhost:9000
APP_DOMAIN_ADMIN="admin.localhost"
APP_DOMAIN_API="api.localhost"

# Database
DB_CONNECTION=mysql
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# File Storage
FILESYSTEM_DISK=public
FILAMENT_FILESYSTEM_DISK=public

# Optional: Sentry Error Tracking
SENTRY_LARAVEL_DSN=your-sentry-dsn

# Optional: Redis (for caching/sessions)
SESSION_DRIVER=redis
CACHE_STORE=redis
REDIS_HOST=127.0.0.1
```

### Subdomain Configuration

The application requires subdomain support. For local development:

- Use port 9000: `php artisan serve --port=9000`
- Access admin panel: `http://admin.localhost:9000/`
- Access API docs: `http://api.localhost:9000/v1/docs`

## Important Notes

### Model Configuration

All models have:

- `Model::unguard()` - Mass assignment protection disabled globally
- `Model::shouldBeStrict()` - Strict mode enabled
- `Model::preventLazyLoading()` - Lazy loading prevented in non-production
- `Model::preventAccessingMissingAttributes()` - Attribute access protection

### Filament Customizations

- **Default table sort**: ID descending
- **Pagination options**: 10, 25 records per page
- **Text columns**: Auto-truncated to 40 characters
- **Search**: Enabled by default on text columns
- **Filters layout**: Above content, collapsible
- **Modal actions**: CreateAction and EditAction use slide-over by default

### API Documentation

- Multiple APIs registered: `v1-riders`, `v1-customers`
- All APIs require `Language` header (en/ar)
- Bearer token authentication via Laravel Sanctum
- Access restricted by Gate: `viewApiDocs` (requires specific admin email)

### Rate Limiting

- Configured per user/IP via `config/rate-limiter.php`
- Bypass available with `bypass-limiter` header
- Disabled during unit tests

## Common Pitfalls to Avoid

1. **Never create API files outside Customer/Rider folders** - All Actions, DTOs, Requests, Resources, Repositories, and
   Tests MUST be placed under either `Customer/` or `Rider/` folders
2. **Never use try-catch** in Actions - use `safeProcess()` instead
3. **Never commit without running** `./vendor/bin/pint && php artisan test`
4. **Never skip DTO** when processing input data
5. **Never skip API documentation** (@tags, @authenticated, field docs)
6. **Never hardcode language logic** - use `translated()` methods
7. **Never skip tests** for new features
8. **Never use raw database queries** - use Eloquent/Query Builder
9. **Never skip Request validation** when accepting input
10. **Never mix Customer and Rider code** - Keep them completely separated
11. **🚨 NEVER create socket events without tests** - Every `BaseSocketEvent` MUST have corresponding tests that verify:
    - Event is dispatched with correct data
    - Event is sent to correct channels
    - Event fires after DB commits
    - See "Socket Event Tests" section for details

## Quick Reference

For detailed architectural patterns, translation system, media management, and testing structure, see:

- **CODE_STRUCTURE.md** - Complete development patterns and examples
- **README.md** - Installation and setup instructions
