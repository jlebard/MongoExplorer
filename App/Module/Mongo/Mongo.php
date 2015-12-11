<?php

namespace App\Module\Mongo;

use Silex\Application;
use App\Module\Configuration\Configuration;

class Mongo {



    private function MongoID($id){
        $mongoID = new \MongoId($id);
        return $mongoID;
    }

    private function MongoClient(){
        $m = new \MongoClient();
        return $m;

    }

    private function createDB($name){
        $m=$this->MongoClient();
        $db = $m->$name;
        //unset($m);
        return $db;
    }

    private function insertintoCollection($DB,$collection,$data){
        $col=$this->selectCollection($DB,$collection);
        $r = $col->insert($data);
        unset($col);
        return $r;
    }

    private function updateintoCollection($DB,$collection,$search,$data,$options=array()){
        $col=$this->selectCollection($DB,$collection);
            if(!empty($search['_id'])){
                $search['_id']=$this->MongoID($search['_id']);
            }

        $r = $col->update($search,array('$set'=>$data),$options);
        unset($col);
        return $r;

    }

    private function createCollection($DB,$collection,$options){
        $db = $this->selectDB($DB);
        $r = $db->createCollection($collection,$options);
        unset($db);
        return $r;
    }

    private function deleteDocuments($DB,$collection,$search){
        $col = $this->selectCollection($DB,$collection);
        if(!empty($search['_id'])){
            $search['_id']=$this->MongoID($search['_id']);
        }
        $r = $col->remove($search);
        unset($col);
        return $r;
    }

    private function deleteCollection($DB,$collection){
        $col = $this->selectCollection($DB,$collection);
        $r = $col->drop();
        return $r;
    }

    private function deletedatabase($DB){
        $db = $this->selectDB($DB);
        $r = $db->drop();
        unset($db);
        return $r;
    }
    private function listDB(){
        $m = $this->MongoClient();
        return $m->listDBs();

    }

    private function selectDB($DB){
        $m= $this->MongoClient();
        $db = $m->selectDB($DB);
        return $db;

    }
    private function listCollection($DB){
        $db = $this->selectDB($DB);
        $collection = $db->getCollectionNames();
        return $collection;

    }
    private function selectCollection($DB,$collection){
        $db=$this->selectDB($DB);
        $col = new \MongoCollection($db,$collection);
        return $col;

    }




    private function countCollection($DB,$collection){
        $col = $this->selectCollection($DB,$collection);
        $r = $col->count();
        return $r;
    }
    private function search($DB,$collection,$search){
        $col = $this->selectCollection($DB,$collection);


        if(empty($search)){

            $r = $col->find(); //search all


        }
        elseif(empty($search['_id'])){
            $r = $col->find($search); //search without MongoID _id
        }
        elseif((!empty($search['_id']))&& empty($search['_id']['$id'])){
            $id = $this->MongoID($search['_id']);
            $search['_id']=$id;
            $r = $col->find($search);


        }
        else{
            $r = $col->find(array('_id'=>$this->MongoID($search['_id']['$id']))) ; //search with MongoID _id
        }

        $a=0;
        $result=array();

        foreach ( $r as $id => $value )
        {
            $result[$a]=$value;
            $a++;
        }



        return $result;

    }

    private function prepareview (){
        $db = $this->listDB();


        $r = array(
            'databases' => array (
                'count'=>count($db['databases']),
                'list'=>$db['databases'],
                'resultMongo'=>$db
            )


        );

        foreach ($db['databases'] as $array){
            //var_dump($array);
            $collectionList = $this->listCollection($array['name']);
            $r['databases'][$array['name']]['count']=count($collectionList);

            foreach($collectionList as $collection ) {
                $r['databases'][$array['name']]['list'][$collection]['count']=$this->countCollection($array['name'],$collection);
            }

            $r['databases'][$array['name']]['resultMongo']=$collectionList;
        }
        //var_dump($r);


        return $r;
    }


    public function _listdb(){
        $db = $this->listDB();
        $r = array('count'=>count($db['databases']),'list'=>$db['databases']);
        foreach($db['databases'] as $database){

            $collectionList = $this->listCollection($database['name']);

            $r[$database['name']]['count']=count($collectionList);
        }

        return $r;
    }
    public function _listCollection($DB){
        $collections = $this->listCollection($DB);
        $r['count']=count($collections);
        $r['list']=$collections;
        foreach($collections as $collection){
            $r[$collection]['name']=$collection;
            $r[$collection]['count']=$this->countCollection($DB,$collection);
        }
        return $r;
    }
    public function _listDocuments($DB,$col,$search=array()){
        $doc = $this->search($DB,$col,$search);
        $r['count']= count($doc);
        $r['list']=$doc;
        unset($doc);
        return $r;
    }

    public function MongoObject($options = array()) {
        $r['message']=true;

        switch($options['action']){
            case 'prepareview':
                $r = $this->prepareview();


                break;
            case 'search':
                $r['result'] = $this->_listDocuments($options['database'],$options['collection'],$options['search']);
                break;
            case 'create':
                if((!empty($options['database']))&& (!empty($options['collection'])) ){
                    $r['result'] = $this->createCollection($options['database'],$options['collection'],$options['options']);
                }
                elseif((!empty($options['database']))&& (empty($options['collection']))){
                    $r['result'] = $this->createDB($options['database']);
                }
                else {
                    $r= array('message'=>'error','result'=>'no %database% or/and no %collection% too create!');
                }

                break;
            case 'drop':
                if($options['database']=='local'){
                    $r= array('message'=>'error','result'=>'FORBIDDEN DROP! %system% DB');
                }
                elseif((!empty($options['database']))&& (!empty($options['collection'])) ){
                    $r['result']= $this->deleteCollection($options['database'],$options['collection']);
                }
                elseif((!empty($options['database']))&& (empty($options['collection']))){
                    $r['result'] = $this->deletedatabase($options['database']);
                }

                else {
                    $r= array('message'=>'error','result'=>'no %database% or/and no %collection% too drop!');
                }


                break;
            case 'insert':
                    if(!empty($options['data'])) {
                        $r['result'] = $this->insertintoCollection($options['database'], $options['collection'], $options['data']);
                    }
                    else {
                        $r= array('message'=>'error','result'=>'no DATA to insert');
                    }
                break;
            case 'update':
                if(!empty($options['data'])){
                    $no=0;
                    $search=array();
                    if(is_array($options['data']['_id'])){
                        $search['_id'] = $this->MongoID($options['data']['_id']['$id']);
                        unset($options['data']['_id']);
                    }
                    elseif(!empty($options['search']['_id'])){
                        $search['_id']= $this->MongoID($options['search']['_id']);
                    }
                    elseif(!empty($options['search'])) {
                        $search=$options['search'];
                    }
                    else {
                        $no=1;
                    }
                    if($no == 0) {
                        $r['result'] = $this->updateintoCollection($options['database'], $options['collection'], $search, $options['data'],$options['options']);
                    }
                    else {
                        $r= array('message'=>'error','result'=>'no %search% to update');
                    }

                }
                else {
                    $r= array('message'=>'error','result'=>'no DATA to update');
                }


                break;
            case 'delete':
                if(!empty($options['search'])){
                    $b=0;
                    if(!empty($options['search']['_id'])){
                       $s= $this->_listDocuments($options['database'],$options['collection'],$options['search']);
                        if($s['count']==0 ){
                            $b =0;
                        }
                        else {
                            $b =1;
                            $search['_id']= $this->MongoID($options['search']['_id']);
                        }



                    }
                    else {
                        $s= $this->_listDocuments($options['database'],$options['collection'],$options['search']);

                        if($s['count']==0 ){
                            $b =0;
                        }
                        else {
                            $b =1;
                            $search=$options['search'];
                        }





                    }
                    if($b==1) {

                        $r['result'] = $this->deleteDocuments($options['database'], $options['collection'], $search);
                    }
                    else {
                        $r= array('message'=>'error','result'=>'no result to delete with your %search%');
                    }
                }
                else{
                    $r= array('message'=>'error','result'=>'no %search% to delete');
                }

                break;
            default:
                $r = array('message'=>'error');
        }
        return $r;

    }

}