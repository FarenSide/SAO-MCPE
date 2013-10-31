<?php

/* 
__PocketMine Plugin__ 
name=SAO-MCPE
description=SAO kind of environment in PocketMine server!
version=1.0
author=Junyi00, Glitchmaster_PE, 99leonchang, hexdro
class=SAOMCPE
apiversion=10
*/

class SAOMCPE implements Plugin{
    private $api, $server;
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
        $this->server = ServerAPI::request();
    }

    public function init(){
        $this->api->addHandler("player.block.touch", array($this, "preventBreakPlace"), 15);
        $this->api->addHandler("player.death", array($this, "BanPlayer"), 15);
    }

    public function __destruct() {}
    
    public function preventBreakPlace($data, $event) {
        switch ($data['type']) {
            case "break": return false; //denied
            case "place": return false; //denied
        }
    }
    
    public function BanPlayer($data, $event) {
        $username = $data['player']->username;
        $this->api->ban->ban($username); //bye bye loser? :P
    }
    
}