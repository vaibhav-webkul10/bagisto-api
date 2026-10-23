<?php

namespace Webkul\BagistoApi\Services\CustomerAuth;

use Carbon\CarbonInterface;
use Webkul\BagistoApi\Models\CustomerApiSession;

class CustomerApiTokenPair
{
    /**
     * Create a token pair issued for a customer session.
     */
    public function __construct(
        public readonly string $accessToken,
        public readonly CarbonInterface $accessTokenExpiresAt,
        public readonly string $refreshToken,
        public readonly int $refreshTokenId,
        public readonly CarbonInterface $refreshTokenExpiresAt,
        public readonly CustomerApiSession $session,
        public readonly CarbonInterface $issuedAt,
    ) {}

    /**
     * Seconds until the access token expires.
     */
    public function expiresIn(): int
    {
        return max(0, $this->accessTokenExpiresAt->getTimestamp() - $this->issuedAt->getTimestamp());
    }
}
