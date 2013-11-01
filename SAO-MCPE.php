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
    const DEFAULT_MONEY = 0;
    const DEFAULT_SKILL = 1;
    public function __construct(ServerAPI $api, $server = false){
        $this->api = $api;
        $this->server = ServerAPI::request(); //why do we need this :P
        //Its needed
        //hexdro just contributed :P
        //?? -Leon
    }

    public function init(){
        $this->api->addHandler("player.block.touch", array($this, "preventBreakPlace"), 15);
        
        $this->api->addHandler("player.death", array($this, "BanPlayer"), 15); //Banning is awesome -Junyi00

        $this->api->addHandler("player.join", array($this, "register")); //Adds the player stuff to the config files
        $this->api->addHandler("money.player.get", array($this, "register")); //API to get player money

        $this->api->addHandler("player.block.touch", array($this, "storeManager")); //what about the above code ^ -Junyi00
        $this->api->addHandler("tile.update", array($this, "storeManager"));
        //This code is from my server and I really want it to be kept secret but oh well :/ -Leon
        
        $this->api->console->register("cash","Having to deal with SAO-MCPE Economy",array($this, "Economy"));
        $this->api->ban->cmdWhitelist("cash");
        
        $this->path = $this->api->plugin->configPath($this);

        $this->cash = new Config($this->api->plugin->configPath($this)."Economy.yml", CONFIG_YAML);
        /*
        $this->cash = new Config($this->path . "Economy.yml", CONFIG_YAML, for($i = 1, $i < 1,000,000,000, $i++){
            $i, "User" => "", "Money", "";//this config should do a for loop for each new member that joins -Glitch
        }) //don't understand how the loop will work O.o
        */
        //This is an illegal piece of code i think and will result in a parsing error. I have used a different method --Leon
        //Same as what i use with my plugins, idk how the above code works at all
        $this->cash = $this->api->plugin->readYAML($this->path . "Economy.yml");//Makes it read YAML :P

        $this->DetectSkill = new Config($this->api->plugin->configPath($this)."DetectionSkill.yml", CONFIG_YAML);
        $this->DetectSkill = $this->api->plugin->readYAML($this->path . "DetectionSkill.yml");//someone forgot semicolon :P -Leon
        
        $this->api->schedule(20* 20, array($this, "Healing"), array(), false); //20 secs to heal 1 heatlh
    }
    //Shouldn't we be using storing data using SQL? There are othe stuffs to store too, afraid sing so much yaml would lag the server-Junyi00
    //SQL is actually not a very good protocol. It is better than YAML, but if we can create a good YAML file, or a file for each player, the lag will be virtually nonexistent -Leon

    public function __destruct() {}

    public function register($data,$event){
        switch($event){
            case "player.join":
                //Better way to write player stuff to yaml
                $target = $data->username;
                if (!$this->cash->exists($target)) {
                    $this->cash->set($target, array('money' => self::DEFAULT_MONEY));
                    if(self::DEFAULT_MONEY !== 0){
                        $data->sendChat("[SAO]You have received self::DEFAULT_MONEY coins");
                        //Not sure if it works
                    }
                }
                $this->config->save();
                if (!$this->DetectSkills->exists($target)) {
                    $this->DetectSkills->set($target, array('SkillLevel' => self::DEFAULT_SKILL, 'Count' => 0));
                    if(self::DEFAULT_SKILL !== 0){
                        $data->sendChat("[SAO]You have received self::DEFAULT_SKILL detection skill points");
                        //Not sure if it works
                    }
                }
                $this->config->save();
                break;
        }
    }

    public function preventBreakPlace($data, $event) {
        switch ($data['type']) {
            case "break": return false; //denied
            case "place": return false; //denied
        }
    }//I like how you guys are adding a lot of commentts, I will do the same -Glitch
    
    public function BanPlayer($data, $event) {
        $username = $data['player']->username;
        $this->api->ban->ban($username); //bye bye loser? :P
    }
    
    public function Economy($cmd, $args, $issuer){
        $username = $issuer->username;
        $money = $this->cash["Money"];//this is incomplete, I will keep working on it in a bit -Glitch
    }
    
    public function Healing() {
        $players = $this->api->player->online();
        for($i=1;$i<count($players);$i++) {
            $player = $this->api->player->get($players[$i]);
            if ($player->getHealth() != 20) { 
                $player->setHealth($player->getHealth()+1, "Healing"); //heal 1 health
            }
        }
    } //Done? Someone test for me plz :P -Junyi00

    //Below is my awesome store thingy, it also only allows admins to create shops
    //This also means that permissions plus is a necessity
    //Even comes with a VIP thingy
    // --Leon
    public function countItemInventory($player, $type){
        //Checks if player has enough of an item/block
        $count = 0;
        foreach($player->inventory as $item){
            if($item->getID() === $type){
                $count = $count + $item->count;
            }
        }
        return $count;
    }

    public function storeManager(&$data, $event){
        switch ($event) {
            case "tile.update":
                if ($data->class === TILE_SIGN) {
                    $usrname = $data->data['creator'];
                    $user_permission = $this->api->dhandle("get.player.permission", $usrname);
                    if ($data->data['Text1'] == "[VIP]"){
                        if ($user_permission !== "ADMIN") {
                            $data->data['Text1'] = "[BROKEN]";
                            $this->api->chat->sendTo(false, "[SAO]Only admins can create shops!", $usrname);
                            return false;
                        }
                        else{
                            return true;
                        }
                    }
                    if ($data->data['Text1'] == "[SHOP]"){
                        if ($user_permission !== "ADMIN") {
                            $data->data['Text1'] = "[BROKEN]";
                            $this->api->chat->sendTo(false, "[SAO]Only admins can create shops!", $usrname);
                            return false;
                        }
                        else{
                            return true;
                        }
                    }
                    if ($data->data['Text1'] == "[SELL]"){
                        if ($user_permission !== "ADMIN") {
                            $data->data['Text1'] = "[BROKEN]";
                            $this->api->chat->sendTo(false, "[SAO]Only admins can create shops!", $usrname);
                            return false;
                        }
                        else{
                            return true;
                        }
                    }
                    if ($data->data['Text1'] == "[WORLD]"){
                        //This only works with my multiworld portal plugin -Leon
                        if ($user_permission !== "ADMIN") {
                            $data->data['Text1'] = "[BROKEN]";
                            $this->api->chat->sendTo(false, "[SAO]Only admins can create portals!", $usrname);
                            return false;
                        }
                        else{
                            return true;
                        }
                    }
                }
                break;
            case "player.block.touch":
                $tile = $this->api->tile->get(new Position($data['target']->x, $data['target']->y, $data['target']->z, $data['target']->level));
                if ($tile === false) break;
                $class = $tile->class;
                switch ($class) {
                    case TILE_SIGN:
                        switch ($data['type']) {
                            case "place":
                                if ($tile->data['Text1'] == "[VIP]") {
                                    $usrname = $data["player"]->username;
                                    $user_permission = $this->api->dhandle("get.player.permission", $usrname);
                                    $cost = $tile->data['Text4'];
                                    $item = $tile->data['Text2'];
                                    $amount = $tile->data['Text3'];
                                    if ($user_permission != "VIP") {
                                        $this->api->chat->sendTo(false, "[SAO]You are not VIP!", $usrname);
                                        return false;
                                    }
                                    else {
                                        $money = $this->config->get($usrname)['money'];
                                        if ($money < $cost) {
                                            $this->api->chat->sendTo(false, "[SAO]You don't have enough coins!", $usrname);
                                        }
                                        else {
                                            $leftovermoney = $this->config->get($usrname)['money'] - $cost;
                                            $this->config->set($usrname, array('money' => $leftovermoney));
                                            $this -> api -> console -> run("give " . $usrname . " " . $item." ".$amount);
                                            $this->config->save();
                                            $this->api->chat->sendTo(false, "[SAO]You just bought $amount $item!", $usrname);
                                        }
                                    }
                                }
                                if ($tile->data['Text1'] == "[STORE]") {
                                    $usrname = $data["player"]->username;
                                    $user_permission = $this->api->dhandle("get.player.permission", $usrname);
                                    $cost = $tile->data['Text4'];
                                    $item = $tile->data['Text2'];
                                    $amount = $tile->data['Text3'];
                                    $money = $this->config->get($usrname)['money'];
                                    if ($money < $cost) {
                                        $this->api->chat->sendTo(false, "[SAO]You don't have enough coins!", $usrname);
                                    }
                                    else {
                                        $leftovermoney = $this->config->get($usrname)['money'] - $cost;
                                        $this->config->set($usrname, array('money' => $leftovermoney));
                                        $this -> api -> console -> run("give " . $usrname . " " . $item." ".$amount);
                                        $this->config->save();
                                        $this->api->chat->sendTo(false, "[SAO]You just bought $amount $item!", $usrname);
                                    }
                                }
                                if ($tile->data['Text1'] == "[SELL]") {
                                    $player = $data['player'];
                                    $usrname = $data["player"]->username;
                                    $user_permission = $this->api->dhandle("get.player.permission", $usrname);
                                    $cost = $tile->data['Text4'];
                                    $itemtype = $tile->data['Text2'];
                                    $item = $this->isItem($itemtype);
                                    $amount = $tile->data['Text3'];

                                    $inv = $this->countItemInventory($player, $item);
                                    if ($inv < $amount) {
                                        $this->api->chat->sendTo(false, "[SAO]You don't have enough $itemtype!", $usrname);
                                    }
                                    else {
                                        $damage = 0;
                                        $extramoney = $this->config->get($usrname)['money'] + $cost;
                                        $this->config->set($usrname, array('money' => $extramoney));
                                        $player->removeItem($item, $damage, $amount, $send = true);
                                        $this->config->save();
                                        $this->api->chat->sendTo(false, "[SAO]You just sold $amount of $itemtype!", $usrname);
                                        $this->api->chat->sendTo(false, "[SAO]You just received $cost coins!", $usrname);
                                    }
                                }
                        }
                        break;
                }
                break;
        }
    }

    public function isItem($item){
        //For parsing word names to numbers
        $tmp = strtolower($item);
        if (isset($this->blocks[$tmp])) return $item;
        if (isset($this->items[$tmp])) return $item;
        if (($id = array_search($tmp, $this->blocks)) !== false) return $id;
        if (($id = array_search($tmp, $this->items)) !== false) return $id;
        return false;
    }

    //List of items and crap that I stole from ChestShop
    private $blocks = array(
        0 => "air",
        1 => "stone",
        2 => "grass",
        3 => "dirt",
        4 => "cobblestone",
        5 => "woodenplank",
        6 => "sapling",
        7 => "bedrock",
        8 => "water",
        9 => "stationarywater",
        10 => "lava",
        11 => "stationarylava",
        12 => "sand",
        13 => "gravel",
        14 => "goldore",
        15 => "ironore",
        16 => "coalore",
        17 => "wood",
        18 => "leaves",
        19 => "sponge",
        20 => "glass",
        21 => "lapislazuliore",
        22 => "lapislazuliblock",
        23 => "dispenser",
        24 => "sandstone",
        25 => "noteblock",
        26 => "bed",
        27 => "poweredrail",
        28 => "detectorrail",
        29 => "stickypiston",
        30 => "cobweb",
        31 => "tallgrass",
        32 => "deadshrub",
        35 => "wool",
        37 => "yellowflower",
        38 => "cyanflower",
        39 => "brownmushroom",
        40 => "redmushroom",
        41 => "blockofgold",
        42 => "blockofiron",
        44 => "stoneslab",
        45 => "brick",
        46 => "tnt",
        47 => "bookcase",
        48 => "mossstone",
        49 => "obsidian",
        50 => "torch",
        51 => "fire",
        52 => "mobspawner",
        53 => "woodenstairs",
        56 => "diamondore",
        57 => "blockofdiamond",
        58 => "workbench",
        59 => "wheat",
        60 => "farmland",
        61 => "furnace",
        62 => "furnace",
        64 => "wooddoor",
        65 => "ladder",
        66 => "rails",
        67 => "cobblestonestairs",
        71 => "irondoor",
        73 => "redstoneore",
        78 => "snow",
        79 => "ice",
        80 => "snowblock",
        81 => "cactus",
        82 => "clayblock",
        83 => "sugarcane",
        85 => "fence",
        87 => "netherrack",
        88 => "soulsand",
        89 => "glowstone",
        96 => "trapdoor",
        98 => "stonebricks",
        99 => "brownmushroom",
        100 => "redmushroom",
        102 => "glasspane",
        103 => "melon",
        105 => "melonvine",
        107 => "fencegate",
        108 => "brickstairs",
        109 => "stonebrickstairs",
        112 => "netherbrick",
        114 => "netherbrickstairs",
        128 => "sandstonestairs",
        155 => "blockofquartz",
        156 => "quartzstairs",
        245 => "stonecutter",
        246 => "glowingobsidian",
        247 => "netherreactorcore"
    );
    private $items = array(
        256 => "ironshovel",
        257 => "ironpickaxe",
        258 => "ironaxe",
        259 => "flintandsteel",
        260 => "apple",
        261 => "bow",
        262 => "arrow",
        263 => "coal",
        264 => "diamondgem",
        265 => "ironingot",
        266 => "goldingot",
        267 => "ironsword",
        268 => "woodensword",
        269 => "woodenshovel",
        270 => "woodenpickaxe",
        271 => "woodenaxe",
        272 => "stonesword",
        273 => "stoneshovel",
        274 => "stonepickaxe",
        275 => "stoneaxe",
        276 => "diamondsword",
        277 => "diamondshovel",
        278 => "diamondpickaxe",
        279 => "diamondaxe",
        280 => "stick",
        281 => "bowl",
        282 => "mushroomstew",
        283 => "goldsword",
        284 => "goldshovel",
        285 => "goldpickaxe",
        286 => "goldaxe",
        287 => "string",
        288 => "feather",
        289 => "gunpowder",
        290 => "woodenhoe",
        291 => "stonehoe",
        292 => "ironhoe",
        293 => "diamondhoe",
        294 => "goldhoe",
        295 => "wheatseeds",
        296 => "wheat",
        297 => "bread",
        298 => "leatherhelmet",
        299 => "leatherchestplate",
        300 => "leatherleggings",
        301 => "leatherboots",
        302 => "chainmailhelmet",
        303 => "chainmailchestplate",
        304 => "chainmailleggings",
        305 => "chainmailboots",
        306 => "ironhelmet",
        307 => "ironchestplate",
        308 => "ironleggings",
        309 => "ironboots",
        310 => "diamondhelmet",
        311 => "diamondchestplate",
        312 => "diamondleggings",
        313 => "diamondboots",
        314 => "goldhelmet",
        315 => "goldchestplate",
        316 => "goldleggings",
        317 => "goldboots",
        318 => "flint",
        319 => "rawporkchop",
        320 => "cookedporkchop",
        321 => "painting",
        322 => "goldapple",
        323 => "sign",
        332 => "snowball",
        334 => "leather",
        336 => "claybrick",
        337 => "clay",
        338 => "sugarcane",
        339 => "paper",
        340 => "book",
        344 => "egg",
        348 => "glowstonedust",
        352 => "bone",
        353 => "sugar",
        355 => "bed",
        359 => "shears",
        360 => "melon",
        362 => "melonseeds",
        363 => "rawbeef",
        364 => "steak",
        365 => "rawchicken",
        366 => "cookedchicken",
        405 => "netherbrick",
        456 => "camera"
    );
    
}
