<?php
namespace App\Module\Configuration;
use Silex\Application;

class Configuration {

    private $json;

    public function __construct()
    {
        $file= dirname(__DIR__).'/../../config/config.json';

        $this->json = file_get_contents($file);
    }

    private function getconfigvalue(){


        $r = json_decode($this->json,true);

        //var_dump($r);
        return $r;

    }

    public function getConfig($arg){
        $config = $this->getconfigvalue();

        return $config[$arg];

    }
}