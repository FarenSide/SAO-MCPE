<?php

/* 
__PocketMine Plugin__ 
name=SAO-MCPE
description=KillCounter
version=1.0
author=Junyi00, Glitchmaster_PE, 99leonchang, hexdro
class=SAOMCPE
apiversion=9,10
*/

class SAOMCPE implements Plugin{
    private $api,$server;
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
        $this->server = ServerAPI::request();
    }

    public function init(){

    }

    public function __destruct() {}
}