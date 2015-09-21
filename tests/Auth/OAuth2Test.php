<?php

namespace GoPay\Auth;

use GoPay\Http\Response;

class OAuth2Test extends \PHPUnit_Framework_TestCase
{
    private $config = [
        'clientID' => 'irrelevant id',
        'clientSecret' => 'irrelevant secret',
        'scope' => PaymentScope::ALL
    ];

    private $browser;
    private $auth;

    protected function setUp()
    {
        $cache = new InMemoryTokenCache();
        $this->browser = $this->prophesize('GoPay\Http\Browser');
        $this->auth = new OAuth2($this->config, $cache, $this->browser->reveal());
    }

    /** @dataProvider provideAccessToken */
    public function testShouldRequestAccessTokenOnce($statusCode, array $jsonResponse, $expectedCalls, $expectedToken)
    {
        $apiResponse = new Response;
        $apiResponse->statusCode = $statusCode;
        $apiResponse->json = $jsonResponse;

        $this->browser->postJson(
            'https://gw.sandbox.gopay.com/api/oauth2/token',
            [
                'Accept' => 'application/json',
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Authorization' => [$this->config['clientID'], $this->config['clientSecret']],
            ],
            ['grant_type' => 'client_credentials', 'scope' => $this->config['scope']]
        )->shouldBeCalledTimes($expectedCalls)->willReturn($apiResponse);

        assertThat($this->auth->getAccessToken(), is($expectedToken));
        assertThat($this->auth->getAccessToken(), is($expectedToken));
    }

    public function provideAccessToken()
    {
        return [
            'success' => [200, ['access_token' => 'new token', 'expires_in' => 1800], 1, 'new token'],
            'failure' => [400, ['error' => 'access_denied'], 2, emptyString()]
        ];
    }
}