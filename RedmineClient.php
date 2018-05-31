<?php
/**
 * Created by PhpStorm.
 * User: JanAlexy
 * Date: 14-Apr-18
 * Time: 15:21
 */

namespace janyskk\feibp;
use Yii;
use yii\base\Model;




class RedmineClient extends Model
{

    /**
     * @param $name - nazov podujatia
     * @param null $typ - typ role rozhodcu. defaultne null, v takom pripade sa typ rozhodcu nastavi podla prave prihlaseneho pouzivatela v aplikacii
     * @return array|null - vrati pole pozostavajuce z IDciek uloh daneho podujatia
     */
    public static function getIssuesByProjectName($name, $typ=null){
        require('config.php');
        $id = $client->project->getIdByName($name);
        if($typ==null)
            $typ = Yii::$app->user->identity->typ;

        $project = $client->project->show($id)['project']['identifier'];
        $idu = null;
        $issues = $client->issue->all(['project_id'=>$project])['issues'];
        for ($i=0; $i < sizeof($issues) ; $i++) {
            if ($typ != 'Manager') {

                if ($issues[$i]['project']['id'] == $id && $client->issue->all()['issues'][$i]['custom_fields'][0]['value'] == $typ)
                    $idu = $issues[$i]['id'];

            }elseif($issues[$i]['project']['id'] == $id){
                $idu[] = $issues[$i]['id'];
            }
        }
    return $idu;
    }


    /**
     * @param $name - nazov podujatia
     * @return mixed $issue - vrati ID vseobecnej ulohy podujatia
     */
    public static function getVseobecnaFromProjectName($name){
        require('config.php');
        $id = $client->project->getIdByName($name);
        $projectId = $client->project->show($id)['project']['identifier'];
        $trackerId = $client->tracker->listing()['Turnaj'];
        $issue = $client->issue->all(
        ['project_id'=>$projectId,
            'tracker_ids' => [$trackerId],
            'cf_1' => 'Vseobecna',
        ])['issues'][0];
        return $issue;
    }

    /**
     * @param $name - nazov podujatia
     * @return array|null $idu - pole IDciek vsetkych uloh
     */
    public static function getAllIssuesByProjectName($name){
        require('config.php');
        $id = $client->project->getIdByName($name);
        $idu = null;
        $issues = $client->issue->all()['issues'];
        for ($i=0; $i < sizeof($issues) ; $i++) {
            if ($issues[$i]['project']['id'] == $id)
                $idu[] = $issues[$i]['id'];

        }
        return $idu;
    }

    /**
     * @param $nazov - nazov podujatia
     * @return array $pocet - asociativne pole poctov, kde kluc je ID ulohy
     */
    public static function countOfMembersInIssuesByName($nazov){
        require ('config.php');
        $id = $client->project->getIdByName($nazov);
        $pocet = [];
        $idu = self::getIssuesByProjectName($nazov);
        if(is_array($idu)){
            foreach ($idu as $i) {
                $pocet[$i]=RozhodcaProject::find()->where(['idUloha' => $i])->andwhere(['idProject' => $id])->count();

            }
        }else{
            $pocet[$idu]=RozhodcaProject::find()->where(['idUloha' => $idu])->andwhere(['idProject' => $id])->count();
        }

           return $pocet;
    }
}