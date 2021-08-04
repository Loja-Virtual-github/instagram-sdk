<?php

namespace Pablosanches\InstagramSDK;

use Owl\Method as Http;

class Instagram
{
    const AUTH_URI = 'https://api.instagram.com/oauth/authorize';
    const TOKEN_URI = 'https://api.instagram.com/oauth/access_token';
    const LONG_LIVED_TOKEN_URI = 'https://graph.instagram.com/access_token';
    const REFRESH_TOKEN_URI = 'https://graph.instagram.com/refresh_access_token';
    const USER_URI = 'https://graph.instagram.com/{user-id}';
    const USER_MEDIA_URI = 'https://graph.instagram.com/v11.0/{user-id}/media?fields={fields}&access_token={access-token}';
    
    /**
     * APP ID
     *
     * @var string
     */
    private $clientID;

    /**
     * APP Secret Key
     *
     * @var string
     */
    private $clientSecret;

    /**
     * Valid OAuth redirect uri
     *
     * @var string
     */
    private $redirectURI;

    /**
     * Application access permission list 
     *
     * @var array
     */
    private $scope = array('user_profile', 'user_media');

    /**
     * User access token
     *
     * @var string
     */
    private $accessToken;

    /**
     * User ID
     * 
     * @var string
     */
    private $userID;

    /**
     * List of fields to return when searching for media
     *
     * @var array
     */
    private $mediaFields = [
        'id',
        'media_type',
        'media_url',
        'caption',
        'permalink',
        'thumbnail_url'
    ];

    /**
     * Constructor Method
     *
     * @param string $clientID
     * @param string $clientSecret
     * @param string $redirectURI
     */
    public function __construct($clientID, $clientSecret, $redirectURI)
    {
        $this
            ->setClientID($clientID)
            ->setClientSecret($clientSecret)
            ->setRedirectURI($redirectURI);
    }

    /**
     * Set the client ID
     *
     * @param string $clientID
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setClientID($clientID)
    {
        $this->clientID = $clientID;
        return $this;
    }

    /**
     * Get client ID
     *
     * @return string
     */
    public function getClientID()
    {
        return $this->clientID;
    }

    /**
     * Set client secret
     *
     * @param string $clientSecret
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setClientSecret($clientSecret)
    {
        $this->clientSecret = $clientSecret;
        return $this;
    }

    /**
     * Get client secret
     *
     * @return string
     */
    public function getClientSecret()
    {
        return $this->clientSecret;
    }

    /**
     * Set redirect URI
     *
     * @param string $redirectURI
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setRedirectURI($redirectURI)
    {
        $this->redirectURI = $redirectURI;
        return $this;
    }

    /**
     * Get redirect URI
     *
     * @return string
     */
    public function getRedirectURI()
    {
        return $this->redirectURI;
    }

    /**
     * Set scope
     *
     * @param array $scope
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setScope(array $scope)
    {
        $this->scope = $scope;
        return $this;
    }

    /**
     * Get scope
     *
     * @return string
     */
    public function getScope()
    {
        return implode(',', $this->scope);
    }

    /**
     * Set access token
     *
     * @param string $accessToken
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setAccessToken($accessToken)
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    /**
     * Get access token
     *
     * @return string
     */
    public function getAccessToken()
    {
        return $this->accessToken;
    }

    /**
     * Set UserID
     *
     * @param string $userID
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setUserID($userID)
    {
        $this->userID = $userID;
        return $this;
    }

    /**
     * Get UserID
     *
     * @return string
     */
    public function getUserID()
    {
        return $this->userID;
    }

    /**
     * Set media fields
     *
     * @param mixed (string/array) $fields
     * @return Pablosanches\InstagramSDK\Instagram
     */
    public function setMediaFields($fields)
    {
        if (is_array($fields)) {
            $this->mediaFields = $fields;
        } else {
            array_push($this->mediaFields, $fields);
        }

        return $this;
    }

    /**
     * Get media fields
     *
     * @return string
     */
    public function getMediaFields()
    {
        return implode(',', $this->mediaFields);
    }

    /**
     * Get Authentication URL
     *
     * @param string $state Param to control de request callback
     * @return string
     */
    public function getAuthURI($state = null)
    {
        $queryString = array(
            'client_id' => $this->getClientID(),
            'scope' => $this->getScope(),
            'response_type' => 'code',
            'redirect_uri' => $this->getRedirectURI(),
            'state' => $state
        );

        return self::AUTH_URI . '?' . http_build_query($queryString);
    }

    /**
     * Get token from code
     *
     * @param string $code
     * @return array
     */
    public function getTokenFromCode($code)
    {
        if (empty($code)) {
            return false;
        }
        $param = array(
            'client_id' => $this->getClientID(),
            'client_secret' => $this->getClientSecret(),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $this->getRedirectURI()
        );

        $request = new Http\Post(self::TOKEN_URI, array('data' => $param));

        $request->send();
        $response = $request->getResponse();

        return json_decode($response, true);
    }

    /**
     * Get long lived token
     *
     * @return array
     */
    public function getLongLivedToken()
    {
        $queryString = array(
            'grant_type' => 'ig_exchange_token',
            'client_secret' => $this->getClientSecret(),
            'access_token' => $this->getAccessToken()
        );

        $uri = self::LONG_LIVED_TOKEN_URI . '?' . http_build_query($queryString);
        $request = new Http\Get($uri);
        $request->send();

        $response = $request->getResponse();

        $response = json_decode($response, true);

        if ($request->getStatus() == 200) {
            $response['expires_in'] = self::timestampToDate($response['expires_in']);
        }

        return $response;
    }

    /**
     * Refresh token
     *
     * @return array
     */
    public function refreshToken()
    {
        $queryString = array(
            'grant_type' => 'ig_refresh_token',
            'access_token' => $this->getAccessToken()
        );

        $uri = self::REFRESH_TOKEN_URI . '?' . http_build_query($queryString);
        $request = new Http\Get($uri);
        $request->send();

        $response = $request->getResponse();
        $response = json_decode($response, true);
        
        if ($request->getStatus() == 200) {
            $response['expires_in'] = self::timestampToDate($response['expires_in']);
        }

        return $response;
    }

    /**
     * Get user data
     *
     * @param string $userID
     * @return array
     */
    public function getUser($userID)
    {
        $uri = self::parseURL(self::USER_URI, array(
            '{user-id}' => $this->getUserID()
        ));

        $request = new Http\Get($uri);
        $request->send();

        $response = $request->getResponse();

        return json_decode($response, true);
    }
    
    /**
     * Get user media
     *
     * @param string $userID
     * @return array
     */
    public function getUserMedia()
    {
        $uri = self::parseURL(self::USER_MEDIA_URI, array(
            '{user-id}' => $this->getUserID(),
            '{fields}' => $this->getMediaFields(),
            '{access-token}' => $this->getAccessToken()
        ));
        
        $request = new Http\Get($uri);
        $request->send();

        $response = $request->getResponse();

        return json_decode($response, true);
    }

    /**
     * Parse the url with params
     *
     * @param string $url
     * @param array $params
     * @return string
     */
    private static function parseURL($url, array $params = array())
    {
        return strtr($url, $params);
    }

    private static function timestampToDate(&$timestamp)
    {
        $expires = time() + $timestamp;
        $expiraEm = new \DateTime();
        $expiraEm->setTimestamp($expires);

        return $expiraEm->format('Y-m-d H:i:s');
    }
}