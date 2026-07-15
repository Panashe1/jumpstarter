<?php

namespace App\Enums;

enum OrganizationRole: string
{
    case Owner = 'owner';
    case Admin = 'admin';
    case Member = 'member';

    public function isAdmin(): bool
    {
        return $this === self::Owner || $this === self::Admin;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
