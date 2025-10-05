# Code Structure & Development Patterns

This document explains the architectural patterns and code structure used in this Laravel project.

## Table of Contents

- [API Controller Architecture](#api-controller-architecture)
  - [Pattern Overview](#pattern-overview)
  - [Complete Example](#complete-example)
  - [1. Request Class](#1-request-class)
  - [2. DTO (Data Transfer Object)](#2-dto-data-transfer-object)
  - [3. Action Class](#3-action-class)
  - [4. Resource Class](#4-resource-class)
- [When to Use Each Component](#when-to-use-each-component)
  - [Always Required Components](#always-required-components)
  - [Conditional Components](#conditional-components)
- [Complete Flow Examples](#complete-flow-examples)
- [File Organization](#file-organization)
- [Key Benefits](#key-benefits)
- [Best Practices](#best-practices)
  - [1. Naming Conventions](#1-naming-conventions)
  - [2. Error Handling with safeProcess](#2-error-handling-with-safeprocess)
  - [3. API Documentation Requirements](#3-api-documentation-requirements)
  - [4. Dependencies](#4-dependencies)
  - [5. Documentation Standards](#5-documentation-standards)
- [Quick Checklist](#quick-checklist)
- [Testing Structure & Requirements](#testing-structure--requirements)
  - [Required Tests for Every Resource](#required-tests-for-every-resource)
  - [1. Filament Resource Tests](#1-filament-resource-tests)
  - [2. API Feature Tests](#2-api-feature-tests)
  - [3. Test Organization Structure](#3-test-organization-structure)
  - [4. Required Test Coverage](#4-required-test-coverage)
  - [5. Test Naming Conventions](#5-test-naming-conventions)
  - [6. Common Test Patterns](#6-common-test-patterns)
  - [7. Testing Best Practices](#7-testing-best-practices)
- [⚠️ Pre-Commit Requirements](#️-pre-commit-requirements)
  - [MANDATORY: Run Before Every Commit](#mandatory-run-before-every-commit)
  - [Pre-Commit Workflow](#pre-commit-workflow)
- [Translatable Columns Structure](#translatable-columns-structure)
  - [Model Setup for Multi-Language Support](#model-setup-for-multi-language-support)
  - [Resource Implementation for Translatable Data](#resource-implementation-for-translatable-data)
  - [Migration Pattern for Translatable Columns](#migration-pattern-for-translatable-columns)
  - [Filament Resource for Translatable Fields](#filament-resource-for-translatable-fields)
  - [Translation Best Practices](#translation-best-practices)
  - [Required Files for Translatable Models](#required-files-for-translatable-models)
- [Media Management](#media-management)
  - [Model Setup for Media Support](#model-setup-for-media-support)
  - [Simple Media Usage (Non-Translatable)](#simple-media-usage-non-translatable)
  - [Translatable Media Usage](#translatable-media-usage)
  - [Media with Fallback Support](#media-with-fallback-support)
  - [Media Best Practices](#media-best-practices)

## API Controller Architecture

Our API controllers follow a strict pattern using **Request → DTO → Action → Resource** flow:

### Pattern Overview

```
Request → DTO → Action → Resource → Response
```

Each controller method should include these components when handling data:

1. **Request** - Validation and input handling
2. **DTO** - Data Transfer Object for type-safe data passing
3. **Action** - Business logic execution
4. **Resource** - Response formatting

### Complete Example

Here's how a typical API endpoint is structured:

#### Complete Controller Example
```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
// ... other imports

/**
 * @tags Auth
 */
class AuthController extends Controller
{
    /**
     * Sign up a new user (send and resend opt for sign up)
     * 
     * @unauthenticated
     * @throws \Throwable
     */
    public function signUp(
        SignUpRequest $request,      // 1. Input validation
        SignUpDTO $dto,              // 2. Data transfer object
        SignUpAction $action         // 3. Business logic
    ): SignUpResource {              // 4. Response formatting
        $dto->getDataFromRequest($request);

        return new SignUpResource($action($dto));
    }

    /**
     * Sign out a user
     *
     * @authenticated
     */
    public function signOut(SignOutAction $action): JsonResponse
    {
        $action();

        return $this->successResponse();
    }
}
```

#### 1. Request Class
**Purpose:** Input validation and sanitization

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1\Auth;

use Illuminate\Foundation\Http\FormRequest;

class SignUpRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'email' => [
                'required',
                'email',
            ],
            'password' => 'required|string|min:6',
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => trans('auth.email.required'),
            'email.email' => trans('auth.email.email'),
            'password.required' => trans('auth.password.required'),
            'password.min' => trans('auth.password.min'),
        ];
    }
}
```

**Key Points:**
- Validates incoming data
- Provides custom error messages
- Uses translations for internationalization

#### 2. DTO (Data Transfer Object)
**Purpose:** Type-safe data container and transformation

```php
<?php

declare(strict_types=1);

namespace App\DTOs\Api\V1\Auth;

use App\Enums\User\UserStatusEnum;
use App\Interfaces\DTOs\RequestDataTransferObject;
use Illuminate\Http\Request;

class SignUpDTO implements RequestDataTransferObject
{
    public ?string $name;
    public string $email;
    public ?string $password;
    public UserStatusEnum $status;

    public function getDataFromRequest(Request $request): void
    {
        $this->name = null;
        $this->email = $request->post('email');
        $this->password = $request->post('password');
        $this->status = UserStatusEnum::NOT_VERIFIED;
    }
}
```

**Key Points:**
- Typed properties for data safety
- Implements `RequestDataTransferObject` interface
- Transforms request data into structured format
- Can set default values and business logic

#### 3. Action Class
**Purpose:** Business logic execution

```php
<?php

declare(strict_types=1);

namespace App\Actions\Api\V1\Auth;

use App\DTOs\Api\V1\Auth\SignUpDTO;
use App\Services\Auth\VerificationCodeService;
use App\Interfaces\Api\V1\UserRepoInterface;

class SignUpAction
{
    public function __construct(
        public UserRepoInterface $userRepo
    ) {}

    /**
     * @throws \Throwable
     */
    public function __invoke(SignUpDTO $dto): array
    {
        return safeProcess()
            ->withTransaction()
            ->onFailed(fn ($e) => throw $e)
            ->do(function () use ($dto) {
                $user = $this->userRepo->signUp($dto->email);
                // Business logic continues...
                
                return [
                    'user' => $user,
                ];
            });
    }
}
```

**Key Points:**
- Contains all business logic
- Uses dependency injection
- Returns structured data
- Handles transactions and error cases
- Implements `__invoke()` method for callable objects

#### 4. Resource Class
**Purpose:** Response formatting and data presentation

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class SignUpResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            /**
             * Verification code expiration time
             * This is unix timestamped
             * 
             * @var int
             */
            'expired_at' => $this->resource['expired_at'],
            
            /**
             * User registration status
             * Indicates if user needs email verification
             * 
             * @var string
             */
            'status' => $this->resource['status'] ?? 'pending',
        ];
    }
}
```

**Key Points:**
- Formats response data
- Provides consistent API structure
- Can include documentation in comments
- Transforms internal data to public API format

## When to Use Each Component

### Always Required Components

#### Controller Methods
- **Always** needed for every endpoint
- Defines the route handler and method signature

### Conditional Components

#### Request Classes
**Use when:**
- You have input data to validate
- You need custom validation rules
- You want type-safe input handling

**Skip when:**
- No input data (e.g., `GET /user/profile`)
- Simple endpoints with no validation needed

```php
// With Request (has input data)
public function updateProfile(UpdateProfileRequest $request, UpdateProfileDTO $dto, UpdateProfileAction $action): ProfileResource

// Without Request (no input data)
public function getProfile(GetProfileAction $action): ProfileResource
```

#### DTO Classes
**Use when:**
- You have data to pass between layers
- You need type safety
- You want to transform/structure data

**Skip when:**
- No data processing needed
- Simple passthrough operations

```php
// With DTO (data processing)
public function createUser(CreateUserRequest $request, CreateUserDTO $dto, CreateUserAction $action): UserResource

// Without DTO (no data needed)
public function deleteUser(DeleteUserAction $action): JsonResponse
```

#### Action Classes
**Always required** - Every controller method should have an Action class for business logic

#### Resource Classes
**Use when:**
- You need to format response data
- You want consistent API responses
- You need to transform data for output

**Skip when:**
- Simple success/error responses
- No data to return

```php
// With Resource (returns data)
public function getUser(GetUserAction $action): UserResource

// Without Resource (simple response)
public function deleteUser(DeleteUserAction $action): JsonResponse
{
    $action();
    return $this->successResponse();
}
```

## Complete Flow Examples

### Example 1: Full Flow (Create User)
```php
// Controller
public function createUser(
    CreateUserRequest $request,
    CreateUserDTO $dto,
    CreateUserAction $action
): UserResource {
    $dto->getDataFromRequest($request);
    return new UserResource($action($dto));
}
```

### Example 2: No Input Data (Get Profile)
```php
// Controller  
public function getProfile(GetProfileAction $action): ProfileResource
{
    return new ProfileResource($action());
}
```

### Example 3: No Output Data (Delete User)
```php
// Controller
public function deleteUser(DeleteUserAction $action): JsonResponse
{
    $action();
    return $this->successResponse();
}
```

### Example 4: Simple Action (Logout)
```php
// Controller
public function logout(LogoutAction $action): JsonResponse
{
    $action();
    return $this->successResponse();
}
```

## File Organization

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── V1/
│   │           └── AuthController.php
│   ├── Requests/
│   │   └── Api/
│   │       └── V1/
│   │           └── Auth/
│   │               ├── SignUpRequest.php
│   │               ├── SignInRequest.php
│   │               └── ...
│   └── Resources/
│       └── Api/
│           └── V1/
│               └── Auth/
│                   ├── SignUpResource.php
│                   ├── SignInResource.php
│                   └── ...
├── DTOs/
│   └── Api/
│       └── V1/
│           └── Auth/
│               ├── SignUpDTO.php
│               ├── SignInDTO.php
│               └── ...
└── Actions/
    └── Api/
        └── V1/
            └── Auth/
                ├── SignUpAction.php
                ├── SignInAction.php
                └── ...
```

## Key Benefits

### 1. **Separation of Concerns**
- Each class has a single responsibility
- Easy to test individual components
- Clear data flow

### 2. **Type Safety**
- DTOs provide compile-time type checking
- Reduces runtime errors
- Better IDE support

### 3. **Maintainability**
- Changes in business logic only affect Action classes
- Validation changes only affect Request classes
- Response format changes only affect Resource classes

### 4. **Testability**
- Each component can be unit tested independently
- Mock dependencies easily
- Clear test boundaries

### 5. **Consistency**
- All endpoints follow the same pattern
- Predictable code structure
- Easy onboarding for new developers

## Best Practices

### 1. **Naming Conventions**
- Request: `{Action}Request` (e.g., `SignUpRequest`)
- DTO: `{Action}DTO` (e.g., `SignUpDTO`)
- Action: `{Action}Action` (e.g., `SignUpAction`)
- Resource: `{Action}Resource` (e.g., `SignUpResource`)

### 2. **Error Handling with safeProcess**
**Always use `safeProcess()` instead of try-catch blocks:**

```php
// ✅ Correct - Use safeProcess
public function __invoke(SignUpDTO $dto): array
{
    return safeProcess()
        ->withTransaction()
        ->onFailed(fn ($e) => throw $e)
        ->do(function () use ($dto) {
            // Your business logic here
            return $result;
        });
}

// ❌ Incorrect - Don't use try-catch
public function __invoke(SignUpDTO $dto): array
{
    try {
        DB::transaction(function () {
            // business logic
        });
    } catch (Exception $e) {
        // error handling
    }
}
```

### 3. **API Documentation Requirements**

#### Controller Class Documentation
**Always add `@tags` above controller class:**

```php
/**
 * @tags Auth
 */
class AuthController extends Controller
{
    // controller methods
}
```

#### Method Documentation
**Always document each method with:**
- Short description
- Authentication status (`@authenticated` or `@unauthenticated`)
- Exceptions if needed

```php
/**
 * Sign up a new user (send and resend opt for sign up)
 *
 * @unauthenticated
 *
 * @throws \Throwable
 */
public function signUp(
    SignUpRequest $request,
    SignUpDTO $dto,
    SignUpAction $action
): SignUpResource {
    // method implementation
}

/**
 * Sign out a user
 *
 * @authenticated
 */
public function signOut(SignOutAction $action): JsonResponse
{
    // method implementation
}
```

#### Resource Documentation
**Always document each field in Resource classes:**

```php
class SignUpResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            /**
             * Verification code expiration time
             * This is unix timestamped
             * 
             * @var int
             */
            'expired_at' => $this->resource['expired_at'],
            
            /**
             * User registration status
             * 
             * @var string
             */
            'status' => $this->resource['status'],
        ];
    }
}
```

### 4. **Dependencies**
- Inject services into Actions via constructor
- Keep controllers thin - only orchestration
- Use interfaces for better testability

### 5. **Documentation Standards**
- Use `@tags` for grouping endpoints in API docs
- Mark authentication requirements clearly
- Document all Resource fields with types and descriptions
- Keep method descriptions concise but informative

## Quick Checklist

### For Every New API Endpoint:

#### ✅ Controller
- [ ] Add `@tags ControllerName` above class
- [ ] Add method description
- [ ] Add `@authenticated` or `@unauthenticated`
- [ ] Use Request → DTO → Action → Resource pattern

#### ✅ Request (if input data exists)
- [ ] Validation rules
- [ ] Custom error messages with translations
- [ ] Proper namespace: `App\Http\Requests\Api\V1\{Module}`

#### ✅ DTO (if data processing needed)
- [ ] Typed properties
- [ ] Implement `RequestDataTransferObject` interface
- [ ] `getDataFromRequest()` method
- [ ] Proper namespace: `App\DTOs\Api\V1\{Module}`

#### ✅ Action (always required)
- [ ] Use `safeProcess()` instead of try-catch
- [ ] Dependency injection in constructor
- [ ] `__invoke()` method
- [ ] Proper namespace: `App\Actions\Api\V1\{Module}`

#### ✅ Resource (if formatted output needed)
- [ ] Document every field with description and type
- [ ] Use clear field names
- [ ] Proper namespace: `App\Http\Resources\Api\V1\{Module}`

#### ✅ Translatable Models (if multi-language support needed)
- [ ] **Interface:** Implement `TranslatableInterface`
- [ ] **Trait:** Use `HasTranslatable` trait
- [ ] **Constants:** Define `FIELD` and `FIELD_AR` constants
- [ ] **Method:** Implement `getTranslatableColumns()` array
- [ ] **Resource:** Use `translated()` methods

#### ✅ Media Models (if image/file support needed)
- [ ] **Interface:** Implement `HasMedia`
- [ ] **Trait:** Use `HasMediaTrait` trait
- [ ] **Constants:** Define `IMAGE`, `IMAGE_AR`, `MEDIA_COLLECTION_NAME`
- [ ] **Methods:** Choose appropriate media method (Simple/Translatable/Fallback)
- [ ] **Resource:** Use correct media methods with fallback and documentation

### Error Handling Rules:
- ✅ **Always use:** `safeProcess()->withTransaction()->onFailed()->do()`
- ❌ **Never use:** `try-catch` blocks directly

### Documentation Rules:
- ✅ **Controller class:** `@tags GroupName`
- ✅ **Controller methods:** Description + `@authenticated/@unauthenticated`
- ✅ **Resource fields:** Description, type, and purpose

### 🚨 Pre-Commit Rules (MANDATORY):
- ✅ **Run Pint:** `./vendor/bin/pint` (must pass)
- ✅ **Run Tests:** `php artisan test` (must pass)
- ❌ **Never commit** if either command fails

#### ✅ Tests (always required)
- [ ] **Filament Resource Tests:** Index, Create, Edit, List, Search, Validation
- [ ] **API Feature Tests:** Success, Validation, Auth, Response structure
- [ ] **Database Tests:** Assert state changes and data integrity
- [ ] **Permission Tests:** Access control and authorization
- [ ] **Edge Cases:** Error scenarios and boundary conditions

## Testing Structure & Requirements

### Required Tests for Every Resource

Every Filament Resource and API endpoint **MUST** have comprehensive tests covering all CRUD operations and business logic.

### 1. Filament Resource Tests

#### Required Test Cases for Each Resource:
```php
<?php

declare(strict_types=1);

use App\Filament\Resources\Admins\AdminResource;
use App\Filament\Resources\Admins\Pages\CreateAdmin;
use App\Filament\Resources\Admins\Pages\EditAdmin;
use App\Filament\Resources\Admins\Pages\ListAdmins;
use App\Models\Admin;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function () {
    adminPanelLogin(); // Setup authentication
});

// ✅ 1. Access Control Tests
it('can render index page of the admin resource', function () {
    get(AdminResource::getUrl())->assertOk();
});

it('can render create admin page', function () {
    get(AdminResource::getUrl('create'))->assertSuccessful();
});

it('can render edit admin page', function () {
    get(AdminResource::getUrl('edit', [
        'record' => Admin::factory()->create(),
    ]))->assertSuccessful();
});

// ✅ 2. List/Table Tests
it('can list admins in the datatable', function () {
    $items = Admin::factory()->count(5)->create();
    
    livewire(ListAdmins::class)
        ->assertCanSeeTableRecords($items);
});

it('can search admins in the datatable', function () {
    $admin = Admin::factory()->create(['name' => 'John Doe']);
    $otherAdmin = Admin::factory()->create(['name' => 'Jane Smith']);
    
    livewire(ListAdmins::class)
        ->searchTable('John')
        ->assertCanSeeTableRecords([$admin])
        ->assertCanNotSeeTableRecords([$otherAdmin]);
});

// ✅ 3. Create Tests
it('can create admin', function () {
    $adminData = Admin::factory()->make();

    livewire(CreateAdmin::class)
        ->fillForm([
            'name' => $adminData->name,
            'email' => $adminData->email,
            'password' => 'password123',
            'phone_number' => $adminData->phone_number,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('admins', [
        'name' => $adminData->name,
        'email' => $adminData->email,
    ]);
});

it('validates required fields when creating admin', function () {
    livewire(CreateAdmin::class)
        ->fillForm([
            'name' => '',
            'email' => '',
        ])
        ->call('create')
        ->assertHasFormErrors(['name', 'email']);
});

// ✅ 4. Update Tests
it('can update admin', function () {
    $admin = Admin::factory()->create();
    $newData = Admin::factory()->make();

    livewire(EditAdmin::class, ['record' => $admin->getKey()])
        ->fillForm([
            'name' => $newData->name,
            'phone_number' => $newData->phone_number,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($admin->refresh())
        ->name->toBe($newData->name)
        ->phone_number->toBe($newData->phone_number);
});

// ✅ 5. Delete Tests (if applicable)
it('can delete admin', function () {
    $admin = Admin::factory()->create();
    
    livewire(ListAdmins::class)
        ->callTableAction('delete', $admin);
        
    $this->assertSoftDeleted($admin);
});

// ✅ 6. Permission Tests (if using permissions)
it('cannot access admin resource without permission', function () {
    // Test unauthorized access
    logout();
    get(AdminResource::getUrl())->assertRedirect();
});
```

### 2. API Feature Tests

#### Required Test Cases for Each API Endpoint:

```php
<?php

declare(strict_types=1);

use App\Models\User;
use function Pest\Laravel\{get, post, put, delete, withHeaders};

// ✅ Authentication Tests
describe('Auth API', function () {
    it('can sign up a new user', function () {
        $userData = [
            'email' => 'test@example.com',
            'password' => 'password123',
        ];

        $response = post('/api/v1/auth/sign-up', $userData)
            ->assertStatus(201)
            ->assertJsonStructure([
                'data' => [
                    'expired_at',
                ],
                'meta' => [
                    'message',
                ],
            ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
        ]);
    });

    it('validates required fields for sign up', function () {
        post('/api/v1/auth/sign-up', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    });

    it('can sign in with valid credentials', function () {
        $user = User::factory()->create();

        post('/api/v1/auth/sign-in', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user',
                ],
            ]);
    });

    it('cannot sign in with invalid credentials', function () {
        post('/api/v1/auth/sign-in', [
            'email' => 'wrong@example.com',
            'password' => 'wrongpassword',
        ])
            ->assertStatus(401);
    });

    it('can sign out authenticated user', function () {
        $user = User::factory()->create();
        $token = $user->createToken('test')->plainTextToken;

        withHeaders(['Authorization' => "Bearer $token"])
            ->post('/api/v1/auth/sign-out')
            ->assertStatus(200);
    });

    it('requires authentication for protected endpoints', function () {
        post('/api/v1/auth/sign-out')
            ->assertStatus(401);
    });
});

// ✅ Resource API Tests
describe('User API', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->token = $this->user->createToken('test')->plainTextToken;
        $this->headers = ['Authorization' => "Bearer {$this->token}"];
    });

    it('can get user profile', function () {
        withHeaders($this->headers)
            ->get('/api/v1/user/profile')
            ->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                ],
            ]);
    });

    it('can update user profile', function () {
        $updateData = [
            'name' => 'Updated Name',
        ];

        withHeaders($this->headers)
            ->put('/api/v1/user/profile', $updateData)
            ->assertStatus(200);

        expect($this->user->refresh()->name)
            ->toBe('Updated Name');
    });

    it('validates update data', function () {
        withHeaders($this->headers)
            ->put('/api/v1/user/profile', [
                'email' => 'invalid-email',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    });
});
```

### 3. Test Organization Structure

```
tests/
├── Feature/
│   ├── Api/
│   │   └── V1/
│   │       ├── Auth/
│   │       │   ├── SignUpTest.php
│   │       │   ├── SignInTest.php
│   │       │   └── PasswordResetTest.php
│   │       ├── User/
│   │       │   ├── ProfileTest.php
│   │       │   └── SettingsTest.php
│   │       └── Admin/
│   │           └── AdminManagementTest.php
│   ├── Filament/
│   │   ├── Admin/
│   │   │   ├── AdminResourceTest.php
│   │   │   └── CustomerResourceTest.php
│   │   └── Access/
│   │       └── AuthenticationTest.php
│   └── Integration/
│       ├── EmailServiceTest.php
│       └── SentryIntegrationTest.php
├── Unit/
│   ├── Models/
│   │   ├── UserTest.php
│   │   └── AdminTest.php
│   ├── Services/
│   │   └── VerificationCodeServiceTest.php
│   └── DTOs/
│       └── SignUpDTOTest.php
└── Pest.php
```

### 4. Required Test Coverage

#### ✅ For Every Filament Resource:
- [ ] **Index page** access test
- [ ] **Create page** access test  
- [ ] **Edit page** access test
- [ ] **List/Table** functionality
- [ ] **Search** functionality
- [ ] **Create** operation with valid data
- [ ] **Create** validation tests
- [ ] **Update** operation with valid data
- [ ] **Update** validation tests
- [ ] **Delete** operation (if applicable)
- [ ] **Permission/Access** control tests

#### ✅ For Every API Endpoint:
- [ ] **Success** response with valid data
- [ ] **Validation** error responses
- [ ] **Authentication** requirements
- [ ] **Authorization** checks
- [ ] **Database** state changes
- [ ] **Response** structure validation
- [ ] **Error** handling scenarios
- [ ] **Edge** cases

### 5. Test Naming Conventions

```php
// ✅ Good Test Names
it('can create admin with valid data')
it('validates required fields when creating admin')
it('cannot access admin resource without permission')
it('can sign up with valid email and password')
it('returns 422 when email is invalid')

// ❌ Bad Test Names  
it('test create admin')
it('admin test')
it('validation')
```

### 6. Common Test Patterns

#### Authentication Setup:
```php
beforeEach(function () {
    // For Filament tests
    adminPanelLogin();
    
    // For API tests
    $this->user = User::factory()->create();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => "Bearer {$this->token}"];
});
```

#### Database Testing:
```php
// Assert database changes
$this->assertDatabaseHas('users', ['email' => 'test@example.com']);
$this->assertDatabaseMissing('users', ['email' => 'deleted@example.com']);
$this->assertSoftDeleted($user);
```

#### Response Testing:
```php
// Assert response structure
->assertStatus(200)
->assertJsonStructure(['data' => ['id', 'name']])
->assertJsonPath('data.name', 'John Doe')
->assertJsonValidationErrors(['email', 'password']);
```

### 7. Testing Best Practices

#### ✅ Do:
- Test one thing per test case
- Use descriptive test names
- Test both success and failure scenarios
- Use factories for test data
- Clean up after tests
- Test API response structures
- Test database state changes

#### ❌ Don't:
- Write tests that depend on each other
- Use production data in tests
- Skip validation tests
- Ignore edge cases
- Test implementation details
- Write overly complex tests

## ⚠️ Pre-Commit Requirements

### MANDATORY: Run Before Every Commit

**🚨 Before committing any code, you MUST run these commands and ensure they pass:**

```bash
# 1. Fix code style with Pint
./vendor/bin/pint

# 2. Run all tests  
php artisan test

# 3. Check for any failures
# ❌ If either command fails, DO NOT commit
# ✅ Only commit when both commands pass successfully
```

### Pre-Commit Workflow:

```bash
# Step 1: Fix code style
./vendor/bin/pint
✅ Code style fixed successfully

# Step 2: Run tests
php artisan test
✅ All tests passing

# Step 3: Commit (only if both above pass)
git add .
git commit -m "feat: add user profile endpoint"
```

### ❌ DO NOT COMMIT IF:
- Pint reports any code style issues
- Any tests are failing
- Code coverage is below requirements
- Linting errors exist

### ✅ ONLY COMMIT WHEN:
- `./vendor/bin/pint` runs without errors
- `php artisan test` shows all tests passing
- Code follows the established patterns
- All new features have corresponding tests

## Translatable Columns Structure

### Model Setup for Multi-Language Support

For models that need translation support, follow this pattern:

#### Model Implementation
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Interfaces\Model\TranslatableInterface;
use App\Traits\Model\HasTranslatable;
use Illuminate\Database\Eloquent\Model;

class Company extends Model implements TranslatableInterface
{
    use HasTranslatable;

    // ✅ Define column constants for both languages
    public const string COLUMN_NAME = 'name';
    public const string COLUMN_NAME_AR = 'name_ar';
    
    public const string COLUMN_DESCRIPTION = 'description';
    public const string COLUMN_DESCRIPTION_AR = 'description_ar';

    // ✅ Define media constants for translatable images
    public const string IMAGE = 'image';
    public const string IMAGE_AR = 'image_ar';

    public const string MEDIA_COLLECTION_NAME = 'companies';

    /**
     * ✅ Define which columns are translatable
     */
    public function getTranslatableColumns(): array
    {
        return [
            self::COLUMN_NAME,
            self::COLUMN_DESCRIPTION,
        ];
    }
}
```

#### Key Requirements for Translatable Models:

##### ✅ 1. Interface Implementation
```php
class Company extends Model implements TranslatableInterface
```

##### ✅ 2. Trait Usage
```php
use HasTranslatable;
```

##### ✅ 3. Column Constants Pattern
```php
// Primary language (English)
public const string COLUMN_NAME = 'name';
public const string COLUMN_DESCRIPTION = 'description';

// Arabic translations  
public const string COLUMN_NAME_AR = 'name_ar';
public const string COLUMN_DESCRIPTION_AR = 'description_ar';
```

##### ✅ 4. Translatable Columns Method
```php
public function getTranslatableColumns(): array
{
    return [
        self::COLUMN_NAME,
        self::COLUMN_DESCRIPTION,
        // Add all translatable columns (without _ar suffix)
    ];
}
```

### Resource Implementation for Translatable Data

#### API Resource Pattern
```php
<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1\Company;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Company $company
         */
        $company = $this->resource;

        return [
            /**
             * Company unique identifier
             * 
             * @example 10
             * @var int
             */
            'id' => $company->{Company::COLUMN_ID},

            /**
             * Company name (auto-translated based on request language)
             * 
             * @example Samsung
             * @var string
             */
            'name' => $company->translated(Company::COLUMN_NAME),

            /**
             * Company description (auto-translated based on request language)
             * 
             * @example Leading technology company
             * @var string
             */
            'description' => $company->translated(Company::COLUMN_DESCRIPTION),
           ];
    }
}
```

#### Key Methods for Translatable Resources:

##### ✅ Text Translation
```php
// Automatically returns correct language based on request headers
'name' => $company->translated(Company::COLUMN_NAME),
```

### Migration Pattern for Translatable Columns

```php
Schema::create('companies', function (Blueprint $table) {
    $table->id();
    
    // ✅ English columns
    $table->string('name');
    $table->text('description')->nullable();
    
    // ✅ Arabic columns (with _ar suffix)
    $table->string('name_ar')->nullable();
    $table->text('description_ar')->nullable();
    
    $table->timestamps();
});
```

### Filament Resource for Translatable Fields

```php
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;

public static function form(Schema $schema): Schema
{
    return $schema->components([
        Tabs::make(trans('companies.admin.text'))
            ->tabs([
                Tabs\Tab::make(trans('companies.admin.english'))
                    ->schema([
                        TextInput::make(Company::COLUMN_NAME)
                            ->label(trans('companies.admin.fields.name'))
                            ->required(),
                        Textarea::make(Company::COLUMN_DESCRIPTION)
                            ->label(trans('companies.admin.fields.description')),
                    ]),
                Tabs\Tab::make(trans('companies.admin.arabic'))
                    ->schema([
                        TextInput::make(Company::COLUMN_NAME_AR)
                            ->label(trans('companies.admin.fields.name_ar')),
                        Textarea::make(Company::COLUMN_DESCRIPTION_AR)
                            ->label(trans('companies.admin.fields.description_ar')),
                    ]),
            ]),
    ]);
}
```

### Translation Best Practices

#### ✅ Do:
- Always implement `TranslatableInterface`
- Use `HasTranslatable` trait
- Define constants for both languages (`FIELD` and `FIELD_AR`)
- Use `translated()` method in API resources
- Document translated fields in API resources
- Use tabs in Filament forms for different languages

#### ❌ Don't:
- Hardcode language suffixes in queries
- Skip fallback handling for missing translations
- Mix translated and non-translated data access patterns
- Forget to add new translatable fields to `getTranslatableColumns()`

### Required Files for Translatable Models:

#### ✅ Model Checklist:
- [ ] Implements `TranslatableInterface`
- [ ] Uses `HasTranslatable` trait
- [ ] Defines column constants with AR suffix
- [ ] Implements `getTranslatableColumns()` method
- [ ] Includes fillable fields for both languages

#### ✅ Resource Checklist:
- [ ] Uses `translated()` method for text fields
- [ ] Documents all translated fields with examples
- [ ] Provides fallback values

#### ✅ Migration Checklist:
- [ ] Creates columns for both languages
- [ ] Arabic columns are nullable
- [ ] Consistent naming pattern (field + _ar)

## Media Management

### Model Setup for Media Support

For models that need media (images/files) support, follow this pattern:

#### Model Implementation
```php
<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\Model\HasMediaTrait;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;

class Company extends Model implements HasMedia
{
    use HasMediaTrait;

    // ✅ Define media constants
    public const string IMAGE = 'image';
    public const string IMAGE_AR = 'image_ar';
    // or
    public const string IMAGES = 'images'; // for multiple images
    public const string IMAGES_AR = 'images_ar'; // for multiple images

    public const string MEDIA_COLLECTION_NAME = 'companies';
}
```

#### Key Requirements for Media Models:

##### ✅ 1. Interface Implementation
```php
class Company extends Model implements HasMedia
```

##### ✅ 2. Trait Usage
```php
use HasMediaTrait;
```

##### ✅ 3. Media Constants
```php
// Single image
public const string IMAGE = 'image';
public const string IMAGE_AR = 'image_ar';

// Multiple images
public const string IMAGES = 'images';
public const string IMAGES_AR = 'images_ar';

// Collection name for media storage
public const string MEDIA_COLLECTION_NAME = 'companies';
```

### Simple Media Usage (Non-Translatable)

For basic media without translation support:

#### API Resource Implementation
```php
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Company $company
         */
        $company = $this->resource;

        return [
            /**
             * Single company logo
             * 
             * @example https://admin.com/storage/company-logo.jpg
             * @var string|null
             */
            'logo' => $company->getFirstMediaLink(Company::IMAGE),

            /**
             * Multiple company images
             * 
             * @example ["https://admin.com/image1.jpg", "https://admin.com/image2.jpg"]
             * @var array
             */
            'images' => $company->getMediaLinks(Company::IMAGES),
        ];
    }
}
```

#### Available Simple Media Methods:
```php
// Get single image URL
$model->getFirstMediaLink(MEDIA_CONSTANT);

// Get multiple images URLs
$model->getMediaLinks(MEDIA_CONSTANT);
```

### Translatable Media Usage

For media with translation support (different images for different languages):

#### API Resource Implementation
```php
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Company $company
         */
        $company = $this->resource;

        return [
            /**
             * Single company logo (language-specific)
             * Returns Arabic logo if request language is Arabic, otherwise English
             * 
             * @example https://admin.com/storage/company-logo-ar.jpg
             * @var string|null
             */
            'logo' => $company->getFirstTranslatedMediaLink(Company::IMAGE),

            /**
             * Multiple company images (language-specific)
             * Returns Arabic images if request language is Arabic, otherwise English
             * 
             * @example ["https://admin.com/image1-ar.jpg", "https://admin.com/image2-ar.jpg"]
             * @var array
             */
            'images' => $company->getTranslatedMediaLinks(Company::IMAGES),
        ];
    }
}
```

#### Available Translatable Media Methods:
```php
// Get single translated image URL
$model->getFirstTranslatedMediaLink(MEDIA_CONSTANT);

// Get multiple translated images URLs
$model->getTranslatedMediaLinks(MEDIA_CONSTANT);
```

### Media with Fallback Support

For media with fallback (if Arabic image doesn't exist, use English image):

#### API Resource Implementation
```php
class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /**
         * @var Company $company
         */
        $company = $this->resource;

        return [
            /**
             * Single company logo with fallback
             * If Arabic logo doesn't exist, returns English logo
             * If no logo exists, returns default image
             * 
             * @example https://admin.com/storage/company-logo.jpg
             * @var string
             */
            'logo' => $company->getFirstTranslatedMediaLinkWithFallback(Company::IMAGE) ?: getDefaultImageUrl(),

            /**
             * Multiple company images with fallback
             * If Arabic images don't exist, returns English images
             * 
             * @example ["https://admin.com/image1.jpg", "https://admin.com/image2.jpg"]
             * @var array
             */
            'images' => $company->getTranslatedMediaLinksWithFallback(Company::IMAGES),
        ];
    }
}
```

#### Available Fallback Media Methods:
```php
// Get single image with fallback
$model->getFirstTranslatedMediaLinkWithFallback(MEDIA_CONSTANT);

// Get multiple images with fallback
$model->getTranslatedMediaLinksWithFallback(MEDIA_CONSTANT);
```

### Media Method Selection Guide

#### When to Use Each Method:

##### ✅ Simple Media (No Translation):
```php
// Use when model doesn't support translations
'logo' => $company->getFirstMediaLink(Company::IMAGE);
'images' => $company->getMediaLinks(Company::IMAGES);
```

##### ✅ Translatable Media (Language-Specific):
```php
// Use when you want strict language separation
'logo' => $company->getFirstTranslatedMediaLink(Company::IMAGE);
'images' => $company->getTranslatedMediaLinks(Company::IMAGES);
```

##### ✅ Fallback Media (Translation with Fallback):
```php
// Use when you want graceful fallback to default language
'logo' => $company->getFirstTranslatedMediaLinkWithFallback(Company::IMAGE);
'images' => $company->getTranslatedMediaLinksWithFallback(Company::IMAGES);
```

### Fallback Logic Explanation

The fallback system works as follows:

1. **Check for language-specific image** (e.g., `image_ar` for Arabic)
2. **If not found, fallback to default** (e.g., `image` for English)
3. **If still not found, return null** (or use `?: getDefaultImageUrl()`)

```php
// Example: Arabic user requests company logo
// 1. Check for IMAGE_AR collection (company->image_ar)
// 2. If empty, check IMAGE collection (company->image)
// 3. If empty, return null or default
$logo = $company->getFirstTranslatedMediaLinkWithFallback(Company::IMAGE);
```

### Media Best Practices

#### ✅ Do:
- Always implement `HasMedia` interface
- Use `HasMediaTrait` trait
- Define media constants for each media type
- Use appropriate method based on translation needs
- Provide fallback with `?: getDefaultImageUrl()`
- Document all media fields with examples in API resources
- Use descriptive constant names (`IMAGE`, `IMAGES`, `GALLERY`)

#### ❌ Don't:
- Mix different media method approaches in same model
- Skip fallback handling for critical images
- Hardcode media collection names
- Forget to implement `HasMedia` interface
- Use unclear constant names

### Required Setup for Media Models:

#### ✅ Model Checklist:
- [ ] Implements `HasMedia` interface
- [ ] Uses `HasMediaTrait` trait
- [ ] Defines media constants (`IMAGE`, `IMAGE_AR`, etc.)
- [ ] Defines `MEDIA_COLLECTION_NAME` constant
- [ ] Chooses appropriate media methods based on needs

#### ✅ Resource Checklist:
- [ ] Uses correct media method for model type
- [ ] Documents all media fields with examples
- [ ] Provides fallback values with `?: getDefaultImageUrl()`
- [ ] Includes proper type annotations

#### ✅ Media Method Usage:
- [ ] **Simple:** `getFirstMediaLink()` / `getMediaLinks()`
- [ ] **Translatable:** `getFirstTranslatedMediaLink()` / `getTranslatedMediaLinks()`
- [ ] **Fallback:** `getFirstTranslatedMediaLinkWithFallback()` / `getTranslatedMediaLinksWithFallback()`
