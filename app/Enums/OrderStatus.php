<?php

namespace App\Enums;

enum OrderStatus: string
{
    case PendingPayment = 'pending_payment';
    case Transporting = 'transporting';
    case Completed = 'completed';
    case Canceled = 'canceled';
}
