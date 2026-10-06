<?php

namespace App\Enums;

enum AuditAction: string
{
    case View = 'view';
    case Create = 'create';
    case Update = 'update';
    case Delete = 'delete';
}
