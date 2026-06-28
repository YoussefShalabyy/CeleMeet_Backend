# CeleMeet — Master Implementation Roadmap
**Version 1.0 | Created: 2026-06-27 | Status: Phase 0 — In Progress**

> This document is the **single source of truth for implementation order**.
> Every AI session must read this file before writing code.
> Mark phases complete in `PROJECT_STATUS.md` — do NOT modify this roadmap's phase content.
> This roadmap is designed for a 5–10 year production system.

---

---

## Implementation Order Overview

```
Phase 0  → Foundation (skeleton, no features)
Phase 1  → Authentication (JWT, Google, Apple)
Phase 2  → User & Creator Profiles
Phase 3  → Media Storage (Cloudinary)
Phase 4  → Wallet & Coin Packages
Phase 5  → Payment & Coin Purchase (Paymob)
Phase 6  → Follow System
Phase 7  → Content: Posts
Phase 8  → Content: Stories
Phase 9  → Engagement: Likes & Comments
Phase 10 → Subscription System
Phase 11 → Messaging (Stream Chat)
Phase 12 → Video & Voice Calls (Stream Video)
Phase 13 → Notifications
Phase 14 → Creator Earnings & Withdrawals
Phase 15 → Admin Panel
Phase 16 → Reports & Audit
Phase 17 → Performance Optimization
Phase 18 → Security Hardening
Phase 19 → Production Readiness
```

---

## Phase 0 — Foundation

**Status:** 🔄 In Progress

### Goal
Build the complete architectural skeleton that every future phase depends on.
No business logic. No features. No migrations. Just infrastructure.

### Why This Phase Exists
Every future phase needs: base classes, enums, contracts, fake adapters, config files, exception handlers, and the unified API response format. Building this first prevents architectural drift and eliminates duplication.

### Dependencies
- None. This is the starting point.

### Modules Involved
- `app/Enums/` — all enums
- `app/Contracts/` — all provider interfaces
- `app/Infrastructure/` — all fake adapters
- `app/Support/` — base DTOs, traits, helpers
- `app/Exceptions/` — exception hierarchy
- `app/Http/` — base controller, base resource, API response
- `app/Providers/` — FoundationServiceProvider
- `config/` — wallet, stream, cloudinary, paymob

### Database Impact
None. No migrations.

### API Impact
- `routes/api.php` created but empty.
- `/up` health endpoint available (Laravel default).

### Deliverables
- [ ] `.agents/AGENTS.md` — project-scoped agent rules
- [ ] `docs/CODING_STANDARDS.md` — populated
- [ ] `docs/PROJECT_STATUS.md` — initialized
- [ ] `docs/IMPLEMENTATION_ROADMAP.md` — this file
- [ ] `config/wallet.php`
- [ ] `config/stream.php`
- [ ] `config/cloudinary.php`
- [ ] `config/paymob.php`
- [ ] `.env` + `.env.example` updated (MySQL + all providers)
- [ ] All 17 Enums created
- [ ] All 5 Contracts (interfaces) created
- [ ] All 5 Fake Adapters created
- [ ] `app/Support/DTOs/BaseDTO.php`
- [ ] `app/Support/Traits/ApiResponseTrait.php`
- [ ] `app/Support/Helpers/MoneyHelper.php`
- [ ] `app/Support/Helpers/PaginationHelper.php`
- [ ] `app/Http/Controllers/Api/BaseApiController.php`
- [ ] `app/Http/Resources/BaseApiResource.php`
- [ ] `app/Http/Responses/ApiResponse.php`
- [ ] `app/Exceptions/BaseException.php`
- [ ] `app/Exceptions/BusinessException.php`
- [ ] `app/Exceptions/NotFoundException.php`
- [ ] `app/Exceptions/UnauthorizedException.php`
- [ ] `app/Exceptions/ForbiddenException.php`
- [ ] `bootstrap/app.php` updated (API routes + exception handler)
- [ ] `app/Providers/FoundationServiceProvider.php`
- [ ] `app/Providers/AppServiceProvider.php` updated
- [ ] All Module directories scaffolded
- [ ] `routes/api.php` created
- [ ] `pint.json` configured

### Exit Criteria
- `php artisan config:cache` → no errors
- `php artisan route:list` → `/api/v1/` prefix visible
- `php artisan optimize:clear` → no class resolution errors
- `php artisan serve` → app boots cleanly
- `composer test` → default tests pass
- `./vendor/bin/pint --test` → zero violations

---

## Phase 1 — Authentication Module

**Status:** ⏳ Pending Phase 0

### Goal
Implement JWT-based authentication with Google and Apple OAuth.
Users can register, log in, and receive a JWT + refresh token pair.

### Why This Phase Exists
Authentication is the gate to every other feature. Nothing else can be built without knowing who the user is.

### Dependencies
- Phase 0 complete (base classes, enums, exceptions, API response)

### Modules Involved
- `app/Modules/Auth/`

### Database Impact
New migrations:
- `users` table (UUID, role enum, status, soft deletes)
- `social_accounts` table (Google/Apple OAuth)
- `refresh_tokens` table (device-aware, rotated on use)
- `personal_access_tokens` table (if using Sanctum — evaluate vs custom JWT)

> **Decision Point:** The schema specifies JWT + Refresh Tokens with a custom `refresh_tokens` table.
> This suggests **custom JWT implementation** using `php-open-source-saver/jwt-auth` or a hand-rolled approach.
> Recommended: `php-open-source-saver/jwt-auth` (Laravel 10+ compatible, actively maintained).
> Composer: `composer require php-open-source-saver/jwt-auth`

### API Endpoints
```
POST /api/v1/auth/google        → Social login via Google
POST /api/v1/auth/apple         → Social login via Apple
POST /api/v1/auth/refresh       → Refresh access token
POST /api/v1/auth/logout        → Revoke refresh token
GET  /api/v1/auth/me            → Get current user (authenticated)
```

### Services
- `AuthService` — orchestrates login/logout/token creation
- `SocialAuthService` — handles Google/Apple token verification
- `TokenService` — manages JWT creation and refresh token rotation

### DTOs
- `SocialAuthDTO` — provider, provider_token, device_id
- `RefreshTokenDTO` — refresh_token, device_id

### Policies
- None at this phase (auth endpoints are public or token-gated)

### Resources
- `AuthUserResource` — returns id, uuid, email, username, role, access_token, expires_at

### External Providers Involved
- Google OAuth2 (token verification — no SDK needed, use HTTP)
- Apple Sign-In (token verification — `lcobucci/jwt` for JWT parsing)
- JWT library for access token generation

### Testing Strategy
- Unit: `TokenService` — token creation, expiry, refresh rotation
- Unit: `SocialAuthService` — mocked HTTP responses for Google/Apple
- Feature: `POST /auth/google` → 200 with tokens
- Feature: `POST /auth/refresh` → rotated token returned
- Feature: `POST /auth/logout` → refresh token revoked
- Feature: `GET /auth/me` → 401 without token, 200 with valid token

### Exit Criteria
- [ ] All auth endpoints return correct unified API response format
- [ ] Expired JWT returns `401 UnauthorizedException`
- [ ] Revoked refresh token returns `401`
- [ ] Refresh token rotates on use (old token invalidated)
- [ ] Role is stored and returned (`regular`, `celebrity`, `admin`, `moderator`)
- [ ] All feature tests pass

---

## Phase 2 — User & Creator Profiles

**Status:** ⏳ Pending Phase 1

### Goal
Users can view and update their profile.
Celebrity users have an extended Creator Profile.
Categories for creators are established.

### Why This Phase Exists
Every downstream feature (posts, subscriptions, messaging) references user and creator profiles. The profile data layer must exist before content can be created.

### Dependencies
- Phase 1 (authenticated users)

### Modules Involved
- `app/Modules/User/`
- `app/Modules/Creator/`

### Database Impact
New migrations:
- `creator_profiles` table
- `categories` table
- `creator_categories` pivot table
- `media_assets` table (needed for avatar/cover, but upload comes in Phase 3)

Note: `media_assets` migration must be created here because `creator_profiles` has FK to it.
Actual media upload via Cloudinary is Phase 3.

### API Endpoints
```
GET    /api/v1/users/me                    → Get own profile
PUT    /api/v1/users/me                    → Update own profile
GET    /api/v1/creators/{id}               → Get creator public profile
GET    /api/v1/creators                    → List creators (paginated, filterable)
PUT    /api/v1/creator/profile             → Update creator profile (creator role only)
GET    /api/v1/categories                  → List all categories
```

### Services
- `UserService` — get/update user profile
- `CreatorProfileService` — manage creator profile data
- `CategoryService` — list categories

### DTOs
- `UpdateUserProfileDTO` — username, display fields
- `UpdateCreatorProfileDTO` — display_name, bio, category_ids
- `CreatorFilterDTO` — category_id, search_term, sort_by, page

### Policies
- `UserPolicy` — can only update own profile
- `CreatorProfilePolicy` — only celebrity role can update creator profile

### Resources
- `UserResource` — id, uuid, username, email, role, status
- `CreatorProfileResource` — display_name, bio, avatar, cover, followers_count, verification_badge
- `CreatorProfileListResource` — minimal for list views
- `CategoryResource`

### External Providers
- None (media assets only stored as URLs — actual upload is Phase 3)

### Testing Strategy
- Unit: `CreatorProfileService` — profile update rules
- Feature: `GET /creators/{id}` → 404 for non-existent, 200 for valid
- Feature: `PUT /creator/profile` → 403 for non-celebrity, 200 for celebrity
- Feature: `PUT /users/me` → only updates own profile

### Exit Criteria
- [ ] Creator profile lists use pagination
- [ ] Non-celebrity users cannot access creator update endpoint (403)
- [ ] Soft-deleted users return 404
- [ ] All resources return correct field set

---

## Phase 3 — Media Storage Module

**Status:** ⏳ Pending Phase 2

### Goal
Implement the real Cloudinary adapter.
Users and creators can upload media (images, videos, audio).
Media assets are stored in the `media_assets` table.

### Why This Phase Exists
Posts, stories, messages, and profiles all require media.
The Media module must exist before any content-heavy feature can work.

### Dependencies
- Phase 2 (`media_assets` table exists)
- Phase 0 (`MediaStorageInterface` and `FakeMediaStorage` exist)

### Modules Involved
- `app/Modules/` (no dedicated module — media is cross-cutting)
- `app/Infrastructure/MediaStorage/CloudinaryAdapter.php`

### Database Impact
- Populate `media_assets` with real records

### API Endpoints
```
POST /api/v1/media/upload    → Upload a file, get back media_asset record
DELETE /api/v1/media/{id}    → Delete own media asset
```

### Services
- `MediaService` — orchestrates upload, stores media_asset record

### DTOs
- `UploadMediaDTO` — file, collection, owner_type, owner_id

### Policies
- `MediaPolicy` — can only delete own media

### Resources
- `MediaAssetResource` — id, url, mime_type, width, height, duration

### External Providers
- **Cloudinary** — `cloudinary/cloudinary_php` SDK via `CloudinaryAdapter`
- The adapter implements `MediaStorageInterface`
- Business logic (MediaService) never imports Cloudinary classes

### Testing Strategy
- Unit: `MediaService` — uses `FakeMediaStorage` in tests
- Feature: `POST /media/upload` → returns MediaAssetResource
- Integration: `CloudinaryAdapter` → tested separately with mock HTTP

### Exit Criteria
- [ ] Upload returns a media_asset record with correct URL
- [ ] `MediaService` has no Cloudinary imports (only uses interface)
- [ ] Fake adapter used in all non-integration tests

---

## Phase 4 — Wallet & Coin Packages

**Status:** ⏳ Pending Phase 2

### Goal
Every user gets a Wallet on registration.
Admins can create Coin packages.
Users can view their wallet and transaction history.

### Why This Phase Exists
The Wallet is the core financial primitive. Every Coin-consuming feature (messaging, calls, subscriptions) depends on it.
This phase establishes the ledger before any Coins are ever moved.

### Dependencies
- Phase 1 (authenticated users exist)
- Phase 2 (users/user model established)

### Modules Involved
- `app/Modules/Wallet/`

### Database Impact
New migrations:
- `wallets` table
- `wallet_transactions` table
- `coin_packages` table

**Critical:** Add a `Wallet` creation observer/hook on `User` creation so every user always has a wallet.

### API Endpoints
```
GET  /api/v1/wallet                       → Get own wallet (balances)
GET  /api/v1/wallet/transactions          → Paginated transaction history
GET  /api/v1/coin-packages                → List active coin packages (public)

# Admin only
POST   /api/v1/admin/coin-packages        → Create package
PUT    /api/v1/admin/coin-packages/{id}   → Update package
DELETE /api/v1/admin/coin-packages/{id}   → Deactivate package
```

### Services
- `WalletService` — the most critical service in the project:
  - `credit(userId, amount, type, referenceId, referenceType)` — add coins
  - `deduct(userId, amount, type, referenceId, referenceType)` — remove coins
  - `hold(userId, amount)` — move coins to held (for calls)
  - `releaseHold(userId, amount)` — release held coins back to available
  - `getBalance(userId)` → `Wallet`
  - ALL methods wrapped in DB transactions
- `CoinPackageService` — CRUD for coin packages

### DTOs
- `CreditWalletDTO` — userId, amount, transactionType, referenceId, referenceType, description
- `DeductWalletDTO` — same shape
- `CreateCoinPackageDTO` — coins, price, currency, bonus_percentage, bonus_coins

### Policies
- `WalletPolicy` — users can only view own wallet
- `CoinPackagePolicy` — only admin can create/update/delete

### Resources
- `WalletResource` — available_balance, held_balance, total_earned, total_spent
- `WalletTransactionResource` — amount, type, status, description, created_at
- `CoinPackageResource` — coins, price, currency, bonus_coins

### Testing Strategy (Most Important Phase)
- Unit: `WalletService.deduct()` → throws `BusinessException` when balance insufficient
- Unit: `WalletService.deduct()` → creates `wallet_transaction` record
- Unit: `WalletService.credit()` → increases `available_balance`
- Unit: `WalletService` → balance never goes negative
- Unit: DB transaction rollback on failure
- Feature: `GET /wallet` → shows correct balances
- Feature: `GET /wallet/transactions` → paginated

### Exit Criteria
- [ ] `WalletService` has 100% unit test coverage for balance operations
- [ ] No balance update happens outside `WalletService`
- [ ] Every operation creates a `wallet_transaction` record
- [ ] Balance never goes negative (enforced in code + DB UNSIGNED constraint)
- [ ] All wallet operations are atomic DB transactions

---

## Phase 5 — Payment & Coin Purchase

**Status:** ⏳ Pending Phase 4

### Goal
Users can purchase Coin packages using real money via Paymob.
Successful payment credits Coins to the user's wallet.

### Why This Phase Exists
Without real money → Coins conversion, the platform has no revenue.
This phase enables the fundamental business model.

### Dependencies
- Phase 4 (wallet and coin packages exist)
- Phase 0 (`PaymentGatewayInterface` and `FakePaymentGateway` exist)

### Modules Involved
- `app/Modules/Payment/`
- `app/Infrastructure/Payment/PaymobAdapter.php`

### Database Impact
New migrations:
- `payment_transactions` table

### API Endpoints
```
POST /api/v1/payments/initiate          → Create payment intent (returns checkout URL)
POST /api/v1/payments/callback          → Paymob webhook (public, HMAC-verified)
GET  /api/v1/payments/history           → User's payment history
```

### Services
- `PaymentService` — orchestrates payment initiation and completion:
  - `initiate(userId, coinPackageId)` → payment URL
  - `handleCallback(rawPayload, hmacSignature)` → credits wallet on success
- **Idempotency:** callback must be idempotent — same transaction ID processed only once

### DTOs
- `InitiatePaymentDTO` — userId, coinPackageId
- `PaymentCallbackDTO` — provider, provider_transaction_id, amount, status, raw_response

### Policies
- `PaymentPolicy` — user can only initiate payment for themselves

### Resources
- `PaymentTransactionResource` — amount, currency, coins, status, created_at
- `PaymentInitiateResource` — checkout_url, transaction_id

### External Providers
- **Paymob** — `PaymobAdapter` implements `PaymentGatewayInterface`
- HMAC webhook signature verification is mandatory
- Never process a callback without verifying the signature

### Testing Strategy
- Unit: `PaymentService` — mocked `PaymentGatewayInterface`
- Unit: Duplicate callback → idempotent (no double credit)
- Unit: Failed payment → no wallet credit
- Feature: `POST /payments/initiate` → returns checkout URL
- Feature: `POST /payments/callback` → credits wallet on valid HMAC

### Exit Criteria
- [ ] HMAC verification fails → 400 response, no processing
- [ ] Successful payment → `payment_transactions` record + `wallet_transactions` record
- [ ] Duplicate callback → no double credit
- [ ] `PaymentService` has no Paymob SDK imports

---

## Phase 6 — Follow System

**Status:** ⏳ Pending Phase 2

### Goal
Users can follow and unfollow creators.
Following affects feed, notifications, and recommendations.

### Why This Phase Exists
Following is a prerequisite for feed ranking and notification targeting.

### Dependencies
- Phase 2 (users and creator profiles exist)

### Modules Involved
- `app/Modules/User/` (follow actions belong to User module)

### Database Impact
New migrations:
- `follows` table (defined in V1.0 schema)

```sql
CREATE TABLE follows (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    follower_id BIGINT UNSIGNED NOT NULL,  -- the user following
    creator_id BIGINT UNSIGNED NOT NULL,   -- the creator being followed
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_follow (follower_id, creator_id),
    FOREIGN KEY (follower_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    INDEX idx_creator_followers (creator_id)
);
```

Update `DATABASE_SCHEMA.md` when this phase begins.
Update `creator_profiles.followers_count` counter via DB trigger or service observer.

### API Endpoints
```
POST   /api/v1/creators/{id}/follow      → Follow a creator
DELETE /api/v1/creators/{id}/follow      → Unfollow a creator
GET    /api/v1/users/me/following        → List creators the user follows (paginated)
```

### Services
- `FollowService` — follow(), unfollow(), isFollowing(), getFollowing()

### DTOs
- `FollowCreatorDTO` — followerId, creatorId

### Policies
- `FollowPolicy` — cannot follow yourself

### Resources
- Reuses `CreatorProfileListResource`

### Testing Strategy
- Unit: Cannot follow same creator twice
- Unit: `followers_count` increments on follow, decrements on unfollow
- Feature: `POST /follow` → 200, `DELETE /follow` → 200
- Feature: Double follow → 200 idempotent (or 409, decide in implementation)

### Exit Criteria
- [ ] `follows` table added to DATABASE_SCHEMA.md
- [ ] `followers_count` stays consistent
- [ ] Cannot follow self (403)

---

## Phase 7 — Content: Posts

**Status:** ⏳ Pending Phase 3 + Phase 6

### Goal
Creators can publish free and premium posts (image, video, text).
Users can view free posts and premium posts if subscribed.
Feed is paginated and ordered by recency.

### Why This Phase Exists
Posts are the primary content unit of the platform.

### Dependencies
- Phase 3 (media assets uploadable)
- Phase 6 (follows determine feed)
- Phase 10 (subscription status gates premium content)
  — Note: Posts can be built without subscriptions if premium gating is deferred to Phase 10.

### Modules Involved
- `app/Modules/Post/`

### Database Impact
New migrations:
- `posts` table (already in schema)
- `post_media` pivot (or embed media directly via `media_assets` polymorphic owner)

### API Endpoints
```
POST   /api/v1/posts                     → Create post (creator only)
GET    /api/v1/posts                     → Feed (paginated, authenticated)
GET    /api/v1/posts/{id}                → Single post
PUT    /api/v1/posts/{id}                → Update post (own only)
DELETE /api/v1/posts/{id}                → Soft delete post (own only)
GET    /api/v1/creators/{id}/posts       → Creator's public posts (paginated)
```

### Services
- `PostService` — create, update, delete, getPaginatedFeed, getCreatorPosts
- `ContentAccessService` — determines if a user can view premium content

### DTOs
- `CreatePostDTO` — content_type, caption, visibility, media_ids[]
- `UpdatePostDTO` — caption, visibility
- `FeedFilterDTO` — page, per_page

### Policies
- `PostPolicy` — only creator who owns the post can update/delete
- `PostPolicy::view()` — free: all; premium: subscribed only

### Resources
- `PostResource` — id, caption, visibility, media[], likes_count, comments_count, created_at
- `PostListResource` — minimal for feed

### Testing Strategy
- Unit: Premium post returns 403 for non-subscriber
- Unit: Deleted post returns 404
- Feature: `GET /posts` → paginated feed
- Feature: `POST /posts` → 403 for non-creator

### Exit Criteria
- [ ] Premium posts return 403 for non-subscribers
- [ ] Deleted posts return 404 immediately
- [ ] Feed is paginated (no unbounded queries)

---

## Phase 8 — Content: Stories

**Status:** ⏳ Pending Phase 7

### Goal
Creators can publish 24-hour stories (free or premium).
Expired stories are automatically hidden.
A scheduled job cleans up expired stories.

### Why This Phase Exists
Stories are a distinct content type with expiration logic and a different UX from posts.

### Dependencies
- Phase 7 (posts module establishes content patterns)
- Phase 3 (media for stories)

### Modules Involved
- `app/Modules/Story/`

### Database Impact
New migrations:
- `stories` table (already in schema)

### API Endpoints
```
POST   /api/v1/stories                   → Create story (creator only)
GET    /api/v1/stories                   → Active stories from followed creators
GET    /api/v1/stories/{id}              → Single story
DELETE /api/v1/stories/{id}              → Delete own story
```

### Services
- `StoryService` — create, delete, getActiveStories
- A scheduled command: `ExpireStoriesCommand` — runs hourly via scheduler

### DTOs
- `CreateStoryDTO` — is_premium, media_id, expires_at (calculated as now + 24h)

### Policies
- `StoryPolicy` — same access rules as posts

### Resources
- `StoryResource`

### Testing Strategy
- Unit: Story expires after 24h (time-mocked test)
- Feature: Expired story → 404

### Exit Criteria
- [ ] Expired stories not returned in API
- [ ] Scheduled command registered in `console.php`

---

## Phase 9 — Engagement: Likes & Comments

**Status:** ⏳ Pending Phase 7

### Goal
Users can like and unlike posts.
Users can comment on visible posts.
Blocked users cannot interact.

### Why This Phase Exists
Social engagement is a core retention mechanic.

### Dependencies
- Phase 7 (posts exist)

### Modules Involved
- `app/Modules/Post/` (likes and comments belong to the Post domain)

### Database Impact
New migrations:
- `likes` table (defined in V1.0 schema)
- `comments` table (defined in V1.0 schema)

```sql
-- likes
CREATE TABLE likes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    likeable_type VARCHAR(50) NOT NULL,
    likeable_id BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_like (user_id, likeable_type, likeable_id)
);

-- comments
CREATE TABLE comments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    post_id BIGINT UNSIGNED NOT NULL,
    body TEXT NOT NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_post_comments (post_id, created_at)
);
```

Update `DATABASE_SCHEMA.md` when this phase begins.

### API Endpoints
```
POST   /api/v1/posts/{id}/like           → Like a post
DELETE /api/v1/posts/{id}/like           → Unlike a post
GET    /api/v1/posts/{id}/comments       → List comments (paginated)
POST   /api/v1/posts/{id}/comments       → Add a comment
DELETE /api/v1/comments/{id}             → Delete own comment
```

### Services
- `LikeService` — like(), unlike(), getLikeCount()
- `CommentService` — add(), delete(), paginate()

### DTOs
- `AddCommentDTO` — postId, userId, body

### Policies
- `CommentPolicy` — can only delete own comment; blocked users cannot comment

### Testing Strategy
- Unit: Cannot like same post twice
- Unit: Deleted comment is hidden (soft delete)

### Exit Criteria
- [ ] `likes` and `comments` tables added to DATABASE_SCHEMA.md
- [ ] Like count is consistent

---

## Phase 10 — Subscription System

**Status:** ⏳ Pending Phase 4 + Phase 7

### Goal
Users can subscribe to creators using Coins.
Subscriptions grant access to premium content.
Subscriptions expire and must be manually renewed (no auto-renewal in V1).

### Why This Phase Exists
Subscriptions are a primary revenue stream and the gate for premium content.

### Dependencies
- Phase 4 (wallet and coins — to deduct subscription cost)
- Phase 7 (posts/premium content exists to gate)

### Modules Involved
- `app/Modules/Subscription/`

### Database Impact
New migrations:
- `subscription_plans` table (already in schema)
- `subscriptions` table (already in schema)

### API Endpoints
```
GET    /api/v1/creators/{id}/subscription-plan  → Get creator's subscription plan
POST   /api/v1/subscriptions                    → Subscribe to a creator
GET    /api/v1/subscriptions                    → List my active subscriptions
DELETE /api/v1/subscriptions/{id}               → Cancel subscription

# Creator only
POST   /api/v1/creator/subscription-plan        → Create/update plan
```

### Services
- `SubscriptionService`:
  - `subscribe(userId, planId)` — deducts coins, creates subscription record
  - `isSubscribed(userId, creatorId)` → bool
  - `getActiveSubscription(userId, creatorId)` → Subscription|null
  - Full DB transaction wrapping

### DTOs
- `SubscribeDTO` — userId, planId

### Policies
- `SubscriptionPolicy` — cannot subscribe to own plan

### Resources
- `SubscriptionResource` — plan, creator, started_at, expires_at, status
- `SubscriptionPlanResource` — title, description, coins, duration_days

### Testing Strategy
- Unit: Subscribe deducts correct coins from wallet
- Unit: Subscription created with correct expires_at
- Unit: Subscribing without enough coins → BusinessException
- Unit: `isSubscribed()` returns false after expiry
- Feature: `POST /subscriptions` → 422 when balance insufficient

### Exit Criteria
- [ ] `SubscriptionService.subscribe()` is fully atomic (DB transaction)
- [ ] `ContentAccessService` checks subscription status correctly
- [ ] Expired subscriptions → premium content returns 403

---

## Phase 11 — Messaging (Stream Chat)

**Status:** ⏳ Pending Phase 4 + Phase 10

### Goal
Users can send paid messages to creators.
Before delivery, Coins are deducted atomically.
Message refund system for unanswered messages.

### Why This Phase Exists
Paid messaging is a primary revenue stream and core product feature.

### Dependencies
- Phase 4 (wallet for coin deduction)
- Phase 0 (`ChatProviderInterface` and `FakeChatProvider` exist)

### Modules Involved
- `app/Modules/Chat/`
- `app/Infrastructure/Chat/StreamChatAdapter.php`

### Database Impact
New migrations:
- `paid_messages` table (already in schema)
- `message_refunds` table (already in schema)

### API Endpoints
```
POST /api/v1/messages/send               → Send paid message
GET  /api/v1/messages/conversations      → List conversations
GET  /api/v1/messages/{id}               → Get message details
POST /api/v1/messages/{id}/refund        → Request refund (if eligible)

# Stream Chat token
POST /api/v1/chat/token                  → Get Stream Chat user token
```

### Services
- `MessageService`:
  - `send(SendMessageDTO)` — validates balance → deducts coins → creates paid_message → delivers via Stream Chat
  - Must be fully atomic: if Stream Chat fails, coins must be refunded
- `MessageRefundService` — process refund if eligible (no reply within window)

### DTOs
- `SendMessageDTO` — senderId, receiverId, content, messageType, mediaAssetId?

### Policies
- `MessagePolicy` — cannot send to self; creator must have messaging enabled

### Resources
- `MessageResource` — id, content, type, status, price_in_coins, created_at

### External Providers
- **Stream Chat** — `StreamChatAdapter` implements `ChatProviderInterface`
- Stream Chat token generation for mobile SDK
- Never import Stream SDK in MessageService

### Critical: Atomicity
```
1. Verify balance ≥ price
2. BEGIN TRANSACTION
3. Deduct coins (wallet_transaction created)
4. Insert paid_message record (status: 'pending')
5. COMMIT
6. Deliver via Stream Chat
7. If Stream fails → refund coins via WalletService (new transaction)
8. Update paid_message status
```

### Testing Strategy
- Unit: Stream failure → coins refunded
- Unit: Insufficient balance → BusinessException before any deduction
- Feature: `POST /messages/send` → 422 when balance insufficient

### Exit Criteria
- [ ] Coin deduction + message creation is one atomic transaction
- [ ] Stream failure → automatic refund
- [ ] `MessageService` has no Stream SDK imports

---

## Phase 12 — Voice & Video Calls (Stream Video)

**Status:** ⏳ Pending Phase 11

### Goal
Users can initiate voice and video calls with creators.
Calls are billed per minute at the creator's configured rate.
Insufficient balance during a call ends the call gracefully.

### Why This Phase Exists
Calls are the highest-value service in the platform.

### Dependencies
- Phase 4 (wallet with `held_balance` support)
- Phase 11 (messaging patterns established)
- Phase 0 (`VideoCallProviderInterface` and `FakeVideoCallProvider` exist)

### Modules Involved
- `app/Modules/Call/`
- `app/Infrastructure/VideoCall/StreamVideoAdapter.php`

### Database Impact
New migrations:
- `call_sessions` table (already in schema)
- `creator_services` table (already in schema — stores per-minute rates)

### API Endpoints
```
POST /api/v1/calls/initiate              → Start call (holds coins, creates session)
POST /api/v1/calls/{id}/end              → End call (finalizes billing)
GET  /api/v1/calls/{id}                  → Get call session status
POST /api/v1/calls/webhook               → Stream Video webhook (signature-verified)

# Stream Video token
POST /api/v1/calls/token                 → Get Stream Video user token
```

### Services
- `CallService`:
  - `initiate(InitiateCallDTO)` — verifies balance → holds coins → creates session
  - `end(callId)` — calculates duration → deducts exact cost → releases remaining hold
  - Must handle graceful termination when balance exhausted mid-call

### DTOs
- `InitiateCallDTO` — callerId, calleeId, callType (voice/video)
- `EndCallDTO` — callId, duration_seconds

### Policies
- `CallPolicy` — creator must have calls enabled; sufficient balance required

### Resources
- `CallSessionResource` — id, status, duration_seconds, total_coins_charged, started_at

### External Providers
- **Stream Video** — `StreamVideoAdapter` implements `VideoCallProviderInterface`

### Critical: Billing Logic
```
1. Verify balance ≥ (rate_per_minute × min_hold_minutes)
2. Hold coins (move to held_balance)
3. Create call_session (status: initiated)
4. Connect via Stream Video
5. On end: calculate actual_cost = ceil(duration_seconds / 60) × rate_per_minute
6. Deduct actual_cost (never more than held amount)
7. Release remaining held coins
8. Update call_session (status: completed, total_coins_charged)
```

### Testing Strategy
- Unit: Call cost calculation (edge cases: 0 seconds, exactly 60 seconds, 61 seconds)
- Unit: Held coins released correctly after call ends
- Unit: Balance insufficient → BusinessException, no session created
- Feature: `POST /calls/initiate` → 422 when balance insufficient

### Exit Criteria
- [ ] Held coins always released (even on failure/crash — needs queue job)
- [ ] Per-minute billing is correct to the ceiling
- [ ] `CallService` has no Stream SDK imports

---

## Phase 13 — Notifications

**Status:** ⏳ Pending Phase 11

### Goal
Users receive in-app notifications for key events.
Push notifications delivered via Expo.
Notification failures never affect business operations.

### Why This Phase Exists
Notifications drive re-engagement and inform users of important events.

### Dependencies
- Phase 11 (messaging events trigger notifications)
- Phase 0 (`NotificationProviderInterface` and `FakeNotificationProvider` exist)

### Modules Involved
- `app/Modules/Notification/`
- `app/Infrastructure/Notification/ExpoNotificationAdapter.php`

### Database Impact
New migrations:
- `notifications` table (already in schema)

### API Endpoints
```
GET    /api/v1/notifications             → Paginated notifications
PUT    /api/v1/notifications/{id}/read   → Mark as read
PUT    /api/v1/notifications/read-all    → Mark all as read
DELETE /api/v1/notifications/{id}        → Delete notification
```

### Services
- `NotificationService`:
  - `send(userId, type, title, body, entityType, entityId)` — creates DB record + dispatches push
  - Push notification dispatched as queued Job — never synchronous
  - Failure of push never throws — only logged

### DTOs
- `SendNotificationDTO` — userId, type, title, body, entityType?, entityId?

### External Providers
- **Expo Push Notifications** — `ExpoNotificationAdapter` implements `NotificationProviderInterface`

### Critical: Isolation Rule
Notification delivery must be in a queued Job.
`NotificationService.send()` should return immediately after persisting the DB record.
Any push failure is caught, logged, and dropped — never rethrown.

### Testing Strategy
- Unit: Push failure → notification still saved to DB
- Unit: `NotificationService` dispatches Job (not sends synchronously)
- Feature: `GET /notifications` → paginated

### Exit Criteria
- [ ] Push dispatch is always queued
- [ ] Push failure never propagates to caller
- [ ] All notification types from `NotificationType` enum are handled

---

## Phase 14 — Creator Earnings & Withdrawals

**Status:** ⏳ Pending Phase 12 + Phase 13

### Goal
Creators can view their earnings breakdown.
Creators can request withdrawals.
Admins approve or reject withdrawal requests.

### Why This Phase Exists
Creator monetization is the product's value proposition.
Without a payout mechanism, creators have no reason to use the platform.

### Dependencies
- Phase 12 (calls generate earnings)
- Phase 11 (messages generate earnings)
- Phase 10 (subscriptions generate earnings)

### Modules Involved
- `app/Modules/Wallet/` (extended)
- `app/Modules/Admin/` (approval flow)

### Database Impact
New migrations:
- `withdrawals` table (defined in V1.0 schema)

```sql
CREATE TABLE withdrawals (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    creator_id BIGINT UNSIGNED NOT NULL,
    amount_coins BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'paid') DEFAULT 'pending',
    admin_note TEXT NULL,
    processed_by BIGINT UNSIGNED NULL,
    processed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES users(id),
    FOREIGN KEY (processed_by) REFERENCES users(id),
    INDEX idx_creator_withdrawals (creator_id, status)
);
```

Update `DATABASE_SCHEMA.md` when this phase begins.

### API Endpoints
```
GET  /api/v1/creator/earnings            → Earnings summary + history
POST /api/v1/creator/withdrawals         → Request withdrawal
GET  /api/v1/creator/withdrawals         → Withdrawal history

# Admin only
GET    /api/v1/admin/withdrawals         → Pending withdrawal requests
PUT    /api/v1/admin/withdrawals/{id}    → Approve or reject
```

### Services
- `EarningsService` — calculate earnings by type, period
- `WithdrawalService` — request(), approve(), reject()

### DTOs
- `RequestWithdrawalDTO` — creatorId, amountCoins
- `ProcessWithdrawalDTO` — withdrawalId, adminId, status, note

### Policies
- `WithdrawalPolicy` — only creator can request for themselves; only admin can approve

### Resources
- `EarningsResource` — total, by_type, by_period
- `WithdrawalResource` — amount_coins, status, created_at, processed_at

### Testing Strategy
- Unit: Approved withdrawal → `wallet_transactions` record created
- Unit: Rejected withdrawal → no coins deducted (business rule from BUSINESS_RULES.md)
- Unit: Paid withdrawal is immutable

### Exit Criteria
- [ ] `withdrawals` table added to DATABASE_SCHEMA.md
- [ ] Rejected withdrawal → zero coins affected
- [ ] Paid withdrawal → status cannot change

---

## Phase 15 — Admin Panel

**Status:** ⏳ Pending Phase 14

### Goal
Admins have full management capability over users, creators, content, and platform settings.
All admin actions are audit-logged.

### Why This Phase Exists
A platform without moderation is ungovernable at scale.

### Dependencies
- All previous phases (admin manages everything)

### Modules Involved
- `app/Modules/Admin/`

### Database Impact
New migrations:
- `audit_logs` table (already in schema)
- `feature_flags` table (already in schema)
- `system_settings` table (already in schema)

### API Endpoints
```
# User management
GET    /api/v1/admin/users               → List all users
PUT    /api/v1/admin/users/{id}/ban      → Ban user
PUT    /api/v1/admin/users/{id}/unban    → Unban user

# Creator management
GET    /api/v1/admin/creators            → List creators
PUT    /api/v1/admin/creators/{id}/verify → Grant/revoke verification badge

# Content moderation
GET    /api/v1/admin/reports             → List reports
PUT    /api/v1/admin/reports/{id}        → Resolve report

# Platform settings
GET    /api/v1/admin/settings            → Get all settings
PUT    /api/v1/admin/settings            → Update settings
GET    /api/v1/admin/feature-flags       → List feature flags
PUT    /api/v1/admin/feature-flags/{id}  → Toggle feature flag

# Audit
GET    /api/v1/admin/audit-logs          → Paginated audit trail
```

### Services
- `AdminUserService` — ban, unban, list
- `AdminCreatorService` — verify, list
- `ModerationService` — report review
- `SystemSettingsService` — cached settings management
- `FeatureFlagService` — flag toggle + cache

### Testing Strategy
- Feature: Non-admin → 403 on all admin endpoints
- Feature: Admin can ban/unban users

### Exit Criteria
- [ ] All admin endpoints protected by role check (Policy/Gate)
- [ ] All admin actions create `audit_log` records
- [ ] Admins cannot manually modify wallet balances (business rule)

---

## Phase 16 — Reports & Audit Trail

**Status:** ⏳ Pending Phase 15

### Goal
Users can report posts, stories, comments, and creators.
All reports are reviewable by admins.
Audit log captures all sensitive admin actions.

### Dependencies
- Phase 15 (admin infrastructure)

### Database Impact
- `reports` table (already in schema)
- `audit_logs` table (already in schema)

### API Endpoints
```
POST /api/v1/reports                     → Submit a report
```

### Exit Criteria
- [ ] All report types from `ReportableType` enum are handled
- [ ] Audit log entries created for: user bans, withdrawal approvals, balance adjustments

---

## Phase 17 — Performance Optimization

**Status:** ⏳ Pending Phase 16

### Goal
Optimize critical paths for scale. Add caching, queue heavy operations, fix N+1 queries.

### Key Tasks
- Cache: `coin_packages`, `feature_flags`, `system_settings`, `creator_profiles`
- Cache driver: Redis (configured in Phase 0)
- Queue heavy jobs: notification delivery, media processing, earnings calculation
- Add DB indexes on frequently queried columns (audit in this phase)
- Profile API response times for top 10 endpoints
- Add rate limiting to sensitive endpoints (messaging, payment initiation)

### Testing Strategy
- Benchmark: Key endpoints with `artisan octane:start` or Siege
- Cache: Verify cache hit for settings/packages

### Exit Criteria
- [ ] No N+1 queries on any paginated endpoint
- [ ] Cache strategy documented
- [ ] Rate limiting on payment and auth endpoints

---

## Phase 18 — Security Hardening

**Status:** ⏳ Pending Phase 17

### Goal
Harden the API against common attacks. Validate all inputs. Enforce authorization everywhere.

### Key Tasks
- Rate limiting (Laravel Throttle middleware) on:
  - Auth endpoints: 5 attempts per minute per IP
  - Payment endpoints: 10 per hour per user
  - Message send: 30 per minute per user
- HMAC validation on all provider webhooks
- Security headers middleware (X-Content-Type-Options, X-Frame-Options, etc.)
- Ensure every endpoint has a Policy check — audit all routes
- SQL injection: all queries use parameter binding (enforce via code review)
- Mass assignment: verify all models have `$fillable` defined

### Exit Criteria
- [ ] All routes have corresponding policy check
- [ ] Auth endpoint rate limiting active
- [ ] Webhook signatures verified on all callbacks

---

## Phase 19 — Production Readiness

**Status:** ⏳ Pending Phase 18

### Goal
Prepare the application for deployment to production.

### Key Tasks
- Health check endpoint (`/up` — already exists via Laravel)
- Telescope or Laravel Pulse for monitoring (dev/staging only)
- Log aggregation: Sentry or Papertrail integration
- Queue worker process configuration (Supervisor config)
- Horizon configuration for queue monitoring
- Docker/deployment configuration
- `APP_ENV=production` behavior audit
- `composer install --no-dev --optimize-autoloader`
- Final `README.md` update with deployment instructions

### Exit Criteria
- [ ] `APP_DEBUG=false` → no stack traces in API responses
- [ ] Health endpoint returns 200
- [ ] Queue workers are configured and restart-safe
- [ ] All secrets via environment (no hardcoded credentials)
- [ ] Sentry (or equivalent) error tracking active

---

## Ordering Rationale

| Phase | Why Here |
|---|---|
| 0 before everything | Foundation is prerequisite for all code |
| Auth (1) before profiles (2) | Can't have profiles without identity |
| Profiles (2) before media (3) | Media is owned by profiles |
| Wallet (4) before payment (5) | Need the ledger before money moves |
| Wallet (4) before subscriptions (10) | Subscriptions consume coins |
| Follows (6) before posts (7) | Feed requires follow data |
| Posts (7) before engagement (9) | Can't like/comment without posts |
| Posts (7) before subscriptions (10) | Premium gating needs posts |
| Subscriptions (10) before messaging (11) | Messaging depends on subscription checks |
| Messaging (11) before calls (12) | Calls build on messaging patterns |
| Calls (12) before earnings (14) | Earnings depend on call/message data |
| Earnings (14) before admin (15) | Admin manages withdrawals |
| Optimization (17) after features | Premature optimization is forbidden |
| Security (18) last hardening | Security audit requires full feature set |
| Production (19) final | Only after everything else is hardened |

---

## Implementation Constraints Summary

1. **Never skip a phase.** The order exists for architectural reasons.
2. **Never implement features within Foundation (Phase 0).**
3. **Update PROJECT_STATUS.md after every phase.**
4. **Update DATABASE_SCHEMA.md before implementing any new table.**
5. **Every new migration must be backward-compatible with existing data.**
6. **Never move to Phase N+1 until all exit criteria of Phase N are met.**
