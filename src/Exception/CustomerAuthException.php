<?php

namespace Webkul\BagistoApi\Exception;

class CustomerAuthException extends ApiAuthException
{
    /**
     * The machine-readable code of a login by a customer who has not verified their email address.
     */
    public const CODE_ACCOUNT_UNVERIFIED = 'account_unverified';
}
