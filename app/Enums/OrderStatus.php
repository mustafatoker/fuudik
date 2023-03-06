<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PENDING = 1;
    case CANCELLED = 2;
    case COMPLETED = 3;
}
