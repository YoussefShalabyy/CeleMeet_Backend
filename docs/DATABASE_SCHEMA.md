-- =============================================
-- Celebrity Connect Platform - Database Schema V4.0
-- MySQL 8.0+ | Production Ready
-- =============================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================
-- 1. Authentication & Users
-- =============================================

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    uuid CHAR(36) NOT NULL UNIQUE,
    username VARCHAR(50) UNIQUE,
    email VARCHAR(150) UNIQUE,
    phone VARCHAR(20) UNIQUE NULL,
    password_hash VARCHAR(255) NULL,
    email_verified_at TIMESTAMP NULL,
    phone_verified_at TIMESTAMP NULL,
    role ENUM('regular', 'celebrity', 'admin', 'moderator') NOT NULL DEFAULT 'regular',
    is_banned BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    last_active_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    INDEX idx_email (email),
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS social_accounts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('google', 'apple') NOT NULL,
    provider_id VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_social (provider, provider_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS refresh_tokens (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    device_id VARCHAR(255) NULL,
    token VARCHAR(512) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 2. Profiles
-- =============================================

CREATE TABLE IF NOT EXISTS creator_profiles (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    display_name VARCHAR(100) NOT NULL,
    bio TEXT,
    avatar_media_id BIGINT UNSIGNED NULL,
    cover_media_id BIGINT UNSIGNED NULL,
    verification_badge BOOLEAN DEFAULT FALSE,
    followers_count BIGINT UNSIGNED DEFAULT 0,           -- Cached
    posts_count BIGINT UNSIGNED DEFAULT 0,               -- Cached
    premium_subscribers_count BIGINT UNSIGNED DEFAULT 0, -- Cached
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (avatar_media_id) REFERENCES media_assets(id) ON DELETE SET NULL,
    FOREIGN KEY (cover_media_id) REFERENCES media_assets(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS creator_categories (
    creator_id BIGINT UNSIGNED NOT NULL,
    category_id BIGINT UNSIGNED NOT NULL,
    PRIMARY KEY (creator_id, category_id),
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 3. Media Assets (Polymorphic)
-- =============================================

CREATE TABLE IF NOT EXISTS media_assets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    owner_id BIGINT UNSIGNED NOT NULL,
    owner_type VARCHAR(50) NOT NULL,
    collection VARCHAR(50) NOT NULL,
    provider VARCHAR(50) NOT NULL DEFAULT 'cloudinary',
    provider_id VARCHAR(255) NULL,
    url VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NULL,
    size BIGINT UNSIGNED NULL,
    width INT UNSIGNED NULL,
    height INT UNSIGNED NULL,
    duration INT UNSIGNED NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_owner (owner_type, owner_id, collection)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 4. Wallet & Coins
-- =============================================

CREATE TABLE IF NOT EXISTS wallets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL UNIQUE,
    available_balance BIGINT UNSIGNED NOT NULL DEFAULT 0,
    held_balance BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_earned BIGINT UNSIGNED NOT NULL DEFAULT 0,
    total_spent BIGINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS coin_packages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    coins BIGINT UNSIGNED NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    bonus_percentage DECIMAL(5,2) DEFAULT 0.00,
    bonus_coins BIGINT UNSIGNED DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    sort_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS wallet_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    wallet_id BIGINT UNSIGNED NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    amount BIGINT NOT NULL,
    transaction_type ENUM('recharge', 'message', 'voice_message', 'video_call', 
                         'subscription', 'gift', 'refund', 'withdrawal', 'admin_adjustment') NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'reversed') DEFAULT 'pending',
    reference_id BIGINT UNSIGNED NULL,
    reference_type VARCHAR(50) NULL,
    description TEXT NULL,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (wallet_id) REFERENCES wallets(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_user_tx (user_id, created_at),
    INDEX idx_ref_tx (reference_type, reference_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS payment_transactions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    provider ENUM('paymob', 'apple', 'google', 'stripe', 'other') NOT NULL,
    provider_transaction_id VARCHAR(255) NULL,
    amount DECIMAL(12,2) NOT NULL,
    currency CHAR(3) NOT NULL DEFAULT 'USD',
    coins BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    raw_response JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_provider_tx (provider, provider_transaction_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 5. Content
-- =============================================

CREATE TABLE IF NOT EXISTS posts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    creator_id BIGINT UNSIGNED NOT NULL,
    content_type ENUM('image', 'video', 'text') NOT NULL,
    caption TEXT,
    visibility ENUM('free', 'premium', 'followers_only') DEFAULT 'free',
    is_active BOOLEAN DEFAULT TRUE,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    INDEX idx_creator_posts (creator_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS stories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    creator_id BIGINT UNSIGNED NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    expires_at TIMESTAMP NOT NULL,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 6. Creator Services
-- =============================================

CREATE TABLE IF NOT EXISTS creator_services (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    creator_id BIGINT UNSIGNED NOT NULL,
    service_type ENUM('message', 'voice_message', 'video_call', 'live_stream', 
                     'meet_greet', 'group_call', 'ai_chat') NOT NULL,
    price_in_coins BIGINT UNSIGNED NOT NULL,
    is_enabled BOOLEAN DEFAULT TRUE,
    metadata JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_service (creator_id, service_type),
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 7. Subscriptions
-- =============================================

CREATE TABLE IF NOT EXISTS subscription_plans (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    creator_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NULL,
    coins BIGINT UNSIGNED NOT NULL,
    duration_days INT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (creator_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    plan_id BIGINT UNSIGNED NOT NULL,
    subscriber_id BIGINT UNSIGNED NOT NULL,
    started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    auto_renew BOOLEAN DEFAULT FALSE,
    status ENUM('active', 'expired', 'cancelled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
    FOREIGN KEY (subscriber_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 8. Messaging & Calls
-- =============================================

CREATE TABLE IF NOT EXISTS paid_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id BIGINT UNSIGNED NOT NULL,
    receiver_id BIGINT UNSIGNED NOT NULL,
    stream_channel_id VARCHAR(255),
    message_type ENUM('text', 'image', 'voice') NOT NULL,
    content TEXT,
    media_asset_id BIGINT UNSIGNED NULL,
    price_in_coins BIGINT UNSIGNED NOT NULL,
    status ENUM('sent', 'delivered', 'read', 'expired', 'refunded') DEFAULT 'sent',
    refund_eligible_until TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE,
    FOREIGN KEY (media_asset_id) REFERENCES media_assets(id) ON DELETE SET NULL,
    INDEX idx_message_refund (refund_eligible_until, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS message_refunds (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    paid_message_id BIGINT UNSIGNED NOT NULL UNIQUE,
    user_id BIGINT UNSIGNED NOT NULL,
    coins_returned BIGINT UNSIGNED NOT NULL,
    reason ENUM('no_reply', 'manual', 'other') DEFAULT 'no_reply',
    processed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (paid_message_id) REFERENCES paid_messages(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS call_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    provider ENUM('stream', 'agora', 'other') NOT NULL DEFAULT 'stream',
    stream_session_id VARCHAR(255),
    caller_id BIGINT UNSIGNED NOT NULL,
    callee_id BIGINT UNSIGNED NOT NULL,
    started_at TIMESTAMP NULL,
    ended_at TIMESTAMP NULL,
    duration_seconds INT UNSIGNED DEFAULT 0,
    rate_per_minute BIGINT UNSIGNED NOT NULL,
    total_coins_charged BIGINT UNSIGNED DEFAULT 0,
    status ENUM('initiated', 'in_progress', 'completed', 'missed', 'rejected', 'refunded') DEFAULT 'initiated',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (callee_id) REFERENCES creator_profiles(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 9. Notifications
-- =============================================

CREATE TABLE IF NOT EXISTS notifications (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    type VARCHAR(100) NOT NULL,
    title VARCHAR(150) NOT NULL,
    body TEXT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id BIGINT UNSIGNED NULL,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_notifications (user_id, read_at, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================
-- 10. Reports, Audit & Admin
-- =============================================

CREATE TABLE IF NOT EXISTS reports (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id BIGINT UNSIGNED NOT NULL,
    reportable_type VARCHAR(50) NOT NULL,
    reportable_id BIGINT UNSIGNED NOT NULL,
    reason TEXT,
    status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reporter_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id BIGINT UNSIGNED NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit (entity_type, entity_id),
    INDEX idx_user_audit (user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS feature_flags (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    is_enabled BOOLEAN DEFAULT FALSE,
    description TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS system_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value JSON NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================
-- Schema V4.0 Complete
-- =============================================