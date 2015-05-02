<?php

require_once('vendor/facebook-php-sdk/src/facebook.php');

class FacebookService extends AbstractService
{

    /**
     *
     * @var Facebook
     */
    public $facebook;

    public function __construct()
    {
        $this->facebook = new Facebook($this->getConfig());
    }

    public function getUserId()
    {
        return $this->facebook->getUser();
    }

    public function getLoginStatus()
    {
        $params = array(
//            'scope' => array('publish_stream', 'email'),
        );
        return $this->facebook->getLoginStatusUrl($params);
    }

    public function getLoginUrl()
    {
        $params = array(
            'scope' => array('publish_stream', 'email', 'user_birthday'),
        );
        return $this->facebook->getLoginUrl($params);
    }

    public function getUserProfile()
    {
        $profiel = null;
        if ($this->getUserId()) {
            try {
                $profiel = new \ProfileEntity($this->facebook->api('/me'));
            } catch (FacebookApiException $e) {
                error_log($e);
            }
        }
        return $profiel;
    }

}
