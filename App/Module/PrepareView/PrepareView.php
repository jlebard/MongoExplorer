<?php
namespace App\Module\PrepareView;

use Silex\Application;
use App\Module\Mongo\Mongo;

class PrepareView {


    public function prepareheaders (){
        $m = new Mongo();
        $r = $m->MongoObject(array('action'=>'prepareview'));
        unset($m);
        return $r;


    }

    public function prepareExplorer(){
        $m = new Mongo();
        $r['databases'] = $m->_listdb();

        //var_dump($r);
        foreach($r['databases']['list'] as $database){

            $r['databases'][$database['name']]['collection']=$m->_listCollection($database['name']);
        }

        return $r;
    }






}