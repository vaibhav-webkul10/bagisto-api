<?php

namespace Webkul\BagistoApi\Observers;

use Webkul\BagistoApi\Models\CustomerApiSession;
use Webkul\BagistoApi\Services\CustomerAuth\CustomerApiAuthService;
use Webkul\BagistoApi\Services\CustomerAuth\CustomerApiTokenService;
use Webkul\BagistoApi\Support\CurrentCustomer;
use Webkul\Customer\Models\Customer;

class CustomerCredentialObserver
{
    /**
     * Create a new customer credential observer.
     */
    public function __construct(
        protected CustomerApiAuthService $authService,
        protected CustomerApiTokenService $tokenService,
    ) {}

    /**
     * End API sessions when a customer's password changes or the customer is deactivated, from any screen.
     */
    public function updated(Customer $customer): void
    {
        if ($customer->wasChanged('password')) {
            $current = CurrentCustomer::user()?->id === $customer->id
                ? CurrentCustomer::session()
                : null;

            $this->authService->passwordChanged($customer, $current);
        }

        if (
            $customer->wasChanged('status')
            && ! $customer->status
        ) {
            $this->tokenService->revokeAllForCustomer((int) $customer->id, CustomerApiSession::REVOKED_DEACTIVATED);
        }
    }
}
