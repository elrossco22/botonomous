<?php

namespace Slackbot;

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

use Slackbot\client\ApiClient;
use Slackbot\utility\SecurityUtility;

/**
 * Class OAuth.
 */
class OAuth
{
    const AUTHORIZATION_URL = 'https://slack.com/oauth/authorize';

    private $clientId;
    private $clientSecret;
    private $scopes;
    private $redirectUri;
    private $state;
    private $teamId;
    private $apiClient;

    /**
     * @var string configuration_url will be the URL that you can point your user to if they'd like to edit
     *             or remove this integration in Slack
     */
    private $configurationUrl;

    /**
     * @var string The team_name field will be the name of the team that installed your app
     */
    private $teamName;
    private $accessToken;

    /**
     * @var string the channel will be the channel name that they have chosen to post to
     */
    private $channel;

    /**
     * @var string you will need to use bot_user_id and bot_access_token whenever you are acting on behalf of
     *             that bot user for that team context.
     *             Use the top-level access_token value for other integration points.
     */
    private $botUserId;
    private $botAccessToken;

    /**
     * OAuth constructor.
     *
     * @param       $clientId
     * @param       $clientSecret
     * @param array $scopes
     */
    public function __construct($clientId, $clientSecret, array $scopes)
    {
        $this->setClientId($clientId);
        $this->setClientSecret($clientSecret);
        $this->setScopes($scopes);
    }

    /**
     * @return string
     */
    public function getClientId()
    {
        return $this->clientId;
    }

    /**
     * @param string $clientId
     */
    public function setClientId($clientId)
    {
        $this->clientId = $clientId;
    }

    /**
     * @return array
     */
    public function getScopes()
    {
        return $this->scopes;
    }

    /**
     * @param array $scopes
     */
    public function setScopes($scopes)
    {
        $this->scopes = $scopes;
    }

    /**
     * @return string
     */
    public function getRedirectUri()
    {
        return $this->redirectUri;
    }

    /**
     * @param string $redirectUri
     */
    public function setRedirectUri($redirectUri)
    {
        $this->redirectUri = $redirectUri;
    }

    /**
     * @return string
     */
    public function getState()
    {
        if (!isset($this->state)) {
            $this->setState((new SecurityUtility())->generateToken());
        }

        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $_SESSION['state'] = $state;
        $this->state = $state;
    }

    /**
     * @return int
     */
    public function getTeamId()
    {
        return $this->teamId;
    }

    /**
     * @param int $teamId
     */
    public function setTeamId($teamId)
    {
        $this->teamId = $teamId;
    }

    /**
     * @param string $height
     * @param string $weight
     * @param string $cssClass
     *
     * @return string
     */
    public function generateAddButton($height = '40', $weight = '139', $cssClass = '')
    {
        $authorizationUrl = self::AUTHORIZATION_URL;
        $scope = implode(',', $this->getScopes());
        $clientId = $this->getClientId();

        if (!empty($cssClass)) {
            $cssClass = "class={$cssClass}";
        }

        $stateQueryString = '';

        if (!empty($this->getState())) {
            $state = $this->getState();
            $stateQueryString = "&state={$state}";
        }

        $html = "<a href='{$authorizationUrl}?scope={$scope}&client_id={$clientId}{$stateQueryString}'>
<img {$cssClass} alt='Add to Slack' height='{$height}' width='{$weight}'
src='https://platform.slack-edge.com/img/add_to_slack.png'
srcset='https://platform.slack-edge.com/img/add_to_slack.png 1x,
https://platform.slack-edge.com/img/add_to_slack@2x.png 2x' /></a>";

        return $html;
    }

    /**
     * @param $code
     *
     * @throws \Exception
     *
     * @return mixed
     */
    public function getAccessToken($code)
    {
        if (!isset($this->accessToken)) {
            try {
                $response = $this->requestAccessToken($code);
            } catch (\Exception $e) {
                throw $e;
            }

            if ($response['ok'] !== true) {
                throw new \Exception($response['error']);
            }

            $this->setAccessToken($response['access_token']);
            $this->setTeamId($response['team_id']);
            $this->setBotUserId($response['bot']['bot_user_id']);
            $this->setBotAccessToken($response['bot']['bot_access_token']);

            $channel = '';

            if (isset($response['incoming_webhook']['channel'])) {
                $channel = $response['incoming_webhook']['channel'];
            }

            $this->setChannel($channel);
        }

        return $this->accessToken;
    }

    /**
     * @param $code
     *
     * @throws \Exception
     *
     * @return mixed
     */
    private function requestAccessToken($code)
    {
        if (empty($code)) {
            throw new \Exception('Code must be provided to get the access token');
        }

        try {
            return $this->getApiClient()->oauthAccess([
                'client_id'     => $this->getClientId(),
                'client_secret' => $this->getClientSecret(),
                'code'          => $code,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $accessToken
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    /**
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * @param string $clientSecret
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
    }

    /**
     * @return string
     */
    public function getBotUserId()
    {
        return $this->botUserId;
    }

    /**
     * @param string $botUserId
     */
    public function setBotUserId($botUserId)
    {
        $this->botUserId = $botUserId;
    }

    /**
     * @return string
     */
    public function getBotAccessToken()
    {
        return $this->botAccessToken;
    }

    /**
     * @param string $botAccessToken
     */
    public function setBotAccessToken($botAccessToken)
    {
        $this->botAccessToken = $botAccessToken;
    }

    /**
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * @param string $channel
     */
    public function setChannel($channel)
    {
        $this->channel = $channel;
    }

    /**
     * @return string
     */
    public function getTeamName()
    {
        return $this->teamName;
    }

    /**
     * @param string $teamName
     */
    public function setTeamName($teamName)
    {
        $this->teamName = $teamName;
    }

    /**
     * @return string
     */
    public function getConfigurationUrl()
    {
        return $this->configurationUrl;
    }

    /**
     * @param string $configurationUrl
     */
    public function setConfigurationUrl($configurationUrl)
    {
        $this->configurationUrl = $configurationUrl;
    }

    /**
     * @return ApiClient
     */
    public function getApiClient()
    {
        if (!isset($this->apiClient)) {
            $this->setApiClient(new ApiClient());
        }

        return $this->apiClient;
    }

    /**
     * @param ApiClient $apiClient
     */
    public function setApiClient(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }
}
