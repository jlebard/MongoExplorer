<?php
//setlocale(LC_TIME, 'fr_FR.UTF8', 'fr.UTF8', 'fr_FR.UTF-8', 'fr.UTF-8');
//On initialise le timeZone
ini_set('date.timezone', 'Europe/Brussels');
//require (__DIR__ . '/../config/config.php');


//On ajoute l'autoloader
$loader = require_once __DIR__ . '/../vendor/autoload.php';

//dans l'autoloader nous ajoutons notre r�pertoire applicatif 
$loader->add('App',dirname(__DIR__));




//Nous instancions un objet Silex\Application
$app = new Silex\Application();

//en dev, nous voulons voir les erreurs
$app['debug'] = true;

$app['asset_path'] = '/sc';
//Twig
$app->register(new Silex\Provider\SessionServiceProvider());

$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => dirname(__DIR__) . '/Views'
    ,'twig.options' => array('auto_reload' => true)//'cache' => dirname(__DIR__).'/cache', 'strict_variables' => true, )
));




$app->register(new Silex\Provider\UrlGeneratorServiceProvider());

//D�claration des controllers

$app->mount('/', new App\Controller\IndexController\IndexController());

$app->mount('/api', new App\Controller\ApiController\ApiController());



$app->register(new Silex\Provider\HttpFragmentServiceProvider());
$app->register(new Silex\Provider\ServiceControllerServiceProvider());

//echo   __DIR__;
//On lance l'application









$app->run();