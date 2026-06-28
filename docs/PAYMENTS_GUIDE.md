# CeleMeet Payment Architecture Guide

This document explains the exact strategy we are using for Phase 5 (Payment & Coin Purchase). It is written in simple terms to act as a permanent reference for how real money enters the CeleMeet platform.

---

## 1. The Golden Rule of App Stores (Why we need two systems)

Apple strictly enforces a rule: **If you are selling digital goods that are consumed inside the app (like CeleMeet Coins), you MUST use Apple's native In-App Purchase system on iOS devices.** 
If we put a Paymob credit card form inside the iOS app to buy coins, Apple will reject the app from the App Store. Apple takes a 30% cut of these purchases.

Because of this, we must build a **dual-payment system**:
- **iOS App:** Must use Apple In-App Purchases (IAP).
- **Android App & Website:** Will use Paymob.

---

## 2. How the systems work (In Simple Terms)

### System A: Paymob (For Web & Android)
1. **Initiation:** The user clicks "Buy 100 Coins". The frontend calls our backend.
2. **Link Generation:** Our backend asks Paymob for a secure payment link and returns it to the frontend.
3. **Payment:** The user types their credit card into Paymob's screen.
4. **Webhook (The Secret Message):** After payment, Paymob silently sends a message directly to our backend saying "Payment ID #123 was successful".
5. **Reward:** Our backend verifies the message using a secret key, finds the user, and adds 100 Coins to their Wallet.

### System B: Apple In-App Purchase (For iOS)
1. **Payment:** The user clicks "Buy 100 Coins". The iOS app shows the native Apple Pay popup. The user double-clicks the side button.
2. **The Receipt:** Apple charges the user and gives the mobile app a "Receipt" (a long string of letters/numbers proving they paid).
3. **Verification:** The mobile app sends this receipt to our backend.
4. **Validation:** Our backend directly contacts Apple's servers and asks, "Is this receipt real, and what did they buy?"
5. **Reward:** Apple says "Yes, they bought 100 coins". Our backend adds 100 Coins to the user's Wallet.

---

## 3. What I will need from you (When you go to production)

You do not need to provide these *right now* while I am writing the code. I will build the system so that you just paste these into the `.env` file later when you are ready to test with real money.

### What you need to get from Paymob:
1. **`PAYMOB_API_KEY`**: Your main account secret key.
2. **`PAYMOB_HMAC_SECRET`**: A special password Paymob gives you. We use this to mathematically prove that the "Payment Successful" messages are actually from Paymob and not a hacker.
3. **`PAYMOB_INTEGRATION_ID`**: Paymob gives you a specific ID for the payment method you are using (e.g., an ID for Card Payments, a different ID for Mobile Wallets).

### What you need to get from Apple (App Store Connect):
1. **`APPLE_BUNDLE_ID`**: The unique identifier of your iOS app (e.g., `com.youssef.celemeet`).
2. **`APPLE_SHARED_SECRET`**: A password you generate in your Apple Developer account that allows our backend to talk to Apple's verification servers.
3. **Product IDs**: When you create your Coin Packages in the Apple Developer portal, you have to give them IDs (e.g., `coins_100_package`). We will need to save these specific IDs in our database so we know which package maps to which Apple product.

---

## 4. What we are building in Phase 5

Now that the strategy is clear, here is exactly what I will code in this phase:

1. **`payment_transactions` Database Table:** A table to record every real-money transaction, keeping it separate from the `wallet_transactions` table (which only tracks virtual coins).
2. **`PaymobAdapter`:** The code that talks to Paymob to generate links and verify webhooks.
3. **`AppleIapAdapter`:** The code that talks to Apple to verify iOS receipts.
4. **API Endpoints:**
   - `POST /api/v1/payments/paymob/initiate`
   - `POST /api/v1/payments/paymob/webhook`
   - `POST /api/v1/payments/apple/verify`
5. **The Glue:** The logic that says: *If the payment is verified (by either Apple or Paymob) -> Call the WalletService -> Add coins to the user's wallet.*
