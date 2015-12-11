<?php

namespace App\Controller\IndexController;

use App\Module\Mongo\Mongo;
use App\Module\PrepareView\PrepareView;
use Illuminate\Support\Facades\App;
use Silex\Application;
use Silex\ControllerProviderInterface;
use App\Module\Configuration\Configuration;
use Symfony\Component\ClassLoader\ApcClassLoader;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Cookie;

class IndexController implements ControllerProviderInterface{


    private function prepareview(){
        $m = new Mongo();
        $config = $m->MongoObject(array('action'=>'prepareview'));
        return $config;

    }

    private function setuser(Application $app){

        $u =$app['session']->get('user');
        if( !empty($u) ){
            $user =$u;
            unset($u);
        }
        else {
            $user = 0;
        }
        return $user;

    }

    public function explorerv2 (Application $app){
        $user = $this->setuser($app);

        if( $user != 0){
            $p = new PrepareView();
            $config = $p->prepareheaders();
            $data = $p->prepareExplorer();

            //var_dump($data);

            return $app['twig']->render('page/explorerv2_template.twig',array('user'=>$user,'config'=>$config,'data'=>$data ));
        }
        else {
            return $app->redirect('/');
        }

    }
    public function explorer(Application $app){
        $user = $this->setuser($app);

        if( $user != 0){
            $p = new PrepareView();
            $config = $p->prepareheaders();
            $data = $p->prepareExplorer();
            return $app['twig']->render('page/explorer_template.twig',array('user'=>$user,'config'=>$config,'data'=>$data ));
        }
        else {
            return $app->redirect('/');
        }

    }

    public function index(Application $app){
        $config = array();
        if( !empty($app['session']->get('user'))){
            $user = $app['session']->get('user');
           $config = $this->prepareview();
        }

        else {
            $user = 0;
        }

        return $app['twig']->render('page/template_page.twig',array('user'=>$user,'config'=>$config ));

    }
    public function logout(Application $app){
        $app['session']->clear('user');
        return $app->redirect('/');

    }
    public function test(Application $app){
        $p= new PrepareView();
        $result = $p->prepareExplorer();
        return $app['twig']->render('page/json_template.twig',array('json'=>$result));

    }



    public function connectme(Application $app, Request $request){
        $configuration = new Configuration();
        $array = $configuration->getConfig('editor');
        if($request->isMethod('POST')){
            $username = $request->request->get('username');
            $password = $request->request->get('password');


            if(($array['username']==$username) && ($array['password']== $password)){
                $app['session']->set('user',array('username'=>$username));

                $result = array (
                  'message' => true

                );
            }
            else{
                $result =  array (
                    'message' => false

                );
            }




        }
        else {
            $result = array('message'=>false);
        }
        return $app['twig']->render('page/json_template.twig',array('json'=>$result));
    }


    public function connect(Application $app)
    {

        // créer un nouveau controller basé sur la route par défaut
        $index = $app['controllers_factory'];
        $index->match('/', 'App\Controller\IndexController\IndexController::index')->bind('index.index');
        $index->match('/connect', 'App\Controller\IndexController\IndexController::connectme')->bind('connect.index');
        $index->match('/logout', 'App\Controller\IndexController\IndexController::logout')->bind('logout.index');
        $index->match('/explorer', 'App\Controller\IndexController\IndexController::explorerv2')->bind('explorer.index');
        $index->match('/explorerv2', 'App\Controller\IndexController\IndexController::explorerv2')->bind('explorerv2.index');
        $index->match('/test', 'App\Controller\IndexController\IndexController::test');



        return $index;
    }

}