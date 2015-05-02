<?php

/*
 *  Set application enviroment for live test
 */
//$_SERVER['APP_ENV'] = 'testing';

/*
 *  Use for debug on server HKJ
 */
//ini_set('display_errors', 1);
//error_reporting( E_ALL ^ (E_NOTICE | E_WARNING ));

chdir(dirname(__DIR__));

require_once('vendor/ooit/ServiceProvider.php');
require_once('Controller/FrontController.php');

session_start();

$frontController = ServiceProvider::get('FrontController');
$frontController->dispatch();
