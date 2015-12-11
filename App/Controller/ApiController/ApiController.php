<?php

namespace App\Controller\ApiController;

use App\Module\Mongo\Mongo;
use App\Module\PrepareView\PrepareView;
use Illuminate\Support\Facades\App;
use Silex\Application;
use Silex\ControllerProviderInterface;
use App\Module\Configuration\Configuration;
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;

class ApiController implements ControllerProviderInterface{



    private function mongo(){
        $m = new Mongo();
        return $m;
    }

    private function search($database=null,$collection=null,$search=null){
        $m = $this->mongo();
        if($database == null ){

           return $m->_listdb();
        }
        else{
            if ($collection == null){
                return $m->_listCollection($database);
            }
            else{


                return $m->_listDocuments($database,$collection,$search);
            }
        }

    }
    private function getRights($key){
        $m = new Mongo();
        $array=array(
            'action'=>'search',
            'database'=>'MongoExplorer_API',
            'collection'=>'users',
            'search'=>array(
                'APIKEY'=>$key
            )
        );
        $r=$m->MongoObject($array);



    }

    public function api(Application $app, $action =null,$database=null,$collection=null,Request $request){
        $result = array();
        $allget = $request->query->all();
        $debug = true;
        if($debug == false){

        }

        foreach($allget as $key=> $value){
            if(is_numeric($value)){
                settype($allget[$key],'integer');
            }
        }

        $m = new Mongo();
        //if ($request->isMethod('POST')) {

            switch($action){
                case 'search':
                    $result['message']=true;
                    if(empty($database)){
                        $result['result']=$this->search();
                    }
                    else {
                        if(empty($collection)){
                            $result['result']=$this->search($database);
                        }
                        else{
                            $result['result']=$this->search($database,$collection,$allget);
                        }
                    }

                    break;
                case 'create':
                        $array=$allget;
                        $array['action']='create';
                        $array['database']=$database;
                        $array['collection']=$collection;
                    $result =    $m->MongoObject($array);
                    break;
                case 'drop':
                    $array=$allget;
                    $array['action']='drop';
                    $array['database']=$database;
                    $array['collection']=$collection;
                    $result = $m->MongoObject($array);
                    break;
                case 'insert':
                    //$array=$allget;
                    $array['action']='insert';
                    $array['database']=$database;
                    $array['collection']=$collection;
                    $array['data']=json_encode($allget['data']);
                    $result =   $m->MongoObject($array);


                    break;
                case 'update':
                    //valeur obligatoire;

                    $array['action']='update';
                    $array['database']=$database;
                    $array['collection']=$collection;
                    $array['data']=json_decode($allget['data'],true);

                    //valeur par défaut
                    if(!empty($allget['_id'])) {
                        $array['search']['_id'] = $allget['_id'];
                    }
                    else {
                        $array['search']=json_encode($allget['search']);
                    }



                    if(!empty($allget['options'])){
                        $array['options']=json_decode($allget['options'],true);
                    }
                    else {
                        $array['options']=array();
                    }

                    $result =   $m->MongoObject($array);
                    break;
                case 'delete':
                    $array=$allget;
                    $array['action']='delete';
                    $array['database']=$database;
                    $array['collection']=$collection;

                    if(!empty($allget['_id'])) {
                        $array['search']['_id'] = $allget['_id'];
                    }
                    else {
                        $array['search']=json_encode($allget['search'],true);
                    }


                    $result =   $m->MongoObject($array);
                    break;

                default:
                    $result = array('message' => 'bad action');
            }


        //}
        /**else {
            $result = array('message' => 'false');
        }**/


        return $app['twig']->render('page/json_template.twig',array('json'=>$result));

    }



    public function connect(Application $app)
    {

        // créer un nouveau controller basé sur la route par défaut
        $index = $app['controllers_factory'];
        $index->match("/", 'App\Controller\ApiController\ApiController::api')->bind('index.api');
        $index->match("/{action}", 'App\Controller\ApiController\ApiController::api');
        $index->match("/{action}/{database}", 'App\Controller\ApiController\ApiController::api');
        $index->match("/{action}/{database}/{collection}", 'App\Controller\ApiController\ApiController::api');



        return $index;
    }

}