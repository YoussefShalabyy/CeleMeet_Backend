# CeleMeet — Coding Standards
Version 1.0 | PHP 8.3 | Laravel 13.x

This document defines the concrete coding standards for the CeleMeet backend.
It is a companion to `docs/AI_ENGINEERING_GUIDE.md` and `.agents/AGENTS.md`.
All code in this repository must conform to these standards.

---

## 1. PHP Standards

### Strict Typing

Every PHP file must declare strict types:

```php
<?php

declare(strict_types=1);
```

No exceptions.

### PHP Version Target

PHP **8.3+**. Use modern PHP features:
- Readonly properties (`readonly`)
- Named arguments for clarity
- First-class callables (`Closure::fromCallable`)
- Fibers (only in queue workers if needed)
- Match expressions over switch statements
- Nullsafe operator (`?->`)

### Type Declarations

All method signatures must have:
- Parameter type declarations
- Return type declarations

```php
// ✅ Good
public function deductCoins(int $amount, string $reason): bool

// ❌ Bad
public function deductCoins($amount, $reason)
```

Use `mixed` only when truly mixed. Prefer union types (`int|string`) over `mixed`.

---

## 2. Class Standards

### File Organization

Each file contains exactly one class, interface, trait, or enum.

```
app/Modules/Wallet/Services/WalletService.php → class WalletService
app/Enums/TransactionType.php                 → enum TransactionType
app/Contracts/PaymentGatewayInterface.php     → interface PaymentGatewayInterface
```

### Constructor Promotion

Always use constructor promotion for dependencies:

```php
// ✅ Good
public function __construct(
    private readonly WalletRepository $walletRepository,
    private readonly LoggerInterface $logger,
) {}

// ❌ Bad
private WalletRepository $walletRepository;
public function __construct(WalletRepository $walletRepository) {
    $this->walletRepository = $walletRepository;
}
```

### Class Size Limits

- Methods: **≤ 40 lines**. Extract private methods if exceeded.
- Classes: **≤ 300 lines**. Split responsibilities if exceeded.
- A class that does more than one thing → split it.

### Visibility

Always declare visibility explicitly. No implicit public.

Order within a class:
1. Constants
2. Static properties
3. Instance properties
4. Constructor
5. Public methods
6. Protected methods
7. Private methods

---

## 3. Naming Conventions

### Files and Classes

| Type | Format | Example |
|---|---|---|
| Class | PascalCase | `WalletService.php` |
| Interface | PascalCase + `Interface` suffix | `PaymentGatewayInterface.php` |
| Trait | PascalCase + `Trait` suffix | `ApiResponseTrait.php` |
| Enum | PascalCase | `TransactionType.php` |
| DTO | `{Verb}{Entity}DTO` | `SendMessageDTO.php` |
| Service | `{Entity}Service` | `WalletService.php` |
| Repository | `{Entity}Repository` | `WalletRepository.php` |
| Controller | `{Entity}Controller` | `WalletController.php` |
| Resource | `{Entity}Resource` | `WalletResource.php` |
| Policy | `{Entity}Policy` | `WalletPolicy.php` |
| Request | `{Verb}{Entity}Request` | `SendMessageRequest.php` |

### Methods

```php
// Services — verb + noun
public function initiateVideoCall(InitiateCallDTO $dto): CallSession {}
public function deductCoins(int $amount, TransactionType $type): WalletTransaction {}

// Repositories — query-style
public function findByUserId(int $userId): ?Wallet {}
public function findActiveSubscription(int $userId, int $creatorId): ?Subscription {}

// Boolean methods — is/has/can prefix
public function hasEnoughBalance(int $amount): bool {}
public function isSubscribedTo(int $creatorId): bool {}
public function canSendMessage(): bool {}
```

### Variables

```php
// ✅ Full names, descriptive
$walletTransaction = ...
$creatorProfile = ...
$coinPackage = ...
$subscriptionPlan = ...

// ❌ Abbreviations
$tx = ...
$prof = ...
$pkg = ...
$sub = ...
```

### Constants

```php
// In classes
const MAX_MESSAGE_REFUND_HOURS = 48;
const MIN_WITHDRAWAL_AMOUNT_COINS = 1000;

// In config files — always via config(), never hardcoded
config('wallet.commission_rate')
config('wallet.min_withdrawal')
```

---

## 4. Laravel Conventions

### Controllers (Thin)

Controllers do exactly four things:
1. Validate (via Form Request)
2. Authorize (via Policy)
3. Call Service
4. Return Resource

```php
public function store(SendMessageRequest $request): JsonResponse
{
    $this->authorize('send', Message::class);
    
    $dto = SendMessageDTO::fromRequest($request);
    $message = $this->messageService->send($dto);
    
    return ApiResponse::success(
        data: new MessageResource($message),
        message: 'Message sent successfully',
        code: 201,
    );
}
```

### Services

- One service per feature domain (not per entity).
- Services are responsible for orchestrating business logic.
- Services can call other services via interfaces.
- Services must NOT directly use external provider SDKs.

```php
// ✅ Good — depends on interface
public function __construct(
    private readonly ChatProviderInterface $chatProvider,
) {}

// ❌ Bad — depends on concrete SDK class
public function __construct(
    private readonly StreamClient $stream,
) {}
```

### Form Requests

All input validation lives in Form Request classes:

```php
class SendMessageRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'receiver_id' => ['required', 'integer', 'exists:users,id'],
            'content'     => ['required', 'string', 'max:1000'],
        ];
    }
    
    public function authorize(): bool
    {
        return true; // Authorization in Policy
    }
}
```

### Resources

Always transform Eloquent models through Resources:

```php
class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'content'    => $this->content,
            'type'       => $this->message_type->value,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
```

Never expose `$this->resource->toArray()` directly.

---

## 5. Database Standards

### Migrations

- One concept per migration file.
- Always include `up()` and `down()`.
- Never use `DB::statement()` for DML (only DDL in migrations).
- Always add indexes on foreign keys and frequently queried columns.

### Eloquent Models

```php
class WalletTransaction extends Model
{
    // Always define fillable
    protected $fillable = [
        'wallet_id', 'user_id', 'amount', 'transaction_type', 'status',
    ];

    // Always cast enums, booleans, and JSON
    protected $casts = [
        'transaction_type' => TransactionType::class,
        'status'           => TransactionStatus::class,
        'metadata'         => 'array',
    ];

    // Define all relationships explicitly
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }
}
```

### Query Rules

- Never load all records: always `paginate()` or `limit()`.
- Always eager load relationships that you know you'll need.
- Use `select()` when only specific columns are needed.
- Use DB transactions for all multi-step operations.

---

## 6. Error Handling Standards

### Exception Hierarchy

```
Throwable
└── Exception
    └── App\Exceptions\BaseException
        ├── App\Exceptions\BusinessException  (HTTP 422)
        ├── App\Exceptions\NotFoundException  (HTTP 404)
        ├── App\Exceptions\UnauthorizedException (HTTP 401)
        └── App\Exceptions\ForbiddenException (HTTP 403)
```

### Throwing Exceptions

```php
// ✅ Correct — descriptive message, proper exception type
throw new BusinessException('Insufficient Coins balance to send this message.');
throw new NotFoundException('Creator profile not found.');

// ❌ Wrong — generic, loses context
throw new Exception('Error');
```

### Catching Exceptions

```php
// ✅ Catch specific, log with context
try {
    $result = $this->chatProvider->sendMessage($dto);
} catch (ChatProviderException $e) {
    Log::error('Chat provider failed to deliver message', [
        'message_id' => $dto->messageId,
        'error' => $e->getMessage(),
    ]);
    throw new BusinessException('Message delivery failed. Please try again.');
}

// ❌ Catch-all that hides failures
try {
    // ...
} catch (\Exception $e) {
    return null; // Silent failure — forbidden
}
```

---

## 7. Testing Standards

### Test Types

| Type | Framework | Location | Coverage Target |
|---|---|---|---|
| Unit Tests | PHPUnit/Pest | `tests/Unit/` | Services, DTOs, Helpers |
| Feature Tests | PHPUnit/Pest | `tests/Feature/` | API endpoints, full flows |
| Integration | PHPUnit/Pest | `tests/Integration/` | Provider adapters (fake) |

### Test Naming

```php
// Method-level: it_does_the_thing
it('deducts coins from available balance when message is sent')
it('throws BusinessException when balance is insufficient')
it('returns 422 when receiver_id is missing')
```

### Fakes in Tests

Always use Fake adapters in tests — never real provider credentials.

```php
// In TestCase or test setUp
$this->app->bind(
    ChatProviderInterface::class,
    FakeChatProvider::class,
);
```

---

## 8. Code Formatting

### Laravel Pint

All code is formatted with Laravel Pint using the `laravel` preset.

Run before every commit:
```bash
./vendor/bin/pint
```

CI will reject code that doesn't pass:
```bash
./vendor/bin/pint --test
```

### Import Ordering

1. PHP built-in classes
2. Laravel/framework classes
3. Third-party packages
4. App classes (alphabetical within each group)

Separate groups with blank lines.

---

## 9. Configuration Standards

- All configurable values live in `config/*.php` files.
- All `config()` values come from environment variables with safe defaults.
- Never hardcode URLs, API keys, timeouts, limits, rates, or amounts.

```php
// ✅
$commission = config('wallet.commission_rate', 0.20);

// ❌
$commission = 0.20;
```

---

## 10. Documentation Standards

### PHPDoc — Minimal but Meaningful

Only document what isn't obvious from types:

```php
/**
 * Deducts coins from the user's wallet and creates a transaction record.
 *
 * @throws BusinessException When balance is insufficient.
 * @throws \Throwable        When the DB transaction fails.
 */
public function deductCoins(int $userId, int $amount, TransactionType $type): WalletTransaction
```

Do NOT write PHPDoc that just restates the type declaration:

```php
// ❌ Useless
/**
 * @param int $amount The amount
 * @return bool
 */
public function hasBalance(int $amount): bool
```

---

## Violation Policy

Code that violates these standards:
1. Will not be merged.
2. Will be flagged by Laravel Pint CI.
3. Must be refactored before moving to the next phase.

These standards exist to protect the project for 5-10 years of development.
