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
        $this->server = ServerAPI::request();
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

        $this->cash = new Config($this->path . "Economy.yml", CONFIG_YAML, array(
            "User" => array("Name" => "", "Money" => "")));//this config should do a for loop for each new member that joins -Glitch
        //fixed it -Glitch
        $this->pvp = new Config($this->path . "PvP.yml", CONFIG_YAML, array("PvP_Default (is PvP enabled or disabled by default)" => "Enabled", "Areas" => array()));
        $this->pvp = $this->api->plugin->readYAML($this->path . "PvP.yml");//Got started on PvP -Glitch
        //Moved PvP into the init -Leon
        //Removed read... Useless for now, maybe we'll need it idk
        
        $this->DetectSkill = new Config($this->api->plugin->configPath($this)."DetectionSkill.yml", CONFIG_YAML);//someone forgot semicolon :P -Leon //me XD -Junyi00
        $this->DetectSkill = $this->api->plugin->readYAML($this->path . "DetectSkill.yml");
        $this->api->console->register("detect","Turn on/off being able to detect in-coming players",array($this, "detectSwitch"));
        $this->api->ban->cmdWhitelist("detect");
        
        $this->FightingSkill = new Config($this->api->plugin->configPath($this)."FightingSkill.yml", CONFIG_YAML);//someone forgot semicolon :P -Leon //me XD -Junyi00
        $this->FightingSkill = $this->api->plugin->readYAML($this->path . "FightingSkill.yml");
        
        $this->api->schedule(20* 4, array($this, "CheckNearby"), array(), true); 
        
        $this->api->schedule(20* 20, array($this, "Healing"), array(), true); //20 secs to heal 1 heatlh, true->(repeat)
        
    }
    //Shouldn't we be using storing data using SQL? There are othe stuffs to store too, afraid sing so much yaml would lag the server-Junyi00
    //SQL is actually not a very good protocol. It is better than YAML, but if we can create a good YAML file, or a file for each player, the lag will be virtually nonexistent -Leon
    public function __destruct() {}

    public function register($data,$event){
        switch($event){
        	//Temporarily removing this block of code, seeing if Glitch's method works -Leon
        	//What method do I have? -Glitch
        	//The loop you made? -Leon
            //That works right? -Leon
            //No, that is why it was removed, I have never worked with unlimited configs before so I was goofing around and seeing what might work -Glitch
        	//Ok makes sense -Leon
            case "player.join":
                //Better way to write player stuff to yaml
                //Needs to be improved
                $target = $data->username;
                if (!$this->cash->exists($target)) {
                    $this->cash->set($target, array('money' => self::DEFAULT_MONEY));
                    if(self::DEFAULT_MONEY !== 0){
                        $data->sendChat("[SAO]You have received self::DEFAULT_MONEY coins");
                    }
                    $this->DetectSkill->set($target, array('SkillLevel' => self::DEFAULT_SKILL, "On/Off" => "Off"));
                    if(self::DEFAULT_SKILL !== 0){
                        $data->sendChat("[SAO]You have received self::DEFAULT_SKILL detection skill points");
                    }
                    $this->FightingSkill->set($target, array("SkillLevel" => self::DEFAULT_SKILL));
                    if(self::DEFAULT_SKILL !== 0){
                        $data->sendChat("[SAO]You have received self::DEFAULT_SKILL fighting skill points");
                    }
                }
                $this->FightingSkill->save();
                $this->DetectSkill->save();
                $this->cash->save();
                break;
            case "money.player.get":
                //Gets the money of a player for the prefix
                if ($this->cash->exists($data['username'])) {
                    return $this->cash->get($data['username'])['money'];
                }
                return false;
        }
    }

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
    
    public function Economy($cmd, $args, $issuer){
        $username = $issuer->username;
        $money = $this->cash->get($username)['money'];
        switch($args[0]){
        	case "get":
        		$issuer->sendChat($money);
        		//Not sure if supposed to be like this? --Leon
        		break;
        	case "gift":
        		$target = $args[1];
        		$player = $this->api->player->get($target);
        		if($player === false){
        			return false;
        		}
        		else{
        			$amount = $args[2];
        			if($money<$amount){
        				$issuer->sendChat("[SAO]You don't have enough money to gift ".$target);
        			}
        			else {
        				$targetmoney = $this->cash->get($target)['money'];
        				$newamount = $money - $amount;
        				$giftedamount = $targetmoney + $targetmoney;
        				$this->cash->set($username, array('money' => $newamount));
        				$this->cash->set($target, array('money' => $giftedamount));
                                        $this->cash->save();
                                        $this->api->chat->sendTo(false, "[SAO]You have gifted $target $giftamount coins!", $username);
                                        $this->api->chat->sendTo(false, "[SAO]$username has gifted you $giftamount coins!", $target);
        				//I think you wanted it like this? -Leon
        				//$issuer->sendChat(); works the same but it's shorter -Glitch
                                        //yeah good point, I'm used to sendTo XD -Leon 
        			}
        			//to be continued -Glitch
        		}
        }
    }
    
    public function detectSwitch($cmd, $arg, $issuer) {
        $username = $issuer->username;
        $data = $this->DetectSkill->get($username);
        if ($data['On/Off'] == "On") {
            $this->DetectSkill->set($username, array("SkillLevel" => $data['SkillLevel'], "On/Off" => "Off"));
            $issuer->sendChat("Detection Skill disabled!");
        }
        else {
            $this->DetectSkill->set($username, array("SkillLevel" => $data['SkillLevel'], "On/Off" => "On"));
            $issuer->sendChat("Detection Skill enabled!");
        }
        $this->DetectSkill->save();
    } //flip boolean
    
    private function FindNearbyPlayers($name, $player2) {
        $player = $this->api->player->get($name);
        if($this->DetectSkill->get($name)["On/Off"] == "On"){
			$Pdetect = 0;
			$Pdetecthide = false;
			$Pdetectskill = $this->DetectSkill->get($name)["SkillLevel"];
			    switch($Pdetectskill) {
					 case 1: 
					 case 2:
					 case 3:
					 case 4: 
					     $Pdetect = $detectSkill[$Pdetectskill];
					     $Pdetecthide = false;
					     break;
					 case 5:
				             $Pdetect = $detectSkill[5];
					     $Pdetecthide = true;
					     break;
			}
		if ($Pdetect >= $range) $player->sendChat($player2." is ".round($range)." blocks away from u");
		}
    }
    
    public function CheckNearby() {
        $py = $this->api->player->online();
		$copy = $py;
		if (count($py) > 1) { //at least 2 players in server
			for($i=0;$i<count($py);$i++) {
				if ($i == (count($py)-1)) break;
				
					$p1 = $this->api->player->get($py[$i]);
					array_shift($copy); //remove the $p1 from the second array
					for($e=0;$e<count($copy);$e++) {
						$p2 = $this->api->player->get($copy[$e]);
					
						$p1vec = new Vector3($p1->entity->x, $p1->entity->y, $p1->entity->z); 
						$p2vec = new Vector3($p2->entity->x, $p2->entity->y, $p2->entity->z);
					
						$range = round($p1vec->distance($p2vec)); //the number of blocks away from each other
					
						$this->FindNearbyPlayers($p1->username, $p2->username);
						$this->FindNearbyPlayers($p2->username, $p1->username); //guys, my mind messed up here, help me see if im dong the correc tthings here -Junyi00
					}
				}
				$copy = $py;
			}
    	}
    //Done detect skill for now?? Someone test for me plz, my server is spoiled; From line 109 - 166; -Junyi00
    //Once i fully implement hiding skill, i can then complete this. Current code should work though -Junyi00
    
    public function Healing() {
        $players = $this->api->player->online();
        for($i=0;$i<count($players);$i++) {
            $player = $this->api->player->get($players[$i]);
            if ($player->entity->getHealth() != 20) { 
                $player->entity->setHealth($player->entity->getHealth()+1, "Healing"); //heal 1 health
            }
        }
    } //Done? Someone test for me plz :P -Junyi00

    //Ok Finished Exams and fixed xD
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
                                        $money = $this->cash->get($usrname)['money'];
                                        if ($money < $cost) {
                                            $this->api->chat->sendTo(false, "[SAO]You don't have enough coins!", $usrname);
                                        }
                                        else {
                                            $leftovermoney = $this->config->get($usrname)['money'] - $cost;
                                            $this->cash->set($usrname, array('money' => $leftovermoney));
                                            $this -> api -> console -> run("give " . $usrname . " " . $item." ".$amount);
                                            $this->cash->save();
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
                                    $money = $this->cash->get($usrname)['money'];
                                    if ($money < $cost) {
                                        $this->api->chat->sendTo(false, "[SAO]You don't have enough coins!", $usrname);
                                    }
                                    else {
                                        $leftovermoney = $this->config->get($usrname)['money'] - $cost;
                                        $this->cash->set($usrname, array('money' => $leftovermoney));
                                        $this -> api -> console -> run("give " . $usrname . " " . $item." ".$amount);
                                        $this->cash->save();
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
                                        $this->cash->set($usrname, array('money' => $extramoney));
                                        $player->removeItem($item, $damage, $amount, $send = true);
                                        $this->cash->save();
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
    
    private $detectSkill = array(
        1 => 3,
        2 => 5,
        3 => 8,
        4 => 11,
        5 => 15 //15 blocks + detect hiding players
    );
    
}
