<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentProvider: string
{
    case Paymob = 'paymob';
    case Apple = 'apple';
    case Google = 'google';
    case Stripe = 'stripe';
    case Other = 'other';
}
