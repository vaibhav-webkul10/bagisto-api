<?php

namespace Webkul\BagistoApi\Tests\Feature\GraphQL;

use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use Webkul\BagistoApi\Admin\Services\Jwt\JwtSigner;
use Webkul\BagistoApi\Models\CustomerApiSession;
use Webkul\BagistoApi\Tests\GraphQLTestCase;
use Webkul\Customer\Models\Customer;

class CustomerSessionAuthTest extends GraphQLTestCase
{
    /**
     * The customer's password in every test.
     */
    protected const PASSWORD = 'Password123!';

    /**
     * The fields every token pair answer is read with.
     */
    protected const PAIR_FIELDS = 'tokenType accessToken expiresIn accessTokenExpiresAt refreshToken refreshTokenExpiresAt session customer';

    // ========================================================================
    // Login
    // ========================================================================

    /**
     * Test createCustomerLogin returns a token pair whose access token authenticates.
     */
    public function test_login_returns_a_token_pair(): void
    {
        $customer = $this->customerWithPassword();

        $response = $this->login($customer->email, ['deviceName' => 'Web', 'clientType' => 'web']);

        $response->assertOk();
        $this->assertNull($response->json('errors'));

        $login = $response->json('data.createCustomerLogin.customerLogin');

        $this->assertSame('Bearer', $login['tokenType']);
        $this->assertSame(900, $login['expiresIn']);
        $this->assertSame($login['accessToken'], $login['token']);
        $this->assertSame('web', $login['session']['clientType']);
        $this->assertSame($customer->id, $login['customer']['id']);
        $this->assertNotEmpty($login['apiToken']);

        $this->assertSame($customer->email, $this->profileEmail($login['accessToken']));
    }

    /**
     * Test refused logins carry their code in the error extensions.
     */
    public function test_refused_logins_carry_their_code(): void
    {
        $customer = $this->customerWithPassword();
        $inactive = $this->customerWithPassword(['status' => 0]);
        $unverified = $this->customerWithPassword(['is_verified' => 0]);

        $this->assertSame('invalid_credentials', $this->login($customer->email, [], 'Wrong-password-1')->json('errors.0.extensions.code'));
        $this->assertSame('account_inactive', $this->login($inactive->email)->json('errors.0.extensions.code'));
        $this->assertSame('account_unverified', $this->login($unverified->email)->json('errors.0.extensions.code'));
    }

    // ========================================================================
    // Refresh And Sessions
    // ========================================================================

    /**
     * Test createCustomerTokenRefresh rotates the pair and a reused refresh token is refused.
     */
    public function test_refresh_rotates_the_pair(): void
    {
        $pair = $this->customerTokenPair($this->createCustomer());

        $response = $this->refresh($pair->refreshToken);

        $this->assertNull($response->json('errors'));

        $refreshed = $response->json('data.createCustomerTokenRefresh.customerTokenRefresh');

        $this->assertSame($pair->session->id, $refreshed['session']['id']);
        $this->assertNotSame($pair->refreshToken, $refreshed['refreshToken']);

        $this->assertSame('refresh_token_invalid', $this->refresh($pair->refreshToken)->json('errors.0.extensions.code'));
    }

    /**
     * Test customerSessions lists the live sessions and deleteCustomerSession ends one.
     */
    public function test_sessions_are_listed_and_revoked(): void
    {
        $customer = $this->createCustomer();

        $current = $this->customerTokenPair($customer);
        $other = $this->customerTokenPair($customer);

        $list = $this->graphQL(
            '{ customerSessions { _id name clientType lastChannel expiresAt isCurrent } }',
            [],
            $this->bearer($current->accessToken),
        );

        $this->assertNull($list->json('errors'));

        $sessions = collect($list->json('data.customerSessions'))->keyBy('_id');

        $this->assertCount(2, $sessions);
        $this->assertTrue($sessions[$current->session->id]['isCurrent']);

        $revoke = $this->graphQL(
            'mutation ($input: deleteCustomerSessionInput!) { deleteCustomerSession(input: $input) { customerSession { _id isCurrent message } } }',
            ['input' => ['id' => '/api/shop/customer/sessions/'.$other->session->id]],
            $this->bearer($current->accessToken),
        );

        $this->assertNull($revoke->json('errors'));
        $this->assertSame($other->session->id, $revoke->json('data.deleteCustomerSession.customerSession._id'));
        $this->assertSame(CustomerApiSession::REVOKED_BY_CUSTOMER, $other->session->fresh()->revoked_reason);
    }

    /**
     * Test createCustomerLogoutAll ends every session.
     */
    public function test_logout_all_ends_every_session(): void
    {
        $customer = $this->createCustomer();

        $current = $this->customerTokenPair($customer);
        $this->customerTokenPair($customer);

        $response = $this->graphQL(
            'mutation { createCustomerLogoutAll(input: {}) { customerLogoutAll { success revoked } } }',
            [],
            $this->bearer($current->accessToken),
        );

        $this->assertNull($response->json('errors'));
        $this->assertSame(2, $response->json('data.createCustomerLogoutAll.customerLogoutAll.revoked'));
    }

    /**
     * Test an admin access token does not authenticate on the storefront GraphQL endpoint.
     */
    public function test_an_admin_token_is_refused(): void
    {
        $admin = app(JwtSigner::class)->signAccessToken(1, 1, now(), now()->addMinutes(15));

        $response = $this->graphQL('{ customerSessions { _id } }', [], $this->bearer($admin));

        $this->assertNotNull($response->json('errors'));
        $this->assertNull($response->json('data.customerSessions'));
    }

    /**
     * Create a customer whose password is known.
     */
    protected function customerWithPassword(array $attributes = []): Customer
    {
        return $this->createCustomer(array_merge([
            'email' => 'login-'.uniqid().'@example.com',
            'password' => Hash::make(self::PASSWORD),
            'status' => 1,
            'is_verified' => 1,
        ], $attributes));
    }

    /**
     * Send createCustomerLogin.
     */
    protected function login(string $email, array $extra = [], string $password = self::PASSWORD): TestResponse
    {
        return $this->graphQL(
            'mutation ($input: createCustomerLoginInput!) { createCustomerLogin(input: $input) { customerLogin { '.self::PAIR_FIELDS.' id apiToken token success message } } }',
            ['input' => array_merge(['email' => $email, 'password' => $password], $extra)],
            $this->storefrontHeaders(),
        );
    }

    /**
     * Send createCustomerTokenRefresh.
     */
    protected function refresh(string $refreshToken): TestResponse
    {
        return $this->graphQL(
            'mutation ($input: createCustomerTokenRefreshInput!) { createCustomerTokenRefresh(input: $input) { customerTokenRefresh { '.self::PAIR_FIELDS.' } } }',
            ['input' => ['refreshToken' => $refreshToken]],
            $this->storefrontHeaders(),
        );
    }

    /**
     * The email the readCustomerProfile query answers with for a bearer token.
     */
    protected function profileEmail(string $token): ?string
    {
        return $this->graphQL('{ readCustomerProfile { email } }', [], $this->bearer($token))
            ->json('data.readCustomerProfile.email');
    }

    /**
     * The storefront headers with a bearer token.
     */
    protected function bearer(string $token): array
    {
        return array_merge($this->storefrontHeaders(), ['Authorization' => 'Bearer '.$token]);
    }
}
