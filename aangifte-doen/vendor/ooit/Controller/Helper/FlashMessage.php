<?php

/*
 * Stub
 */

/**
 * Description of flashMessage
 *
 * @author Bart
 */
class FlashMessage
{

    /**
     * @var Session
     */
    protected $session;
    protected $controller;

    public function __construct(AbstractController $controller)
    {
        $this->session = new Session('flash_messages');
        $this->controller = $controller;
    }

    public function hasMessage($name)
    {
        return $this->session->has($name);
    }

    public function getMessage($name)
    {
        if ($this->hasMessage($name)) {
            $message = $this->session->get($name, null);
            $this->session->delete($name);
        }
        return $message;
    }

    public function setMessage($name, $message = null)
    {
        if (!$message) {
            $this->session->delete($name);
        }
        else {
            $this->session->set($name, $message);
        }
    }

}
