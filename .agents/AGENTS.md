# CeleMeet — Agent Rules (Project-Scoped)

> These rules are derived from `docs/AI_ENGINEERING_GUIDE.md`, `docs/BUSINESS_RULES.md`, and `docs/DATABASE_SCHEMA.md`.
> Every AI session on this project **must** follow them without exception.
> If a rule conflicts with a user request, explain the conflict first — never silently violate it.

---

## 0. First Actions on Every Session

1. Read `docs/PROJECT_STATUS.md` — understand what phase is active and what is done.
2. Read `docs/IMPLEMENTATION_ROADMAP.md` — understand the implementation order.
3. Read the relevant phase section before writing any code.
4. Never implement a phase whose prerequisites are not yet complete.

---

## 1. Absolute Priorities (in order)

1. **Simplicity** — the most important quality. Simple > Clever.
2. Correctness — the code must be correct.
3. Readability — another developer must understand it in 6+ months.
4. Maintainability — changes must be localized, not viral.
5. Scalability — design for growth but don't over-engineer for it.

---

## 2. Folder Structure (Non-Negotiable)

```
app/
├── Modules/
│   ├── Auth/
│   │   ├── Controllers/
│   │   ├── Services/
│   │   ├── DTOs/
│   │   ├── Requests/
│   │   ├── Resources/
│   │   └── Policies/
│   ├── User/
│   ├── Creator/
│   ├── Post/
│   ├── Story/
│   ├── Wallet/
│   ├── Payment/
│   ├── Subscription/
│   ├── Chat/
│   ├── Call/
│   ├── Notification/
│   └── Admin/
├── Support/
│   ├── DTOs/
│   ├── Traits/
│   └── Helpers/
├── Contracts/          ← Interfaces only, no implementations
├── Infrastructure/     ← Adapters only, no business logic
│   ├── Chat/
│   ├── VideoCall/
│   ├── MediaStorage/
│   ├── Payment/
│   └── Notification/
├── Enums/
├── Exceptions/
├── Http/
│   ├── Controllers/Api/   ← Base controller only
│   └── Resources/         ← Base resource only
└── Providers/
```

**Never** place business logic outside of `Modules/`.
**Never** place provider SDK calls outside of `Infrastructure/`.

---

## 3. Module Internal Rules

Every module follows this pattern:
- **Controllers** → Validate → Authorize → Call Service → Return Resource. No logic.
- **Services** → All business logic lives here.
- **DTOs** → Services accept DTOs, never raw Request objects.
- **Repositories** → Only create if query logic is complex (multiple joins, complex conditions). Default: use Eloquent directly in the Service.
- **Requests** → Form Request classes for validation.
- **Resources** → JSON transformation. Never expose raw Eloquent.
- **Policies** → Authorization logic.

---

## 4. Provider / Adapter Rules (Critical)

- Every external provider (Stream, Cloudinary, Paymob, Agora, Expo, etc.) MUST have:
  - An Interface in `app/Contracts/`
  - A concrete Adapter in `app/Infrastructure/`
  - A Fake implementation for development/testing
  - A binding in `FoundationServiceProvider`

- **Never** call provider SDK methods directly from Controllers or Services.
- **Never** import Stream, Cloudinary, or Paymob classes inside `app/Modules/`.
- Business logic depends on the Interface, not the concrete class.

---

## 5. Money & Wallet Rules (Critical — Never Violate)

- **Coins are always BIGINT integers. Never use float for money.**
- **Never update wallet balance directly** — always go through WalletService.
- Every Coin movement must create a `wallet_transactions` record.
- Every wallet-modifying operation must be wrapped in a DB transaction.
- `available_balance` can never become negative.
- `total_earned` tracks lifetime creator earnings.

---

## 6. DTO Rules

- Services NEVER accept `Request` objects.
- Every Service method that processes user input must accept a DTO.
- DTOs extend `App\Support\DTOs\BaseDTO`.
- DTOs are readonly value objects — no setters, no mutation.
- Naming: `CreatePostDTO`, `SendMessageDTO`, `InitiateCallDTO`.

---

## 7. API Response Format (Non-Negotiable)

All API responses must use `App\Http\Responses\ApiResponse` and follow this exact shape:

```json
{
  "success": true,
  "message": "Human-readable message",
  "data": {},
  "meta": {},
  "errors": {}
}
```

- `data` is `null` on error responses.
- `errors` is `null` on success responses.
- `meta` contains pagination info when applicable.
- Never return raw arrays or non-standard shapes.

---

## 8. Exception Handling Rules

- Business rule violations → throw `BusinessException` (HTTP 422).
- Resource not found → throw `NotFoundException` (HTTP 404).
- Unauthorized → throw `UnauthorizedException` (HTTP 401).
- Forbidden → throw `ForbiddenException` (HTTP 403).
- Never silently catch exceptions.
- Never let exceptions bubble up to a generic 500 without logging.

---

## 9. Naming Conventions

| Type | Convention | Example |
|---|---|---|
| Classes | PascalCase | `WalletService`, `CreatorProfileResource` |
| Methods | camelCase | `deductCoins()`, `findActiveSubscription()` |
| Variables | camelCase | `$coinAmount`, `$creatorProfile` |
| Constants | UPPER_SNAKE | `MAX_MESSAGE_REFUND_HOURS` |
| DB columns | snake_case | `available_balance`, `created_at` |
| Routes | kebab-case | `/creator-profiles`, `/coin-packages` |
| DTOs | `{Action}{Entity}DTO` | `SendMessageDTO`, `UpdateCreatorProfileDTO` |

**No abbreviations.** Use full descriptive names.
- ❌ `$tx`, `$sub`, `$msg`, `$usr`
- ✅ `$transaction`, `$subscription`, `$message`, `$user`

---

## 10. Enum Usage Rules

- Always use enums from `app/Enums/` — never hardcode string values.
- ❌ `if ($type === 'recharge')`
- ✅ `if ($type === TransactionType::Recharge)`
- When storing enums in DB, use `->value` explicitly.
- When reading from DB, use `::from()` or `::tryFrom()`.

---

## 11. Performance Rules

- **Always paginate** list endpoints — no unbounded queries.
- **Eager load relationships** — no N+1 queries.
- **Queue heavy jobs** — media processing, notification delivery, PDF generation.
- **Cache read-heavy data** — platform settings, feature flags, coin packages.
- Never run synchronous HTTP calls to external providers in the request cycle if they can be queued.

---

## 12. Logging Rules

Log only operationally valuable events:
- All errors (with context)
- Payment events (initiated, completed, failed, refunded)
- Withdrawal events
- External provider failures
- Important business events (call started, subscription activated)

**Never log** user passwords, tokens, raw payment data, or PII unnecessarily.
Use Laravel's structured logging with `context` arrays.

---

## 13. Security Rules

- Every endpoint requires proper Form Request validation.
- Every endpoint requires a Policy check via `$this->authorize()` or `Gate::authorize()`.
- Never trust client-provided IDs — always verify ownership.
- Always verify webhook signatures (Paymob HMAC, etc.) before processing.
- JWT tokens expire. Refresh tokens rotate on use.

---

## 14. The 10-Step AI Coding Workflow

Before implementing any feature:

1. Understand the requirement clearly (re-read Business Rules).
2. Identify affected modules.
3. Confirm DB schema is ready (check DATABASE_SCHEMA.md or migrations).
4. Design the API endpoints.
5. Design DTOs.
6. Design Services.
7. Design Policies and Resources.
8. Implement.
9. Refactor for simplicity and cleanliness.
10. Self-review against this entire AGENTS.md.

---

## 15. What NEVER to Do

- ❌ Never implement without reading PROJECT_STATUS.md first.
- ❌ Never skip a phase's exit criteria.
- ❌ Never create a migration without documenting it in PROJECT_STATUS.md.
- ❌ Never write SQL raw queries when Eloquent can do it cleanly.
- ❌ Never use `DB::statement()` for business logic (only for schema changes).
- ❌ Never update `available_balance` directly outside WalletService.
- ❌ Never hardcode URLs, API keys, commission rates, or limits.
- ❌ Never create a Repository class unless the query complexity justifies it.
- ❌ Never add a dependency without checking if Laravel provides it natively.
- ❌ Never silently catch `Exception` and return `null`.
- ❌ Never import a provider SDK class inside a Module.
- ❌ Never return a raw Eloquent model from a Controller.

---

## 16. Phase Gate Rule

**Before beginning any new phase, verify:**
- [ ] All deliverables from the previous phase are complete.
- [ ] All exit criteria from the previous phase are met.
- [ ] `docs/PROJECT_STATUS.md` has been updated to reflect the completed phase.
- [ ] The application still boots cleanly (`php artisan optimize:clear`).
- [ ] All tests pass (`composer test`).

---

## 17. Conflict Resolution

If there is a conflict between:
- **This file and the user's request** → Flag the conflict. Explain. Do not silently violate.
- **This file and `docs/AI_ENGINEERING_GUIDE.md`** → The guide takes precedence; update this file to match.
- **Implementation and `docs/BUSINESS_RULES.md`** → Business rules take precedence always.
- **Two acceptable approaches** → Choose the simpler one.
