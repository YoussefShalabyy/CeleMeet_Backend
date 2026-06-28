# CeleMeet — Database Schema V1.0
# MySQL 8.0+ | Production Ready
# Approved: 2026-06-27 | Applied: All Critical + Recommended changes from review

---

## Design Invariants

- All Coin amounts are `BIGINT UNSIGNED`. Never FLOAT, DOUBLE, or DECIMAL for Coins.
- `wallet_transactions.amount` is always positive (BIGINT UNSIGNED). Direction is encoded in `transaction_type`.
- `wallet_transactions` and `payment_transactions` are **immutable ledgers** — records are never deleted.
- `creator_profiles.user_id` is the PRIMARY KEY (no separate `id` column). This is a 1:1 extension of `users`.
- All tables that reference a creator reference `creator_profiles(user_id)`.
- Cached counters (`followers_count`, `likes_count`, etc.) must only be updated via atomic `DB::increment/decrement` — never direct assignment.
- Financial tables (`wallet_transactions`, `payment_transactions`, `message_refunds`) have no `deleted_at`. They are immutable by design.
- Provider-specific IDs are stored in generic columns (`external_session_id`, `external_channel_id`) alongside a `provider` column.

---

## Table Dependency Order (for migrations)

```
users
├── social_accounts
├── refresh_tokens
├── device_tokens
├── wallets
│   └── wallet_transactions
├── coin_packages (independent)
├── categories (independent)
├── feature_flags (independent)
├── system_settings (independent)
└── media_assets (polymorphic, no inbound FKs)
    └── creator_profiles (user_id PK → users)
        ├── creator_categories → categories
        ├── follows
        ├── creator_services
        ├── subscription_plans
        │   └── subscriptions → users
        ├── posts
        │   ├── likes (polymorphic)
        │   └── comments → users
        ├── stories → media_assets
        ├── paid_messages → users, media_assets
        │   └── message_refunds → users
        └── call_sessions → users
payment_transactions → users
withdrawals → users
notifications → users
reports → users
audit_logs → users (nullable)
```

---

## Tables

---

### 1. `users`

```sql
CREATE TABLE IF NOT EXISTS users (
    id                BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    uuid              CHAR(36)         NOT NULL UNIQUE,
    username          VARCHAR(50)      UNIQUE NULL,
    email             VARCHAR(150)     UNIQUE NULL,
    phone             VARCHAR(20)      UNIQUE NULL,
    password          VARCHAR(255)     NULL,            -- NULL for social-only accounts
    email_verified_at TIMESTAMP        NULL,
    phone_verified_at TIMESTAMP        NULL,
    role              ENUM('regular', 'celebrity', 'admin', 'moderator') NOT NULL DEFAULT 'regular',
    is_banned         BOOLEAN          NOT NULL DEFAULT FALSE,
    status            ENUM('active', 'inactive', 'suspended') NOT NULL DEFAULT 'active',
    last_active_at    TIMESTAMP        NULL,
    created_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at        TIMESTAMP        NULL,

    INDEX idx_email    (email),
    INDEX idx_username (username),
    INDEX idx_role     (role),
    INDEX idx_status   (status, deleted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Soft delete: `deleted_at`. Preserves referential integrity for all financial/message records.
- `password` renamed from `password_hash` → Laravel Auth convention.
- `role` + `status` are separate concerns: role is permanent identity, status is operational state.

---

### 2. `social_accounts`

```sql
CREATE TABLE IF NOT EXISTS social_accounts (
    id          BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED  NOT NULL,
    provider    VARCHAR(50)      NOT NULL,    -- e.g. 'google', 'apple'. VARCHAR for extensibility.
    provider_id VARCHAR(255)     NOT NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_social        (provider, provider_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_socials (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `provider` is VARCHAR(50) not ENUM — OAuth providers expand (Facebook, Twitter/X, TikTok, etc.) without schema changes.

---

### 3. `refresh_tokens`

```sql
CREATE TABLE IF NOT EXISTS refresh_tokens (
    id         BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED  NOT NULL,
    device_id  VARCHAR(255)     NULL,         -- Optional client-provided device identifier
    token      VARCHAR(512)     NOT NULL,
    expires_at TIMESTAMP        NOT NULL,
    revoked_at TIMESTAMP        NULL,         -- NULL = active; populated = revoked (soft revocation)
    created_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_token (token),             -- O(1) lookup on authentication
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_tokens (user_id)          -- Fast logout-all-devices query
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `revoked_at` provides audit trail (when was the session invalidated, from which device).
- Expired + revoked tokens should be cleaned up by a scheduled Artisan command.

---

### 4. `device_tokens`

```sql
CREATE TABLE IF NOT EXISTS device_tokens (
    id         BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED  NOT NULL,
    token      VARCHAR(512)     NOT NULL,
    platform   ENUM('ios', 'android', 'web') NOT NULL,
    is_active  BOOLEAN          NOT NULL DEFAULT TRUE,
    created_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_device_token (token),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_active_tokens (user_id, is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Required for Phase 13 (Push Notifications via Expo/FCM).
- Tokens are upserted on each login. UNIQUE on token prevents duplicates.
- `is_active = FALSE` when user logs out from a device.

---

### 5. `media_assets`

```sql
CREATE TABLE IF NOT EXISTS media_assets (
    id          BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    owner_id    BIGINT UNSIGNED  NOT NULL,
    owner_type  VARCHAR(50)      NOT NULL,    -- Eloquent morph class name
    collection  VARCHAR(50)      NOT NULL,    -- e.g. 'avatar', 'cover', 'post', 'story', 'message'
    provider    VARCHAR(50)      NOT NULL DEFAULT 'cloudinary',
    provider_id VARCHAR(255)     NULL,        -- Cloudinary public_id or equivalent
    url         VARCHAR(500)     NOT NULL,
    mime_type   VARCHAR(100)     NULL,
    size        BIGINT UNSIGNED  NULL,        -- bytes
    width       INT UNSIGNED     NULL,        -- pixels
    height      INT UNSIGNED     NULL,        -- pixels
    duration    INT UNSIGNED     NULL,        -- seconds (video/audio)
    metadata    JSON             NULL,        -- provider-specific extra data

    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_owner (owner_type, owner_id, collection)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- No `deleted_at`. Media deletion goes through `MediaStorageInterface.delete()` + DB row delete.
- Polymorphic: no FK constraint on `owner_id` (morph pattern). App-level integrity.
- `collection` + `owner_type` + `owner_id` composite index covers all polymorphic lookups.

---

### 6. `wallets`

```sql
CREATE TABLE IF NOT EXISTS wallets (
    id                BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id           BIGINT UNSIGNED  NOT NULL UNIQUE,
    available_balance BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Coins available to spend
    held_balance      BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Coins reserved during active calls
    total_earned      BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Lifetime creator earnings
    total_spent       BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Lifetime total spent
    created_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at        TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Single source of truth for user balances. Never update `available_balance` directly — always via WalletService.
- Every balance change must create a corresponding `wallet_transactions` record.
- `held_balance` is reserved during active calls and reconciled on call end.

---

### 7. `coin_packages`

```sql
CREATE TABLE IF NOT EXISTS coin_packages (
    id               BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    coins            BIGINT UNSIGNED  NOT NULL,
    price            DECIMAL(12,2)    NOT NULL,     -- Real money (DECIMAL, not Coins)
    currency         CHAR(3)          NOT NULL DEFAULT 'USD',
    bonus_percentage DECIMAL(5,2)     NOT NULL DEFAULT 0.00,
    bonus_coins      BIGINT UNSIGNED  NOT NULL DEFAULT 0,
    is_active        BOOLEAN          NOT NULL DEFAULT TRUE,
    sort_order       INT UNSIGNED     NOT NULL DEFAULT 0,
    created_at       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 8. `categories`

```sql
CREATE TABLE IF NOT EXISTS categories (
    id         BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)     NOT NULL UNIQUE,
    slug       VARCHAR(100)     NOT NULL UNIQUE,
    sort_order INT UNSIGNED     NOT NULL DEFAULT 0,
    created_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 9. `creator_profiles`

```sql
CREATE TABLE IF NOT EXISTS creator_profiles (
    user_id                   BIGINT UNSIGNED  NOT NULL,    -- PRIMARY KEY (no separate id)
    display_name              VARCHAR(100)     NOT NULL,
    bio                       TEXT             NULL,
    avatar_media_id           BIGINT UNSIGNED  NULL,
    cover_media_id            BIGINT UNSIGNED  NULL,
    verification_badge        BOOLEAN          NOT NULL DEFAULT FALSE,
    followers_count           BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Cached (atomic increments only)
    posts_count               BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Cached
    premium_subscribers_count BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Cached
    is_active                 BOOLEAN          NOT NULL DEFAULT TRUE,
    created_at                TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at                TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (user_id),
    FOREIGN KEY (user_id)         REFERENCES users(id)        ON DELETE CASCADE,
    FOREIGN KEY (avatar_media_id) REFERENCES media_assets(id) ON DELETE SET NULL,
    FOREIGN KEY (cover_media_id)  REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `user_id` IS the primary key. No separate auto-increment `id`.
- In Eloquent: `protected $primaryKey = 'user_id'; public $incrementing = false;`
- All other tables reference creators via `creator_id → creator_profiles(user_id)`.

---

### 10. `creator_categories`

```sql
CREATE TABLE IF NOT EXISTS creator_categories (
    creator_id  BIGINT UNSIGNED  NOT NULL,
    category_id BIGINT UNSIGNED  NOT NULL,

    PRIMARY KEY (creator_id, category_id),
    FOREIGN KEY (creator_id)  REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id)            ON DELETE CASCADE,
    INDEX idx_category_creators (category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 11. `follows`

```sql
CREATE TABLE IF NOT EXISTS follows (
    id          BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    follower_id BIGINT UNSIGNED  NOT NULL,
    creator_id  BIGINT UNSIGNED  NOT NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_follow (follower_id, creator_id),
    FOREIGN KEY (follower_id) REFERENCES users(id)                 ON DELETE CASCADE,
    FOREIGN KEY (creator_id)  REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    INDEX idx_creator_followers  (creator_id),
    INDEX idx_follower_following (follower_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 12. `payment_transactions`

```sql
CREATE TABLE IF NOT EXISTS payment_transactions (
    id                      BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id                 BIGINT UNSIGNED  NOT NULL,
    provider                ENUM('paymob', 'apple', 'google', 'stripe', 'other') NOT NULL,
    provider_transaction_id VARCHAR(255)     NULL,
    amount                  DECIMAL(12,2)    NOT NULL,     -- Real money (DECIMAL)
    currency                CHAR(3)          NOT NULL DEFAULT 'USD',
    coins                   BIGINT UNSIGNED  NOT NULL,     -- Coins credited to wallet
    status                  ENUM('pending', 'completed', 'failed', 'refunded') NOT NULL DEFAULT 'pending',
    raw_response            JSON             NULL,         -- Raw provider response for audit

    created_at              TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_provider_tx   (provider, provider_transaction_id),
    INDEX idx_user_payments (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Immutable. No `deleted_at`, no `updated_at`.
- `raw_response` stores the full provider webhook payload for audit and dispute resolution.
- `ON DELETE` not specified for user_id FK — defaults to RESTRICT (cannot delete user with payment history).

---

### 13. `wallet_transactions`

```sql
CREATE TABLE IF NOT EXISTS wallet_transactions (
    id               BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    wallet_id        BIGINT UNSIGNED  NOT NULL,
    user_id          BIGINT UNSIGNED  NOT NULL,
    amount           BIGINT UNSIGNED  NOT NULL,  -- Always positive. Direction from transaction_type.
    transaction_type ENUM(
        'recharge',           -- user bought coins (credit)
        'message',            -- paid text/image message sent (debit)
        'voice_message',      -- paid voice message sent (debit)
        'voice_call',         -- real-time voice call billing (debit)
        'video_call',         -- real-time video call billing (debit)
        'subscription',       -- monthly subscription purchase (debit)
        'gift',               -- gift sent to creator (debit)
        'refund',             -- coins returned (credit)
        'withdrawal',         -- creator withdrawal deduction (debit)
        'admin_adjustment'    -- manual admin credit/debit (credit)
    ) NOT NULL,
    status           ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'pending',
    reference_id     BIGINT UNSIGNED  NULL,      -- Polymorphic reference to the originating record
    reference_type   VARCHAR(50)      NULL,      -- e.g. 'paid_messages', 'call_sessions'
    description      TEXT             NULL,
    metadata         JSON             NULL,

    created_at       TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (user_id)   REFERENCES users(id),
    INDEX idx_user_tx (user_id, created_at),
    INDEX idx_ref_tx  (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Immutable ledger. No `updated_at`, no `deleted_at`.
- `amount` is BIGINT UNSIGNED — always positive. Credits increment balance; debits decrement it. Direction is determined by `transaction_type`.
- Balance formula: `SUM(credits) - SUM(debits)` where credit types are: `recharge`, `refund`, `admin_adjustment`.
- `voice_call` added to enum alongside `video_call` to support separate billing rates.

---

### 14. `posts`

```sql
CREATE TABLE IF NOT EXISTS posts (
    id             BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    creator_id     BIGINT UNSIGNED  NOT NULL,
    content_type   ENUM('image', 'video', 'text') NOT NULL,
    caption        TEXT             NULL,
    visibility     ENUM('free', 'premium', 'followers_only') NOT NULL DEFAULT 'free',
    likes_count    BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Cached (atomic increments only)
    comments_count BIGINT UNSIGNED  NOT NULL DEFAULT 0,  -- Cached (atomic increments only)
    is_active      BOOLEAN          NOT NULL DEFAULT TRUE,
    deleted_at     TIMESTAMP        NULL,
    created_at     TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    INDEX idx_creator_posts (creator_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Soft delete: preserves `likes`, `comments`, and `wallet_transactions` referencing this post.
- Media is stored in `media_assets` (polymorphic) — posts can have 0..N media items (carousel, single, none for text).
- `likes_count` / `comments_count` eliminate COUNT queries on the feed hot path.

---

### 15. `stories`

```sql
CREATE TABLE IF NOT EXISTS stories (
    id         BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    creator_id BIGINT UNSIGNED  NOT NULL,
    media_id   BIGINT UNSIGNED  NOT NULL,       -- Stories always have exactly one media item
    is_premium BOOLEAN          NOT NULL DEFAULT FALSE,
    expires_at TIMESTAMP        NOT NULL,       -- Set to created_at + 24 hours at insert time
    deleted_at TIMESTAMP        NULL,
    created_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (media_id)   REFERENCES media_assets(id)          ON DELETE RESTRICT,
    INDEX idx_creator_stories (creator_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `media_id` added (was missing in V4.0). Stories require exactly one media item.
- `ON DELETE RESTRICT` on `media_id`: cannot delete a media_asset referenced by a story.
- `expires_at` is set by application logic (now + 24h). The index supports efficient expiry queries.

---

### 16. `creator_services`

```sql
CREATE TABLE IF NOT EXISTS creator_services (
    id             BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    creator_id     BIGINT UNSIGNED  NOT NULL,
    service_type   ENUM(
        'message',        -- Paid text/image message
        'voice_message',  -- Paid voice message clip
        'voice_call',     -- Real-time voice call (price per minute)  ← ADDED
        'video_call',     -- Real-time video call (price per minute)
        'live_stream',    -- Future
        'meet_greet',     -- Future
        'group_call',     -- Future
        'ai_chat'         -- Future
    ) NOT NULL,
    price_in_coins BIGINT UNSIGNED  NOT NULL,
    is_enabled     BOOLEAN          NOT NULL DEFAULT TRUE,
    metadata       JSON             NULL,
    created_at     TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    UNIQUE KEY uk_service (creator_id, service_type),
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- UNIQUE on `(creator_id, service_type)` — one price per service type per creator.
- `voice_call` added to enum. Business rules define voice call and video call as separately priced services.
- `price_in_coins` for calls is "per minute" rate.

---

### 17. `subscription_plans`

```sql
CREATE TABLE IF NOT EXISTS subscription_plans (
    id            BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    creator_id    BIGINT UNSIGNED  NOT NULL,
    title         VARCHAR(100)     NOT NULL,
    description   TEXT             NULL,
    coins         BIGINT UNSIGNED  NOT NULL,
    duration_days INT UNSIGNED     NOT NULL,
    is_active     BOOLEAN          NOT NULL DEFAULT TRUE,
    created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    INDEX idx_creator_plans (creator_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 18. `subscriptions`

```sql
CREATE TABLE IF NOT EXISTS subscriptions (
    id            BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    plan_id       BIGINT UNSIGNED  NOT NULL,
    creator_id    BIGINT UNSIGNED  NOT NULL,  -- Denormalized from plan for performance
    subscriber_id BIGINT UNSIGNED  NOT NULL,
    started_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    expires_at    TIMESTAMP        NOT NULL,
    auto_renew    BOOLEAN          NOT NULL DEFAULT FALSE,
    status        ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (plan_id)       REFERENCES subscription_plans(id)    ON DELETE CASCADE,
    FOREIGN KEY (creator_id)    REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (subscriber_id) REFERENCES users(id)                 ON DELETE CASCADE,
    INDEX idx_subscription_check (creator_id, subscriber_id, status, expires_at),  -- HOT PATH
    INDEX idx_expiring           (status, expires_at)                              -- Scheduler
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `creator_id` denormalized from `subscription_plans` for O(1) subscription status check (no JOIN).
- Hot-path query: `WHERE creator_id = ? AND subscriber_id = ? AND status = 'active' AND expires_at > NOW()` uses the composite index directly.

---

### 19. `paid_messages`

```sql
CREATE TABLE IF NOT EXISTS paid_messages (
    id                    BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    sender_id             BIGINT UNSIGNED  NOT NULL,
    receiver_id           BIGINT UNSIGNED  NOT NULL,
    external_channel_id   VARCHAR(255)     NULL,    -- Provider-agnostic channel reference
    external_message_id   VARCHAR(255)     NULL,    -- Provider-assigned message ID for status sync
    message_type          ENUM('text', 'image', 'voice') NOT NULL,
    content               TEXT             NULL,
    media_asset_id        BIGINT UNSIGNED  NULL,
    price_in_coins        BIGINT UNSIGNED  NOT NULL,
    status                ENUM('sent', 'delivered', 'read', 'expired', 'refunded') NOT NULL DEFAULT 'sent',
    refund_eligible_until TIMESTAMP        NULL,

    created_at            TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (sender_id)      REFERENCES users(id)                 ON DELETE CASCADE,
    FOREIGN KEY (receiver_id)    REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (media_asset_id) REFERENCES media_assets(id)          ON DELETE SET NULL,
    INDEX idx_message_refund (refund_eligible_until, status),  -- Refund eligibility scheduler
    INDEX idx_sender_msgs    (sender_id, created_at),          -- User's sent message history
    INDEX idx_receiver_msgs  (receiver_id, created_at)         -- Creator's message inbox
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `stream_channel_id` renamed to `external_channel_id` (provider independence).
- `external_message_id` added: needed to update/reference the message in the chat provider SDK.
- Immutable financial record — no `updated_at`, no `deleted_at`.

---

### 20. `message_refunds`

```sql
CREATE TABLE IF NOT EXISTS message_refunds (
    id              BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    paid_message_id BIGINT UNSIGNED  NOT NULL UNIQUE,
    user_id         BIGINT UNSIGNED  NOT NULL,
    coins_returned  BIGINT UNSIGNED  NOT NULL,
    reason          ENUM('no_reply', 'manual', 'other') NOT NULL DEFAULT 'no_reply',
    processed_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (paid_message_id) REFERENCES paid_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)         REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Immutable. UNIQUE on `paid_message_id` — one refund record per message maximum.

---

### 21. `call_sessions`

```sql
CREATE TABLE IF NOT EXISTS call_sessions (
    id                  BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    provider            ENUM('stream', 'agora', 'other') NOT NULL DEFAULT 'stream',
    external_session_id VARCHAR(255)     NULL,    -- Provider-assigned session ID
    caller_id           BIGINT UNSIGNED  NOT NULL,
    callee_id           BIGINT UNSIGNED  NOT NULL,
    call_type           ENUM('voice', 'video') NOT NULL,    -- Determines billing rate lookup
    started_at          TIMESTAMP        NULL,
    ended_at            TIMESTAMP        NULL,
    duration_seconds    INT UNSIGNED     NOT NULL DEFAULT 0,
    rate_per_minute     BIGINT UNSIGNED  NOT NULL,          -- Snapshot of price at call time
    total_coins_charged BIGINT UNSIGNED  NOT NULL DEFAULT 0,
    status              ENUM('initiated', 'in_progress', 'completed', 'missed', 'rejected', 'refunded') NOT NULL DEFAULT 'initiated',
    created_at          TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (caller_id) REFERENCES users(id)                 ON DELETE CASCADE,
    FOREIGN KEY (callee_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    INDEX idx_caller_calls (caller_id, created_at),
    INDEX idx_callee_calls (callee_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `stream_session_id` renamed to `external_session_id` (provider independence).
- `call_type` added: 'voice' maps to `creator_services.service_type = 'voice_call'`, 'video' maps to 'video_call'. Required for correct billing rate lookup.
- `rate_per_minute` is a snapshot — captures the creator's price at the time of the call.

---

### 22. `likes`

```sql
CREATE TABLE IF NOT EXISTS likes (
    id            BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id       BIGINT UNSIGNED  NOT NULL,
    likeable_type VARCHAR(50)      NOT NULL,    -- Polymorphic morph type
    likeable_id   BIGINT UNSIGNED  NOT NULL,
    created_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    UNIQUE KEY uk_like (user_id, likeable_type, likeable_id),  -- Prevents double-likes at DB level
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_likeable (likeable_type, likeable_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Polymorphic: V1 supports liking `posts`. Future: comments, stories.
- UNIQUE at DB level prevents race-condition double-likes.
- When a like is inserted/deleted, LikeService must call `DB::increment/decrement('likes_count')` on the parent.

---

### 23. `comments`

```sql
CREATE TABLE IF NOT EXISTS comments (
    id         BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id    BIGINT UNSIGNED  NOT NULL,
    post_id    BIGINT UNSIGNED  NOT NULL,
    body       TEXT             NOT NULL,
    deleted_at TIMESTAMP        NULL,
    created_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id)  ON DELETE CASCADE,
    FOREIGN KEY (post_id) REFERENCES posts(id)  ON DELETE CASCADE,
    INDEX idx_post_comments (post_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- Soft delete: "Deleted comments are hidden" per business rules. Record is preserved.
- CommentService must `DB::decrement('comments_count')` on the parent post when soft-deleting.

---

### 24. `withdrawals`

```sql
CREATE TABLE IF NOT EXISTS withdrawals (
    id           BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    creator_id   BIGINT UNSIGNED  NOT NULL,
    amount_coins BIGINT UNSIGNED  NOT NULL,
    status       ENUM('pending', 'approved', 'rejected', 'paid') NOT NULL DEFAULT 'pending',
    admin_note   TEXT             NULL,
    processed_by BIGINT UNSIGNED  NULL,
    processed_at TIMESTAMP        NULL,
    created_at   TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (creator_id)   REFERENCES users(id) ON DELETE RESTRICT,  -- Cannot delete user with pending withdrawal
    FOREIGN KEY (processed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_creator_withdrawals (creator_id, status),
    INDEX idx_pending_withdrawals (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- `ON DELETE RESTRICT` on creator_id: prevents accidental user deletion while a withdrawal is pending/approved.
- `paid` status is immutable per business rules — once paid, status cannot be changed.

---

### 25. `notifications`

```sql
CREATE TABLE IF NOT EXISTS notifications (
    id          BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED  NOT NULL,
    type        VARCHAR(100)     NOT NULL,    -- NotificationType enum value
    title       VARCHAR(150)     NOT NULL,
    body        TEXT             NULL,
    entity_type VARCHAR(50)      NULL,        -- Polymorphic: what triggered this notification
    entity_id   BIGINT UNSIGNED  NULL,
    read_at     TIMESTAMP        NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, read_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 26. `reports`

```sql
CREATE TABLE IF NOT EXISTS reports (
    id              BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    reporter_id     BIGINT UNSIGNED  NOT NULL,
    reportable_type VARCHAR(50)      NOT NULL,
    reportable_id   BIGINT UNSIGNED  NOT NULL,
    reason          TEXT             NULL,
    status          ENUM('pending', 'reviewed', 'resolved') NOT NULL DEFAULT 'pending',
    created_at      TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (reporter_id) REFERENCES users(id),
    INDEX idx_reportable    (reportable_type, reportable_id),
    INDEX idx_report_status (status, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 27. `audit_logs`

```sql
CREATE TABLE IF NOT EXISTS audit_logs (
    id          BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED  NULL,     -- NULL for system-generated events
    action      VARCHAR(100)     NOT NULL,
    entity_type VARCHAR(50)      NOT NULL,
    entity_id   BIGINT UNSIGNED  NULL,
    old_values  JSON             NULL,
    new_values  JSON             NULL,
    ip_address  VARCHAR(45)      NULL,     -- IPv4 or IPv6
    user_agent  TEXT             NULL,
    created_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_audit      (entity_type, entity_id),
    INDEX idx_user_audit (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notes:**
- No FK on `user_id` — allows system-initiated audit events where no user is the actor.
- Immutable. No `updated_at`, no `deleted_at`.

---

### 28. `feature_flags`

```sql
CREATE TABLE IF NOT EXISTS feature_flags (
    id          BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100)     NOT NULL UNIQUE,
    is_enabled  BOOLEAN          NOT NULL DEFAULT FALSE,
    description TEXT             NULL,
    updated_at  TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

### 29. `system_settings`

```sql
CREATE TABLE IF NOT EXISTS system_settings (
    id            BIGINT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    setting_key   VARCHAR(100)     NOT NULL UNIQUE,
    setting_value JSON             NOT NULL,
    updated_at    TIMESTAMP        DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Final Statistics

| Metric | Count |
|---|---|
| Tables | 29 |
| New tables (vs V4.0) | 5 (`follows`, `device_tokens`, `likes`, `comments`, `withdrawals`) |
| Foreign keys | 44 |
| Unique constraints | 12 |
| Regular indexes | 28 |
| Enums | 18 |

---

## V2 Recommendations (Not Implemented)

- **Partitioning**: `wallet_transactions` and `notifications` by `created_at` range when rows exceed 100M.
- **Read replicas**: `posts`, `notifications` reads can be offloaded to read replicas.
- **`blocked_users` table**: User-to-user blocking feature (out of V1 scope).
- **`gifts` table**: A gift catalog with specific items (emoji, virtual gifts) with individual prices.
- **Archival**: Move records older than 2 years from `audit_logs` and `notifications` to cold storage.
- **`creator_services.call_provider` column**: When multiple call providers exist, store which provider handles which call type per creator.

---

*Schema V1.0 — Finalized 2026-06-27 — Do not modify without updating migrations and PROJECT_STATUS.md*