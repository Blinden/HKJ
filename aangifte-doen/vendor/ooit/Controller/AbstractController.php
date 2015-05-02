<?php

abstract class AbstractController
{

    const MESSAGE_SESSION_KEY = 'flash_messages';

    protected $name = '';

    /**
     *
     */
    abstract public function dispatch();

    /**
     * Bewaar de waarde op in de session
     *
     * @param string $name
     * @param mixed $value
     * @return this
     */
    public function setSession($name, $value)
    {
        $_SESSION[$name] = $value;
        return $this;
    }

    public function hasSession($name)
    {
        if (isset($_SESSION[$name])) {
            return true;
        }
        return isset($_SESSION[$name]);
    }

    /**
     * Haal de waarde op uit de sessie
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getSession($name, $default = null)
    {
        return $this->hasSession($name) ? $_SESSION[$name] : $default;
    }

    public function hasMessage($name)
    {
        return (array_key_exists($name, $this->getSession(self::MESSAGE_SESSION_KEY, array())));
    }

    public function getMessage($name)
    {
        if ($this->hasMessage($name)) {
            $messages = $this->getSession(self::MESSAGE_SESSION_KEY);
            $message = $messages[$name];
            unset($messages[$name]);
            $this->setSession(self::MESSAGE_SESSION_KEY, array_filter($messages));
            return $message;
        }
        return '';
    }

    public function setMessage($name, $message = null)
    {
        $messages = $this->getSession(self::MESSAGE_SESSION_KEY, array());
        $messages[$name] = $message;
        $this->setSession(self::MESSAGE_SESSION_KEY, $messages);
    }

    /**
     * Genereer een Csrf string en bewaar deze in de sessie
     *
     * @return string
     */
    protected function generateCsrf($name = 'csrf', $salt = 'hoeveelkrijgjij.nl')
    {
        $this->setSession($name, md5($salt . rand() . $name));
        return $this->getSession($name);
    }

    /**
     * Valideer de Csrf tegen de waarde in de session
     *
     * @return boolean
     */
    protected function validateCsrf($name = 'csrf')
    {
        return $this->getSession($name) === $this->getPost($name, $this->getQuery($name));
    }

    public function getRequestUri()
    {
        $host = $_SERVER['HTTP_HOST'];
        $uri = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?'));

        return "http://{$host}{$uri}";
    }

    /**
     * Haal een Get-parameter op en escape automatisch.
     * Geef de default terug als de waarde niet bestaat.
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getQuery($name, $default = null)
    {
        return isset($_GET[$name]) ? htmlentities($_GET[$name]) : $default;
    }

    public function hasQuery($name)
    {
        return isset($_GET[$name]);
    }

    public function hasPost($name)
    {
        return isset($_POST[$name]);
    }

    /**
     * Haal een Post-parameter op en escape automatisch.
     * Geef de default terug als de waarde niet bestaat.
     * Als paramter een array is wordt recussief ge-escaped
     *
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getPost($name, $default = '')
    {
        if (isset($_POST[$name])) {
            if (is_array($_POST[$name])) {
                $result = $_POST[$name];
                array_walk_recursive($result, function(&$item, $key) {
                    $item = htmlentities($item);
                });
                return $result;
            }
            else {
                return htmlentities($_POST[$name]);
            }
        }
        return $default;
    }

    /**
     * Is dit een POST-request?
     *
     * @return boolean
     */
    public function isPost()
    {
        return stripos($_SERVER['REQUEST_METHOD'], 'post') === 0;
    }

    /**
     * Render het script in de context van de controller
     * TODO render in de view context maar de bestaat nog niet!
     *
     * @param string $script
     */
    public function render($script, $view = null, $dir = null)
    {
        $dir = $dir ? : $this->name;
        return include "views/{$dir}/{$script}.phtml";
    }

    public function renderBuffered($script, $view = null, $dir = null)
    {
        ob_start();
        try {
            $dir = $dir ? : $this->name;
            include "views/{$dir}/{$script}.phtml";
            $content = ob_get_clean();
        } catch (Exception $e) {
            ob_end_clean();
            throw $e;
        }
        return $content;
    }

    /**
     * Redirect naar de url
     *
     * @param string $url
     */
    public function redirect($url)
    {
        header("location: {$url}");
        exit;
    }

    /**
     * Verstuur een email als er gegevens gepost zijn.
     */
    protected function sendEmail($sender, $recipient, $subject, $message)
    {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=iso-8859-1\r\n";
        $headers .= "From: {$sender}\r\n";
        mail($recipient, $subject, $message, $headers);
        return $this;
    }

}
