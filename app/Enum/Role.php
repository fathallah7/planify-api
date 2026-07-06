<?php

namespace App\Enum;

enum Role: string
{
    case OWNER = 'owner';
    case ADMIN = 'admin';
    case USER = 'user';
}
