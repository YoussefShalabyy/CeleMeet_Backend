# BUSINESS RULES
Version 1.0

This document defines the business behavior of the application.

It is independent from any programming language, framework, database, or third-party provider.

These rules are the source of truth for all business logic.

---

# General Rules

- Coins are the only in-app currency.
- No feature may charge users directly using real money.
- All premium features consume Coins.
- Every Coin movement must be traceable.
- Business rules must remain independent from external providers.

---

# Users

A user can:

- Create an account.
- Follow creators.
- Purchase Coin packages.
- Subscribe to creators.
- Send paid messages.
- Send paid voice messages.
- Start paid voice/video calls.
- View premium content if subscribed.

---

# Creators

A creator can:

- Publish free content.
- Publish premium content.
- Configure service prices.
- Reply to messages.
- Receive calls.
- View earnings.
- Request withdrawals.

---

# Wallet

Every user owns exactly one wallet.

Wallet balance can never become negative.

Coins are deducted only after successful validation.

Every wallet operation must create a Wallet Transaction.

Wallet balance must always equal the sum of all completed transactions.

---

# Coin Packages

Users purchase predefined Coin packages.

Packages may contain bonus Coins.

Package prices are managed by the Admin.

Coins never expire.

---

# Paid Messages

Before sending a message:

1. Verify sender balance.
2. Verify creator pricing.
3. Deduct Coins.
4. Create Wallet Transaction.
5. Deliver message.

If any step fails:

Rollback the entire operation.

---

# Voice Messages

Voice messages follow the same payment flow as text messages.

Each creator defines its own price.

---

# Voice & Video Calls

Before creating a call:

- Verify sufficient balance.
- Verify creator availability.

During the call:

- Duration is measured.

After the call:

- Coins are deducted according to the creator's configured price.

If balance becomes insufficient during the call:

The call must end gracefully.

---

# Creator Pricing

Each creator defines:

- Text message price
- Voice message price
- Voice call price per minute
- Video call price per minute
- Monthly subscription price

The platform does not enforce identical pricing.

---

# Premium Content

Premium content is visible only to active subscribers.

Premium access ends immediately after subscription expiration.

---

# Subscriptions

Subscriptions begin immediately after payment.

No Auto Renewal in Version 1.

Users must renew manually.

Expired subscriptions immediately lose premium access.

---

# Stories

Stories remain visible for 24 hours.

Expired stories are automatically hidden.

Premium stories require an active subscription.

---

# Posts

Posts may be:

- Free
- Premium

Creators may edit or delete their own posts.

Deleted posts become unavailable immediately.

---

# Follow System

Users may follow or unfollow creators.

Following affects:

- Feed
- Recommendations
- Notifications

---

# Comments

Users may comment only on visible posts.

Blocked users cannot comment.

Deleted comments are hidden.

---

# Likes

Each user may like a post only once.

Users may remove their like.

Like count is calculated automatically.

---

# Notifications

Notifications may be:

- Push
- In-App

Notification delivery failures must never affect business operations.

---

# Earnings

Creator earnings are calculated from:

- Paid Messages
- Voice Messages
- Calls
- Subscriptions
- Gifts (Future)

Platform commission is deducted automatically.

---

# Withdrawals

Creators may request withdrawals.

Withdrawals require Admin approval.

Rejected withdrawals never deduct earnings.

Paid withdrawals become immutable.

---

# Reports

Users may report:

- Posts
- Stories
- Comments
- Creators

Reports are reviewed only by Admins.

---

# Admin

Admins may:

- Manage users
- Manage creators
- Moderate content
- Approve withdrawals
- Manage Coin packages
- Manage platform settings

Admins cannot modify wallet balances manually.

---

# Refund Rules

Refunds are allowed only when a paid operation fails.

Refunds always create a Wallet Transaction.

Refunds restore Coins to the user's wallet.

---

# Future Features

The following features are planned but not implemented:

- Gifts
- Live Streaming
- Marketplace
- AI Assistant
- Referral System
- Promo Codes
- Coupons
- Events
- Meet & Greet

Business rules must remain compatible with future expansion.

---

# Rule Priority

If implementation conflicts with this document,

this document takes precedence.

Any modification to these rules must be documented before implementation.