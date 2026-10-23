<?php

namespace Webkul\BagistoApi\Services\CustomerAuth;

use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Webkul\BagistoApi\Auth\CustomerApiAuthenticator;
use Webkul\BagistoApi\Exception\CustomerAuthException;
use Webkul\BagistoApi\Models\CustomerApiSession;
use Webkul\BagistoApi\Repositories\CustomerApiRefreshTokenRepository;
use Webkul\Customer\Models\Customer;
use Webkul\Customer\Repositories\CustomerRepository;

class CustomerApiAuthService
{
    /**
     * Create a new customer authentication service.
     */
    public function __construct(
        protected CustomerApiTokenService $tokenService,
        protected CustomerApiAuthSettings $settings,
        protected CustomerRepository $customerRepository,
        protected CustomerApiRefreshTokenRepository $refreshTokenRepository,
        protected RateLimiter $limiter,
    ) {}

    /**
     * Check a customer's email and password on the current channel, as core's storefront login does, and start a session.
     *
     * @param  array{name?: ?string, client_type?: ?string}  $client
     *
     * @throws CustomerAuthException
     */
    public function login(string $email, string $password, Request $request, array $client = []): CustomerApiTokenPair
    {
        $throttleKey = 'bagistoapi-customer-login:'.Str::lower($email).'|'.$request->ip();

        if ($this->limiter->tooManyAttempts($throttleKey, $this->settings->loginMaxAttempts())) {
            $this->rejectThrottled('too-many-login-attempts', CustomerAuthException::CODE_TOO_MANY_LOGIN_ATTEMPTS, $throttleKey);
        }

        $customer = $this->customerRepository->findOneWhere([
            'email' => $email,
            'channel_id' => core()->getCurrentChannel()->id,
        ]);

        if (
            ! $customer
            || ! Hash::check($password, (string) $customer->password)
        ) {
            $this->limiter->hit($throttleKey, $this->settings->loginLockoutSeconds());

            throw new CustomerAuthException(
                trans('bagistoapi::app.graphql.login.invalid-credentials'),
                CustomerAuthException::CODE_INVALID_CREDENTIALS,
            );
        }

        $this->limiter->clear($throttleKey);

        $this->assertCanLogIn($customer);

        $pair = $this->startSession($customer, $request, $client);

        Event::dispatch('customer.after.login', $customer);

        return $pair;
    }

    /**
     * Start a session for a customer who proved who they are another way, such as registering or a social login.
     *
     * @param  array{name?: ?string, client_type?: ?string}  $client
     */
    public function startSession(Customer $customer, Request $request, array $client = []): CustomerApiTokenPair
    {
        return $this->tokenService->issue($customer, (int) core()->getCurrentChannel()->id, [
            'name' => $client['name'] ?? null,
            'client_type' => $client['client_type'] ?? null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'channel' => CustomerApiAuthenticator::channel($request),
        ]);
    }

    /**
     * Exchange a refresh token for a new pair, limited per session.
     *
     * @throws CustomerAuthException
     */
    public function refresh(string $refreshToken, Request $request): CustomerApiTokenPair
    {
        $throttleKey = 'bagistoapi-customer-refresh:'.$this->refreshThrottleScope($refreshToken, $request);

        if ($this->limiter->tooManyAttempts($throttleKey, $this->settings->refreshRequestsPerMinute())) {
            $this->rejectThrottled('too-many-refresh-requests', CustomerAuthException::CODE_TOO_MANY_REFRESH_REQUESTS, $throttleKey);
        }

        $this->limiter->hit($throttleKey, 60);

        return $this->tokenService->rotate($refreshToken, [
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'channel' => CustomerApiAuthenticator::channel($request),
        ]);
    }

    /**
     * End the session the request authenticated with.
     */
    public function logout(CustomerApiSession $session): CustomerApiSession
    {
        return $this->tokenService->revoke($session, CustomerApiSession::REVOKED_LOGOUT);
    }

    /**
     * End every session of a customer and return how many were ended.
     */
    public function logoutAll(Customer $customer): int
    {
        return $this->tokenService->revokeAllForCustomer((int) $customer->id, CustomerApiSession::REVOKED_LOGOUT_ALL);
    }

    /**
     * End one of the customer's own sessions.
     */
    public function revokeSession(CustomerApiSession $session): CustomerApiSession
    {
        return $this->tokenService->revoke($session, CustomerApiSession::REVOKED_BY_CUSTOMER);
    }

    /**
     * End the customer's other sessions after a password change, when the store is set to.
     */
    public function passwordChanged(Customer $customer, ?CustomerApiSession $current): int
    {
        if (! $this->settings->revokeSessionsOnPasswordChange()) {
            return 0;
        }

        return $this->tokenService->revokeAllForCustomer(
            (int) $customer->id,
            CustomerApiSession::REVOKED_PASSWORD_CHANGE,
            $current?->id,
        );
    }

    /**
     * End every session of a customer whose password was reset.
     */
    public function passwordReset(Customer $customer): int
    {
        return $this->tokenService->revokeAllForCustomer((int) $customer->id, CustomerApiSession::REVOKED_PASSWORD_RESET);
    }

    /**
     * Refuse a customer core's storefront would not log in: inactive, or not yet verified.
     *
     * @throws CustomerAuthException
     */
    protected function assertCanLogIn(Customer $customer): void
    {
        if (! $customer->status) {
            throw new CustomerAuthException(
                trans('bagistoapi::app.graphql.login.account-inactive'),
                CustomerAuthException::CODE_ACCOUNT_INACTIVE,
                403,
            );
        }

        if (! $customer->is_verified) {
            throw new CustomerAuthException(
                trans('bagistoapi::app.graphql.login.email-not-verified'),
                CustomerAuthException::CODE_ACCOUNT_UNVERIFIED,
                403,
            );
        }
    }

    /**
     * The session a refresh token belongs to, or the client address when the token names none.
     */
    protected function refreshThrottleScope(string $refreshToken, Request $request): string
    {
        $id = explode('|', $refreshToken, 2)[0];

        $sessionId = ctype_digit($id)
            ? $this->refreshTokenRepository->find((int) $id)?->session_id
            : null;

        return $sessionId !== null
            ? 'session:'.$sessionId
            : 'address:'.$request->ip();
    }

    /**
     * Refuse a throttled request with the seconds to wait.
     *
     * @throws CustomerAuthException
     */
    protected function rejectThrottled(string $message, string $code, string $throttleKey): never
    {
        $seconds = max(1, $this->limiter->availableIn($throttleKey));

        throw new CustomerAuthException(
            trans('bagistoapi::app.graphql.auth.'.$message, ['seconds' => $seconds]),
            $code,
            429,
            ['Retry-After' => (string) $seconds],
        );
    }
}
