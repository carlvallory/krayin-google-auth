<?php

namespace CarlVallory\KrayinGoogleAuth\DataObjects;

use Webkul\User\Models\User;

class ResolutionResult
{
    public function __construct(
        public readonly User $user,
        public readonly bool $allowed,
        public readonly ?string $reason = null,
    ) {}
}
