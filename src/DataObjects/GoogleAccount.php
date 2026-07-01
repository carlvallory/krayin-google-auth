<?php

namespace CarlVallory\KrayinGoogleAuth\DataObjects;

class GoogleAccount
{
    public function __construct(
        public readonly string $email,
        public readonly string $googleId,
        public readonly string $name,
        public readonly ?string $avatar = null,
        public readonly ?string $hostedDomain = null,
    ) {}
}
