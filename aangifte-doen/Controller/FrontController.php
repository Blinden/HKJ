<?php

require_once('vendor/ooit/ooit.php');

require_once('Entity/UserEntity.php');
require_once('Entity/ProfileEntity.php');
require_once('Entity/OpgaveEntity.php');
require_once('Entity/AanvragerEntity.php');
require_once('Entity/BetaalEntity.php');
require_once('Entity/AangifteEntity.php');

require_once('Table/UserTable.php');
require_once('Table/AangifteTable.php');
require_once('Table/BetaalTable.php');

require_once('Service/UserService.php');
require_once('Service/AangifteService.php');
require_once('Service/BetaalService.php');
require_once('Service/FacebookService.php');
require_once('Service/MandrillService.php');
require_once('Service/IdealService.php');
require_once('Service/MoneybirdService.php');
require_once('Service/MailchimpService.php');

require_once('Controller/TestController.php');
require_once('Controller/AangifteController.php');
require_once('Controller/OpgaveController.php');
require_once('Controller/BetaalController.php');
require_once('Controller/MigrateController.php');

class FrontController extends AbstractController
{

    protected $name = 'front';

    const ENV_PRODUCTION = 'production';
    const ENV_DEVELOPMENT = 'development';
    const ENV_TESTING = 'testing';

    static protected $config = array(
        self::ENV_PRODUCTION => array(
            'show_exceptions' => false,
            'services' => array(
                'BetaalService' => array(
                    'betaal-url' => 'https://hoeveelkrijgjij.nl/aangifte-doen/public_html/?action=betaal',
                ),
                'Database' => array(
                    'dns' => 'sqlite:data/database.sqlite',
                ),
                'FacebookService' => array(
                    'appId' => '1446346438926060',
                    'secret' => '',
                ),
                'IdealService' => array(
                    'connector-config' => 'vendor/ideal/Connector/config.conf',
                ),
                'MandrillService' => array(
                    'api_key' => '',
                ),
                'MoneybirdService' => array(
                    'emailaddress' => 'api@hoeveelkrijgjij.nl',
                    'password' => '',
                    'clientname' => 'absolute-diensten',
                    'taxrateid' => 392252,
                ),
                'MailchimpService' => array(
                    'api_key' => '',
                    'profile_list_id' => '',
                    'order_list_id' => '',
                    'newsletter_list_id' => '',
                    'store_id' => 'Hoeveelkrijgjij',
                    'store_name' => 'hoeveelkrijgjij.nl',
                ),
            ),
        ),
        self::ENV_DEVELOPMENT => array(
            'show_exceptions' => true,
            'services' => array(
                'BetaalService' => array(
                    'betaal-url' => 'http://localhost:9001/public_html/?action=betaal',
                ),
                'Database' => array(
                    'dns' => 'sqlite:data/database-test.sqlite',
                ),
                'FacebookService' => array(
                    /* Test Api */
                    'appId' => '703953196281373',
                    'secret' => 'ce7032dfbea924dbf249bfc8adc1c7e6',
                ),
                'IdealService' => array(
                    /* Test config */
                    'connector-config' => 'config/ideal-test-config.conf',
                ),
                'MandrillService' => array(
                    /* Test Api */
                    'api_key' => 'nqaV_7FgpLZZuv7Dm7kX6g',
                ),
                'MoneybirdService' => array(
                    'emailaddress' => 'api@hoeveelkrijgjij.nl', //vraag voor development een development account aan via support@moneybird.nl
                    'password' => '',
                    'clientname' => 'absolute-diensten',
                    'taxrateid' => 392252,
                ),
                'MailchimpService' => array(
                    'api_key' => '',
                    'profile_list_id' => '',
                    'order_list_id' => '',
                    'newsletter_list_id' => '',
                    'store_id' => 'Hoeveelkrijgjij',
                    'store_name' => 'hoeveelkrijgjij.nl',
                ),
            ),
        ),
        self::ENV_TESTING => array(
            'show_exceptions' => true,
            'services' => array(
                'BetaalService' => array(
                    'betaal-url' => 'http://localhost:9001/public_html/?action=betaal',
                ),
                'Database' => array(
                    'dns' => 'sqlite:data/database-test.sqlite',
                ),
                'FacebookService' => array(
                    'appId' => '703953196281373',
                    'secret' => 'ce7032dfbea924dbf249bfc8adc1c7e6',
                ),
                'IdealService' => array(
                    'connector-config' => 'config/ideal-test-config.conf',
                ),
                'MandrillService' => array(
                    'api_key' => 'nqaV_7FgpLZZuv7Dm7kX6g',
                ),
                'MoneybirdService' => array(
                    'emailaddress' => 'api@hoeveelkrijgjij.nl', //vraag voor development een development account aan via support@moneybird.nl
                    'password' => '',
                    'clientname' => 'absolute-diensten',
                    'taxrateid' => 392252,
                ),
                'MailchimpService' => array(
                    'api_key' => '',
                    'profile_list_id' => '',
                    'order_list_id' => '',
                    'newsletter_list_id' => '',
                    'store_id' => 'Hoeveelkrijgjij',
                    'store_name' => 'hoeveelkrijgjij.nl',
                ),
            ),
        ),
    );

    static public function getEnviroment()
    {
        if (isset($_SERVER['APP_ENV'])) {
            return strtolower($_SERVER['APP_ENV']);
        }
        return 'production';
    }

    static public function getConfig($name = null, $default = null)
    {
        $config = static::$config[self::ENV_PRODUCTION];
        if (static::getEnviroment() !== self::ENV_PRODUCTION) {
            $config = array_merge($config, static::$config[static::getEnviroment()]);
        }
        if ($name) {
            $name = strtolower($name);
            if (isset($config[$name])) {
                $config = $config[$name];
            }
            else {
                $config = $default;
            }
        }
        return $config;
    }

    public function error(\Exception $e)
    {

    }

    public function dispatch()
    {
        try {
            // GET: ..test
            if ($this->hasQuery('test')) {
                $controller = new TestController();
                return $controller->dispatch($this);
            }

            // GET: &error=
            if ($this->hasQuery('error')) {
                return $this->render('not-found');
            }

            // GET: &action=
            if ($this->hasQuery('action')) {
                $action = strtolower($this->getQuery('action'));

                if ($action == 'aangifte') {
                    $controller = new AangifteController($this);
                    return $controller->dispatch();
                }
                if ($action == 'opgave') {
                    $controller = new OpgaveController($this);
                    return $controller->dispatch();
                }
                if ($action == 'betaal') {
                    $controller = new BetaalController($this);
                    return $controller->dispatch();
                }
//                if ($action == 'facebook') {
//                    $controller = new FacebookController($this);
//                    return $controller->dispatch();
//                }
//                if ($action == 'migrate') {
//                    $controller = new MigrateController($this);
//                    return $controller->dispatch();
//                }
            }
            $controller = new AangifteController($this);
            return $controller->dispatch();

//        return $this->redirect('?error=not-found');
        } catch (\ErrorException $e) {
            return $this->render('error-500', array('show_exceptions' => $this->getConfig('show_exceptions', false), 'exception' => $e), 'error');
        } catch (\Exception $e) {
            return $this->render('error-500', array('show_exceptions' => $this->getConfig('show_exceptions', false), 'exception' => $e), 'error');
        }
    }

}
