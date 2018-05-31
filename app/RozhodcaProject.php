<?php

namespace janyskk\feibp;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "RozhodcaProject".
 *
 * @property int $id
 * @property string $rozhodcaLogin
 * @property int $idProject
 * @property int $vzdialenost
 * @property int idUloha
 * @property int idMembership
 * @property int timestamp
 */
class RozhodcaProject extends ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'RozhodcaProject';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['rozhodcaLogin', 'idProject', 'vzdialenost','timestamp'], 'required'],
            [['idProject', 'vzdialenost','idMembership','idUloha','timestamp'], 'integer'],
            [['rozhodcaLogin'], 'string', 'max' => 50],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'rozhodcaLogin' => 'Rozhodca Login',
            'idProject' => 'Id Project',
            'vzdialenost' => 'Vzdialenost',
        ];
    }


    public static function getDb()
    {

        $conn = new \yii\db\Connection([
            'dsn' => 'sqlite:database.db',
            'charset' => 'utf8']);
        return $conn;
    }

    /**
     * funkcia vrati nazvy podujati, na ktore je zadany pouzivatel prihlaseny
     * @param $usr - prihlasovacie meno pouzivatela
     * @return array|ActiveRecord[] - pole nazvov podujati
     */
    public static function getProjectsByUsrName($usr){
        require('config.php');
        $podujatia=[];
        $all = self::find()->where(['rozhodcaLogin' => $usr])->orderBy('idProject')->all();
        foreach ($all as $a){
            $podujatia[]=$client->project->show($a->idProject)['project']['name'];
        }
        return $podujatia;
    }

    /** funkcia ktora vrati informacie o prihlasenych pouzivateloch na ulohu. Vrati casy prihlaseni, prihlasovacie mena
     * prihlasenych pouzivatelov, pocty prihlaseni za posledny rok kazdeho pouzivatela, vzdialenost kazdeho pouzivatela
     * od miesta konanaia podujatia
     * @param $nazov - nazov podujatia
     * @param $idi - ID ulohy
     * @return array - asociativne pole informacii, kde kluce su: timestamps,login,counts,vzdialenosti
     */
    public static function getLoggedUsersToIssue($nazov, $idi){
        require ('config.php');
        $id = $client->project->getIdByName($nazov);
        $rp = self::find()->where(['idUloha' => $idi]) ->andwhere(['idProject' => $id])->orderBy('vzdialenost')->all();
        $usernames=[];
        $counts= [];
        $vzdialenosti= [];
        $timestamps = [];
        for ($i=0; $i<sizeof($rp); $i++){
            $usernames[] = $rp[$i]['rozhodcaLogin'];
            $counts[] = self::find()->where(['rozhodcaLogin'=>$rp[$i]->rozhodcaLogin])->andWhere(['>=','timestamp',time()-strtotime('-1year')])->count();
            $timestamps[]=$rp[$i]['timestamp'];
            $vzdialenosti[]=$rp[$i]['vzdialenost'];

        }

        return ['timestamps'=>$timestamps,'login'=>$usernames,'counts'=>$counts,'vzdialenosti'=>$vzdialenosti];

    }

    /**
     * funkcia ktora kazdeho prihlaseneho pouzivatela ohodnoti, a vrati pole váh
     * @param $rozdel - pole, v ktorom su prihlasovacie mena pouzivatelov, vzdialenosti od podujati, pocty prihlaseni a casy prihlasenia na podujatia, kde klucom je ID ulohy
     * @param $issue - nazov ulohy
     * @return array - vahy(hodnotenia)
     */
    public static function getLinearRegression($rozdel,$issue){
        $vahy=[];
        $cm = 10; //konštanta násobiaca vzdialenosti
        $cp = 100;//konštanta násobiaca počet prihlásení
        $ct = 1;//konštanta násobiaca čas prihlásenia

        for ($i = 0; $i<sizeof($rozdel[$issue]);$i++){
                for ($j = 0; $j< sizeof($rozdel[$issue]['login']);$j++){
                    $vahy[$rozdel[$issue]['login'][$j]]= $rozdel[$issue]['vzdialenosti'][$j]*$cm + $rozdel[$issue]['counts'][$j]*$cp + $rozdel[$issue]['timestamps'][$j]*$ct ;
                }
        }
        return $vahy;
    }


    /**
     * zisti ci je pouzivatel prihlaseny na podujatie
     * @param $nazov - nazov podujatia
     * @param $usr - login pouzivatela
     * @return bool - true ak je , false ak nie je
     */
        public static function isMember($nazov, $usr){
        require('config.php');
        $id = $client->project->getIdByName($nazov);
        return self::find()->where(['idProject' => $id])->andwhere( ['rozhodcaLogin' => $usr])->one() == TRUE;
    }

    /**
     * funkcia, ktora uzamkne moznost prihlasovania sa na projekty .
     * @uses finalize()
     */
    public static function lockProjects(){
        $cas="-6days";
        require ('config.php');
        if(Yii::$app->session['lockP']==null || date('D.M.Y',Yii::$app->session['lockP'])!=date('D.M.Y')){
            $sess = Yii::$app->session;
            $sess->open();
            $sess->set('lockP',time());
            $projects = $client->project->all()['projects'];
            $issues=[];
            for ($i = 0; $i<sizeof($projects);$i++){
                $issues[$projects[$i]['id']] = RedmineClient::getAllIssuesByProjectName($projects[$i]['name']);
            }
            for ($i = 0; $i<sizeof($projects); $i++) {
                $turnaj = RedmineClient::getVseobecnaFromProjectName($projects[$i]['name']);
                if (strtotime($turnaj['start_date'] . $cas) <= time()) {
                    $client->issue->setIssueStatus($turnaj['id'], 'Resolved');
                    for ($j=0; $j<sizeof($issues[$projects[$i]['id']]);$j++){
                        $issue = $client->issue->show($issues[$projects[$i]['id']][$j])['issue'];
                        if ($turnaj['id']!=$issues[$projects[$i]['id']][$j] && $issue['status']['name']!='Resolved')
                        {

                            $client->issue->setIssueStatus($issue['id'], 'Resolved');
                            RozhodcaProject::finalize($projects[$i]['id'],$issue['id']);
                        }
                    }

                }
            }

        }

    }

    /**
     * funkcia ktora odhlasi nadbytocnych pouzivatelov v podujati v konkretnej ulohe
     * @param $idP - ID podujatia v rozhrani Redmine
     * @param $idi - ID ulohy
     */
    public static function finalize($idP, $idi){
        require('config.php');
            $issue = $client->issue->show($idi)['issue'];
            $name = $client->project->show($idP)['project']['name'];
            $x = $issue['estimated_hours'];
            $getLoggedUsersToIssue = self::getLoggedUsersToIssue($name,$idi);
            $rozdel[$idi]['timestamps'] = $getLoggedUsersToIssue['timestamps'];
            $rozdel[$idi]['login'] = $getLoggedUsersToIssue['login'];
            $rozdel[$idi]['pocet_prihlaseni'] = $getLoggedUsersToIssue['counts'];
            $rozdel[$idi]['vzdialenosti'] = $getLoggedUsersToIssue['vzdialenosti'];
            $vahy[$idi] = self::getLinearRegression($rozdel,$idi);
            array_multisort($vahy[$idi],$rozdel[$idi]['timestamps'],$rozdel[$idi]['login'],$rozdel[$idi]['pocet_prihlaseni'],$rozdel[$idi]['vzdialenosti']);


        if($x<sizeof($rozdel[$idi]['login'])){
            for($j=$x;$j<sizeof($rozdel[$idi]['login']);$j++){
              Podujatie::odhlasit($name,$idi,$rozdel[$idi]['login'][$j]);
            }
        }else{
            $client->project->update($name,[
                'is_public' => false,
            ]);
        }


    }


    /**
     * @param $name - nazov podujatia
     * @return Podujatie|null|string - objekt typu Podujatie
     * @uses RedmineClient::getAllIssuesByProjectName()
     * @uses RedmineClient::getVseobecnaFromProjectName()
     */
    public static function getPodujatieByName($name){
        require ('config.php');
        $p = null;
        $ulohy=[];
            $ulohyRed= RedmineClient::getAllIssuesByProjectName($name);
            $pod = $client->project->show($client->project->getIdByName($name));
            if($pod !='Syntax error'){

                $pod = $pod['project'];
            }else{
                return "Podujatie s tymto nazvom neexistuje!";
            }
            $vseobecna = RedmineClient::getVseobecnaFromProjectName($name);
            for($i =0;$i<sizeof($ulohyRed);$i++){
                $u = $client->issue->show($ulohyRed[$i])['issue'];
                if($u['id']!=$vseobecna['id'])
                    $ulohy[]=['typ'=>$u['custom_fields'][0]['value'],'nazov'=>$u['subject'],'pocet'=>$u['estimated_hours']];
            }
            $p = new Podujatie($pod['name'],$vseobecna['start_date'],$pod['description'],$pod['homepage']);
            $p->setUlohy($ulohy);
        return $p;
    }


    /**
     * funkcia, ktra zo vstupu (podujatie, ulohy, pouzivatelia) prihlasi pouzivatelov na podujatia na vhodne ulohy
     * a vrati zoradene pole, ktoreho klucom je nazov ulohy a data obsahuju casy prihlasenia na podujatie, pocty prihlaseni
     * pouzivatelov za posledny rok, prihlasovacie mena pouzivatelov, vzdialenosti pouzivatelov od miesta konania podujatia
     * @param $podujatie - objekt typu Podujatie
     * @param $ulohy - asociativne pole uloh
     * @param $users - pole s udajmi o pouzivateloch
     * @return array - zoradene asociativne pole s udajmi o prihlasenych pouzivateloch
     * @throws \Throwable
     * @uses Podujatie::setUlohy()
     * @uses RedmineClient::getIssuesByProjectName()
     * @uses Podujatie::prihlasit()
     * @uses RedmineClient getLoggedUsersToIssue()
     * @uses RozhodcaProject::getLinearRegression()
     */
    public static function getRozdelenie($podujatie, $ulohy, $users){
        require ('config.php');

        $podujatie->setUlohy($ulohy);
        foreach ($users as $u){
            if(!$client->user->getIdByUsername($u['login']))
                trigger_error("User ".$u['login']." does not exist in Redmine. Create him by calling Podujatie::createRedminceUser() function.", E_USER_ERROR);
            $issue = RedmineClient::getIssuesByProjectName($podujatie->getNazov(),$u['typ']);
            Podujatie::prihlasit($podujatie->getNazov(),$issue,$u);

        }
        $ulohy = $podujatie->getUlohy($podujatie->getNazov());
        $rozdel = [];
        foreach ($ulohy as $u){
            $issue = RedmineClient::getIssuesByProjectName($podujatie->getNazov(),$u['typ']);
            $getLoggerUsersToIssue = self::getLoggedUsersToIssue($podujatie->getNazov(),$issue);
            $rozdel[$u['id']]['timestamps'] = $getLoggerUsersToIssue['timestamps'];
            $rozdel[$u['id']]['login'] = $getLoggerUsersToIssue['login'];
            $rozdel[$u['id']]['counts'] = $getLoggerUsersToIssue['counts'];
            $rozdel[$u['id']]['vzdialenosti'] = $getLoggerUsersToIssue['vzdialenosti'];


            $vahy[$u['id']] = self::getLinearRegression($rozdel,$u['id']);
            array_multisort($vahy[$u['id']],$rozdel[$u['id']]['timestamps'],$rozdel[$u['id']]['login'],$rozdel[$u['id']]['pocet_prihlaseni'],$rozdel[$u['id']]['vzdialenosti']);
             //
        }
        return $rozdel;

    }
}
