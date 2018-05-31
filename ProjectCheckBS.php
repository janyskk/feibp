<?php
/**
 * Created by PhpStorm.
 * User: JanAlexy
 * Date: 05-May-18
 * Time: 16:11
 */

namespace janyskk\feibp;


use yii\base\BootstrapInterface;
use yii\base\Application;

class ProjectCheckBS implements BootstrapInterface
{

    /**
     * metoda ktora sa zavola pocas Bootstrap fazy aplikacie
     * @uses RozhodcaProject::lockProjects()
     */
    public function bootstrap($app)
    {
        $app->on(Application::EVENT_AFTER_ACTION,function(){

           RozhodcaProject::lockProjects();


        });
    }
}