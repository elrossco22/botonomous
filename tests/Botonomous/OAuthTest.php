<?php

namespace Botonomous;

use Botonomous\client\ApiClient;
use Botonomous\utility\RequestUtility;
use Botonomous\utility\SessionUtility;
use GuzzleHttp\Client;
use /* @noinspection PhpUndefinedClassInspection */
    GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use /* @noinspection PhpUndefinedClassInspection */
    GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

/**
 * Class OAuthTest.
 */
class OAuthTest extends TestCase
{
    /**
     * Test doOauth.
     *
     * @runInSeparateProcess
     */
    public function testDoOauth()
    {
        $oauth = new OAuth();

        $requestUtility = new RequestUtility();
        $requestUtility->setGet([
            'code'  => '12345',
            'state' => '54321',
        ]);

        $oauth->setRequestUtility($requestUtility);

        $sessionUtility = new SessionUtility();
        $sessionUtility->set('state', '54321');

        $oauth->setSessionUtility($sessionUtility);

        $this->expectException('\Exception');
        $this->expectExceptionMessage('invalid_client_id');

        $oauth->doOauth();
    }

    /**
     * Test doOauth.
     *
     * @runInSeparateProcess
     */
    public function testDoOauthInvalidState()
    {
        $oauth = new OAuth();

        $requestUtility = new RequestUtility();
        $requestUtility->setGet([
            'code'  => '12345',
            'state' => '54321',
        ]);

        $oauth->setRequestUtility($requestUtility);

        $sessionUtility = new SessionUtility();
        $sessionUtility->set('state', '12345');

        $oauth->setSessionUtility($sessionUtility);

        $this->expectException('\Exception');
        $this->expectExceptionMessage("State: '54321' is not valid");

        $oauth->doOauth();
    }

    /**
     * Test doOauth.
     */
    public function testDoOauthEmptyAccess()
    {
        $oauth = new OAuth();

        $requestUtility = new RequestUtility();
        $requestUtility->setGet([
            'code'  => '12345',
            'state' => '54321',
        ]);

        $oauth->setRequestUtility($requestUtility);

        $oauth->setAccessToken('');

        $this->expectException('\Exception');
        $this->expectExceptionMessage('Access token is not provided');

        $oauth->doOauth();
    }

    /**
     * Test getClientSecret.
     */
    public function testGetClientSecret()
    {
        $oauth = new OAuth();
        $oauth->setConfig(new Config());
        $this->assertEquals((new Config())->get('clientSecret'), $oauth->getClientSecret());
    }

    /**
     * Test OAuth.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testOAuth()
    {
        $authorizationUrl = OAuth::AUTHORIZATION_URL;
        $clientId = '4b39e9-752c4';
        $clientSecret = '123456';
        $scope = ['bot', 'users:read'];
        $scopeString = implode(',', $scope);

        $oAuth = new OAuth($clientId, $clientSecret, $scope);
        $addButton = $oAuth->generateAddButton();

        $stateQueryString = '';

        if (!empty($oAuth->getState())) {
            $state = $oAuth->getState();
            $stateQueryString = "&state={$state}";
        }

        $expected = "<a href='{$authorizationUrl}?scope={$scopeString}&client_id={$clientId}{$stateQueryString}'>
<img alt='Add to Slack' class='' height='40' width='139'
src='https://platform.slack-edge.com/img/add_to_slack.png'
srcset='https://platform.slack-edge.com/img/add_to_slack.png 1x,
https://platform.slack-edge.com/img/add_to_slack@2x.png 2x' /></a>";

        $this->assertEquals($expected, $addButton);

        $addButton = $oAuth->generateAddButton('50', '50', 'testCssClass');

        $expected = "<a href='{$authorizationUrl}?scope={$scopeString}&client_id={$clientId}{$stateQueryString}'>
<img alt='Add to Slack' class='testCssClass' height='50' width='50'
src='https://platform.slack-edge.com/img/add_to_slack.png'
srcset='https://platform.slack-edge.com/img/add_to_slack.png 1x,
https://platform.slack-edge.com/img/add_to_slack@2x.png 2x' /></a>";

        $this->assertEquals($expected, $addButton);
    }

    /**
     * Test getAccessToken.
     */
    public function testGetAccessToken()
    {
        $accessToken = 'xoxp-XXXXXXXX-XXXXXXXX-XXXXX';

        $clientId = '4b39e9-752c4';
        $clientSecret = '123456';
        $scope = ['bot', 'users:read'];

        $oAuth = new OAuth($clientId, $clientSecret, $scope);
        $oAuth->setAccessToken($accessToken);

        $this->assertEquals($accessToken, $oAuth->getAccessToken('1234'));
    }

    /**
     * Test getAccessToken.
     */
    public function testGetAccessTokenWithState()
    {
        $accessToken = 'xoxp-XXXXXXXX-XXXXXXXX-XXXXX';

        $clientId = '4b39e9-752c4';
        $clientSecret = '123456';
        $scope = ['bot', 'users:read'];

        $oAuth = new OAuth($clientId, $clientSecret, $scope);
        $oAuth->setAccessToken($accessToken);

        $this->assertEquals($accessToken, $oAuth->getAccessToken('1234', true, '1234'));
    }

    /**
     * Test getAccessTokenEmptyCode.
     */
    public function testGetAccessTokenEmptyCode()
    {
        $oauth = new OAuth();

        $this->expectException('\Exception');
        $this->expectExceptionMessage('Code must be provided to get the access token');

        $oauth->getAccessToken('', false);
    }

    /**
     * Test getAccessToken.
     */
    public function testGetAccessTokenMissingState()
    {
        $accessToken = 'xoxp-XXXXXXXX-XXXXXXXX-XXXXX';

        $clientId = '4b39e9-752c4';
        $clientSecret = '123456';
        $scope = ['bot', 'users:read'];

        $oAuth = new OAuth($clientId, $clientSecret, $scope);

        $this->expectException('\Exception');
        $this->expectExceptionMessage("State: '' is not valid");

        $this->assertEquals($accessToken, $oAuth->getAccessToken(''));
    }

    /**
     * Test getAccessToken which includes getAccessToken.
     */
    public function testRequestAccessToken()
    {
        $clientId = '4b39e9-752c4';
        $clientSecret = '123456';
        $scope = ['bot', 'users:read'];

        $oAuth = new OAuth($clientId, $clientSecret, $scope);

        $content = '{
    "ok": true,
    "access_token": "xoxp-XXXXXXXX-XXXXXXXX-XXXXX",
    "scope": "incoming-webhook,commands,bot",
    "team_name": "Team Installing Your Hook",
    "team_id": "XXXXXXXXXX",
    "incoming_webhook": {
        "url": "https://hooks.slack.com/TXXXXX/BXXXXX/XXXXXXXXXX",
        "channel": "#channel-it-will-post-to",
        "configuration_url": "https://teamname.slack.com/services/BXXXXX"
    },
    "bot":{
        "bot_user_id":"UTTTTTTTTTTR",
        "bot_access_token":"xoxb-XXXXXXXXXXXX-TTTTTTTTTTTTTT"
    }
}';

        $apiClient = new ApiClient();

        /** @noinspection PhpUndefinedClassInspection */
        $mock = new MockHandler([
            new Response(200, [], $content),
        ]);

        /** @noinspection PhpUndefinedClassInspection */
        $handler = new HandlerStack($mock);
        /** @noinspection PhpUndefinedClassInspection */
        $client = new Client(['handler' => $handler]);

        $apiClient->setClient($client);

        $oAuth->setApiClient($apiClient);

        $this->assertEquals('xoxp-XXXXXXXX-XXXXXXXX-XXXXX', $oAuth->getAccessToken('1234', false));
    }

    /**
     * Test getAccessToken which includes getAccessToken.
     */
    public function testRequestAccessTokenException()
    {
        $oAuth = new OAuth();

        $apiClient = new ApiClient();

        /** @noinspection PhpUndefinedClassInspection */
        $mock = new MockHandler([
            new RequestException('Error Communicating with Server', new Request('Post', $apiClient::BASE_URL.'test')),
        ]);

        /** @noinspection PhpUndefinedClassInspection */
        $handler = new HandlerStack($mock);
        /** @noinspection PhpUndefinedClassInspection */
        $client = new Client(['handler' => $handler]);

        $apiClient->setClient($client);

        $oAuth->setApiClient($apiClient);

        $this->expectException('\Exception');
        $this->expectExceptionMessage('Error Communicating with Server');

        $oAuth->getAccessToken('1234', false);
    }

    /**
     * Test verifyState.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testVerifyState()
    {
        $oauth = new OAuth('12345', '12345', ['bot']);

        $result = $oauth->verifyState('dummyState');

        $this->assertFalse($result);

        $dummyState = $oauth->getState();

        $result = $oauth->verifyState($dummyState);

        $this->assertTrue($result);
    }

    /**
     * Test getClientId.
     *
     * @throws \Exception
     */
    public function testGetClientId()
    {
        $oauth = new OAuth();
        $clientId = $oauth->getClientId();

        $this->assertEquals((new Config())->get('clientId'), $clientId);

        $oauth->setClientId('12345');

        $this->assertEquals('12345', $oauth->getClientId());
    }

    /**
     * Test getScopes.
     *
     * @throws \Exception
     */
    public function testGetScopes()
    {
        $oauth = new OAuth();
        $scopes = $oauth->getScopes();

        $this->assertEquals((new Config())->get('scopes'), $scopes);

        $oauth->setScopes(['bot']);

        $this->assertEquals(['bot'], $oauth->getScopes());
    }

    /**
     * Test getRedirectUri.
     */
    public function testGetRedirectUri()
    {
        $oauth = new OAuth();
        $oauth->setRedirectUri('http://test.com');

        $this->assertEquals('http://test.com', $oauth->getRedirectUri());
    }

    /**
     * Test getTeamId.
     */
    public function testGetTeamId()
    {
        $oauth = new OAuth();
        $oauth->setTeamId('12345');

        $this->assertEquals('12345', $oauth->getTeamId());
    }

    /**
     * Test getBotUserId.
     */
    public function testGetBotUserId()
    {
        $oauth = new OAuth();
        $oauth->setBotUserId('12345');

        $this->assertEquals('12345', $oauth->getBotUserId());
    }

    /**
     * Test getBotAccessToken.
     */
    public function testGetBotAccessToken()
    {
        $oauth = new OAuth();
        $oauth->setBotAccessToken('12345');

        $this->assertEquals('12345', $oauth->getBotAccessToken());
    }

    /**
     * Test getChannel.
     */
    public function testGetChannel()
    {
        $oauth = new OAuth();
        $oauth->setChannel('general');

        $this->assertEquals('general', $oauth->getChannel());
    }

    /**
     * Test getTeamName.
     */
    public function testGetTeamName()
    {
        $oauth = new OAuth();
        $oauth->setTeamName('test');

        $this->assertEquals('test', $oauth->getTeamName());
    }

    /**
     * Test getConfigurationUrl.
     */
    public function testGetConfigurationUrl()
    {
        $oauth = new OAuth();
        $oauth->setConfigurationUrl('http://test.com');

        $this->assertEquals('http://test.com', $oauth->getConfigurationUrl());
    }

    /**
     * Test getApiClient.
     */
    public function testGetApiClient()
    {
        $oauth = new OAuth();

        $this->assertEquals(new ApiClient(), $oauth->getApiClient());
    }

    /**
     * Test getRequestUtility.
     */
    public function testGetRequestUtility()
    {
        $oauth = new OAuth();

        $this->assertEquals(new RequestUtility(), $oauth->getRequestUtility());
    }
}
