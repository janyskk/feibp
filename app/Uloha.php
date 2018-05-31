<?php
/**
 * Created by PhpStorm.
 * User: JanAlexy
 * Date: 04-May-18
 * Time: 12:19
 */

namespace janyskk\feibp;


class Uloha
{
    public $rolaRozhodcu;
    public $nazov;
    public $pocetRozhodcov;

    /**
     * Uloha constructor.
     * @param $rolaRozhodcu - rola rozhodcu (Rozhodca Stolny Tenis, Rozhodca Futbal Ciarovy atd..)
     * @param $nazov - nazov ulohy
     * @param $pocetRozhodcov - potrebny pocet rozhodcov na danu ulohu
     * @param $podujatieId - identifikator podujatia, ku ktoremu uloha patri
     */
    public function __construct($rolaRozhodcu, $nazov, $pocetRozhodcov,$podujatieId)
    {
        require('config.php');
        $client->issue->create([
            'project_id'=>$podujatieId,
            'subject' => $nazov,
            'custom_fields' => [
              [
                  'id' => 1,
                  'name' => 'typ',
                  'value' => $rolaRozhodcu,
              ],
            ],
            'estimated_hours' => $pocetRozhodcov,
        ]);
        $this->rolaRozhodcu = $rolaRozhodcu;
        $this->nazov = $nazov;
        $this->pocetRozhodcov = $pocetRozhodcov;
    }


}