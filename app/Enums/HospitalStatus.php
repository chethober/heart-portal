<?php

namespace App\Enums;

enum HospitalStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Suspended = 'suspended';
}
