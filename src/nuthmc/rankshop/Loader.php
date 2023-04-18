<?php

namespace nuthmc\rankshop;

use pocketmine\player\Player;
use pocketmine\Server; 
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;

class Loader extends PluginBase implements Listener {
	
	public $sql;
	
	public function onEnable(): void{
		@mkdir($this->getDataFolder());
		$this->sql = new \PDO('sqlite:' . $this->getDataFolder() . "ranks.db");
		$pp=$this->sql->query("CREATE TABLE IF NOT EXISTS ranks (name CHAR NOT NULL, rank CHAR NOT NULL, price INT, description CHAR NOT NULL);");
		$this->getServer()->getCommandMap()->register("rank", new RankShopCommand($this)); 
		
	}
	
	public function getDataBase() {
		return $this->sql;
	}
	
}
