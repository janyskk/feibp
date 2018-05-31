<?php
/**
 * Created by PhpStorm.
 * User: JanAlexy
 * Date: 04-May-18
 * Time: 12:16
 *
 * Trieda Podujatie. Reprezentuje sportove podujatie.
 */

namespace janyskk\feibp;


use Yii;
use yii\base\Model;

class Podujatie extends Model
{
    public $nazov;
    public $datum_konania;
    public $ulohy;
    public $miesto_konania;
    public $podujateieId;
    public $popis;


    /**
     * Podujatie constructor. - Vytvori objekt Podujatie. Zaroven vytvori podujatie v prostredi Redmine a spolu s nim aj jeho vseobecnu ulohu.
     * @param $nazov - nazov podujatia
     * @param $datum_konania - datum konania podujatia v tvare dd.mm.rrrr
     * @param $popis - popis podujatia
     * @param $miesto_konania - miesto (Mesto) konania podujatia
     */
    public function __construct($nazov, $datum_konania, $popis, $miesto_konania)
    {
        require('config.php');
        parent::__construct();
        $this->nazov = $nazov;
        $this->datum_konania = strtotime($datum_konania);
        $this->miesto_konania = $miesto_konania;
        $this->podujateieId = strtolower(str_replace(" ","_",$nazov));
        $this->popis = $popis;
        $client->project->create([
            'name' => $nazov,
            'homepage' => $miesto_konania,
            'identifier' => $this->podujateieId,
            'description' => $popis,
            'tracker_ids' => [$client->tracker->listing()['Rozhodca']],
        ]);
        if(sizeof($client->issue->all(['project_id' => $this->podujateieId])['issues'])==0)
        {

            $client->issue->create([
                'project_id'=>$this->podujateieId,
                'subject' => 'Vseobecna',
                'tracker_id' => $client->tracker->listing()['Turnaj'],
                'start_date' =>  date("Y-m-d",$datum_konania),
                'custom_fields' => [
                    [
                        'id' => 1,
                        'name' => 'typ',
                        'value' => 'Vseobecna',
                    ],
                ],
            ]);
        }
    }



    /**
     * @return mixed
     */
    public function getNazov()
    {
        return $this->nazov;
    }

    /**
     * @return mixed
     */
    public function getDatumKonania()
    {
        return $this->datum_konania;
    }


    /**
     * @return mixed
     */
    public function getMiestoKonania()
    {
        return $this->miesto_konania;
    }

    /**
     * @return string
     */
    public function getPodujateieId()
    {
        return $this->podujateieId;
    }

    /**
     * @return mixed
     */
    public function getPopis()
    {
        return $this->popis;
    }

    /**
     * Funkcia nie len nastavi premennu $ulohy objektu Podujaite, ale vytvori aj ulohy v prostredi Redmine.
     * @param $ulohy - asociativne pole uloh, ktore ma kluce [typ, nazov, pocet]
     * @example setUlohy(['typ'=>'Rozhodca Stolny Tenis','nazov'=>'Hlavny Rozhodcovia','pocet'=>'1']);
     */
    public function setUlohy($ulohy){
        require ('config.php');
        for($i=0;$i<sizeof($ulohy);$i++){
            for($j=0;$j<sizeof($client->issue->all(['project_id' => $this->podujateieId])['issues']);$j++){
                if($client->issue->all(['project_id' => $this->podujateieId])['issues'][$j]['custom_fields'][0]['value']==$ulohy[$i]['typ']){
                    break;
                }
            }
            if($j==sizeof($client->issue->all(['project_id' => $this->podujateieId])['issues']))
                    $this->ulohy[] = new Uloha($ulohy[$i]['typ'],$ulohy[$i]['nazov'],$ulohy[$i]['pocet'],$this->podujateieId);
        }

    }


    /**funkcia ktora vrati vsetky podujatia.
     * @see RedmineClient::getVseobecnaFromProjectName()
     * @return array - asociativne pole podujati
     * @uses RedmineClient::getVseobecnaFromProjectName()
     */
    public static function getPodujatia(){
        require('config.php');
        $projects = $client->project->all()['projects'];
        $podujatia=[];
        for($i = 0; $i<sizeof($projects);$i++){
            $pod = $client->project->show($projects[$i]['id'])['project'];
            $turnaj= RedmineClient::getVseobecnaFromProjectName($pod['name']);
            $podujatia[$i]['id'] = $pod['id'];
            $podujatia[$i]['nazov'] = $pod['name'];
            $podujatia[$i]['datum_konania'] = $turnaj['start_date'];
            $podujatia[$i]['popis'] =$pod['description'];
            $podujatia[$i]['miesto_konania'] = $pod['homepage'];
        }

        return $podujatia;
    }


    /**
     * Podla zadaneho podujatia, vrati vsetky jeho ulohy
     * @param $nazov - nazov podujatia
     * @return array - vrati asociativne pole vsetkych uloh podujatia
     * @uses RedmineClient::getAllIssuesByProjectName()
     */
    public static function getUlohy($nazov){
        require('config.php');
        $ulohy=null;
        $idu=RedmineClient::getAllIssuesByProjectName($nazov);
        for($i=0;$i<sizeof($idu);$i++){
            $issue = $client->issue->show($idu[$i])['issue'];
            $ulohy[$i]['id'] = $issue['id'];
            $ulohy[$i]['nazov'] = $issue['subject'];
            $ulohy[$i]['datum_konania'] = $issue['start_date'];
            $ulohy[$i]['typ'] = $issue['custom_fields'][0]['value'];
        }

        return  $ulohy;
    }

    /**
     * Vytvori pouzivatela v prostredi Redmine, co je potrebne pre dalsie fungovanie modulu.
     * @param $name - meno pouzivatela
     * @param $surname - priezvisko pouzivatela
     * @param $login - prihlasovacie meno
     * @param $email - email
     * @param $role - rola pouzivatela(napr. Rozhodca Stolny Tenis, Rozhodca Futbal Hlavny atd.)
     */
    public static function createRedmineUser($name, $surname, $login, $email, $role){
        require('config.php');
        $client->user->create([
                'login' => $login,
                'firstname' => $name,
                'lastname' => $surname,
                'mail' => $email,
                'custom_fields'=>[
                    [
                        'id' =>2,
                        'name' => 'role',
                        'value'=> $role,
                    ],
                ],
            ]);
            sleep(0);
    }

    /**
     * Prihlasi to pouzivatela na podujatie na konkretnu ulohu. Tato funkcia zaroven vytvori zaznam v tabulke RozhodcaProject v database prilozenej k tomuto modulu.
     * Vyuziva tie Google Maps Api na zistenie vzdialenosti pouzivatela od miesta konania podujatia.
     * @param $name - nazov podujatia
     * @param $idi - ID Ulohy. Treba ziskat z inej funkcie
     * @param $user - defaultne je null. Asociativne pole s pouzivatelom, kde kluce su login,rola,bydlisko.
     * @throws \Throwable - ak pouzivatel neexistuje vrati chybovu hlasku.
     * @see RedmineClient::getAllIssuesByProjectName()
     * @see RedmineClient::getIssuesByProjectName()
     * @uses RedmineClient::countOfMembersInIssuesByName()
     * @uses https://developers.google.com/maps/documentation/directions/intro
     */
    public static function prihlasit($name, $idi, $user=null){
        require('config.php');
        if($user==null){
            $user['login'] = Yii::$app->user->identity->login;
            $user['typ'] = Yii::$app->user->identity->typ;
            $user['bydlisko']= Yii::$app->user->identity->bydlisko;
        }
        if(!$userid =$client->user->getIdByUsername($user['login']))
            trigger_error("User ".$user." does not exist in Redmine. Create him by calling Podujatie::createRedminceUser() function", E_USER_ERROR);

        $id = $client->project->getIdByName($name);


        $project = $client->project->show($id)['project'];
        $issue = $client->issue->show($idi)['issue'];
        $pocet=RedmineClient::countOfMembersInIssuesByName($name)[$idi];
        $rozhodca_project = new RozhodcaProject();
        if (isset($user) && !($rozhodca_project->find()->where(['rozhodcaLogin' => $user['login'], 'idProject' => $rozhodca_project->idProject = $project['id']])->exists()) && !self::isLocked($name)) {
            $from = str_replace(" ", "%20", $user['bydlisko']);
            $to = str_replace(" ", "%20", $project['homepage']);
            $vzdialenost = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=".$from."&destination=".$to."&mode=walking&key=AIzaSyAMFQEvZ78Jf0skEGr_O8boUhZTmBnp_9E"));
            $hodnota = $vzdialenost->routes[0]->legs[0]->distance->value;


            if ($issue['custom_fields'][0]['value']==$user['typ']) {
                $client->membership->create($id, [
                    'user_id' => $userid,
                    'role_ids' => [$client->role->listing()['Rozhodca']],
                ]);
                for ($i= 0; $i< sizeof($client->user->show($client->user->getIdByUsername($user['login']),['include'=>['memberships']])['user']['memberships']);$i++){
                    if($client->user->show($client->user->getIdByUsername($user['login']),['include'=>['memberships']])['user']['memberships'][$i]['project']['id'] == $id){
                        $rozhodca_project->idMembership = $client->user->show($client->user->getIdByUsername($user['login']),['include'=>['memberships']])['user']['memberships'][$i]['id'];
                    }
                }
                $rozhodca_project->id = null;
                $rozhodca_project->rozhodcaLogin = $user['login'];
                $rozhodca_project->idProject = $project['id'];
                $rozhodca_project->vzdialenost = $hodnota;
                $rozhodca_project->idUloha = $idi;
                $rozhodca_project->timestamp = time();
                $rozhodca_project->insert();

                if ($pocet<=$issue['estimated_hours']){
                    $done = $issue['done_ratio'];

                    $ciel =$issue['estimated_hours'];

                    $done += round((1 / $ciel)*100);

                    $client->issue->update($issue['id'],[
                        'done_ratio' => $done,
                    ]);
                }else{
                    $client->issue->update($issue['id'],[
                        'done_ratio' => 100,
                    ]);
                }
            }
        }else{
            trigger_error("Project is Locked!", E_USER_ERROR);

        }

    }

    /**
     * Odhlasi pouzivatela z podujatia, z konkretnej ulohy
     * @param $name - nazov podujatia
     * @param $idi - ID Ulohy, ktoru si treba ziskat z inej funkcie modulu
     * @param $usr - prihlasovacie meno pouzivatela, ktoreho chceme odhlasit
     * @uses RedmineClient::countOfMembersInIssuesByName()
     */
    public static function odhlasit($name, $idi, $usr){
        require('config.php');
        $id = $client->project->getIdByName($name);
        $pocty=RedmineClient::countOfMembersInIssuesByName($name)[$idi];
        $rozhodca_project = new RozhodcaProject();
        $issue = $client->issue->show($idi)['issue'];
        if($pocty-1<$issue['estimated_hours']){
            $vysledok = (($pocty-1)/$issue['estimated_hours'])*100;
            $client->issue->update($issue['id'],[
                'done_ratio' => $vysledok,
            ]);
        }else{
            $client->issue->update($issue['id'],[
                'done_ratio' => 100,
            ]);
        }

        $rozhodca_project->find()->where(['idProject' => $id])->andwhere( ['rozhodcaLogin' => $usr])->one()->delete();
        for ($i= 0; $i< sizeof($client->user->show($client->user->getIdByUsername($usr),['include'=>['memberships']])['user']['memberships']);$i++){
            if($client->user->show($client->user->getIdByUsername($usr),['include'=>['memberships']])['user']['memberships'][$i]['project']['id'] == $id){
                $client->membership->remove($client->user->show($client->user->getIdByUsername($usr),['include'=>['memberships']])['user']['memberships'][$i]['id']);
            }
        }
    }

    /**
     * @param $nazov
     */
    public static function isLocked($nazov){
        require('config.php');
        $issue = RedmineClient::getVseobecnaFromProjectName($nazov);
        return $issue['status']['name']=='Resolved';
    }
}