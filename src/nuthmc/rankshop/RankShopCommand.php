<?php

namespace nuthmc\rankshop;

use pocketmine\player\Player;
use pocketmine\Server;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat as TE;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use onebone\economyapi\EconomyAPI;

class RankShopCommand extends Command {
	
	private $plugin;
	
	private $name;
	
	private $price; 
	
	public function __construct(Loader $plugin) {
		parent::__construct("ranks", "NXTH-RankShop");
		$this->plugin = $plugin;
	}
	
	public function execute(CommandSender $sender, string $label, array $args): bool {
		if ($sender instanceof Player) {
		$player = $sender;
		if (isset($args[0])) {
			if($args[0] !== "add" && $args[0] !== "edit" && $args[0] !== "remove") {
				$player->sendMessage(TE::RED . "Unknown command");
			}
			if ($args[0]=="add") {
				if (!$player->hasPermission("rankshop.add")) {
					$player->sendMessage(TE::RED . "You dont have permission to use this command");
					return false;
				}
				$this->addForm($player);
				return true;
			}
			if($args[0] == "edit") {
				if (!$player->hasPermission("rankshop.edit")) {
					$player->sendMessage(TE::RED . "You dont have permission to use this command");
					return false;
				}
				$this->selectEditForm($player);
				return true;
			} 
			if($args[0] == "remove") {
				if (!$player->hasPermission("rankshop.remove")) {
					$player->sendMessage(TE::RED . "You dont have permission to use this command");
					return false;
				}
				$this->selectRemoveForm($player);
				return true;
			} 
			return true;
		}
		$this->rankForm($player);
		
		return true;
		}  
		return true;
	}
	public function rankForm($player) {
		$form = new SimpleForm(function(Player $player, $data) {
			$datas = [];
			$datas[] = "exit";
			$bb = $this->plugin->getDataBase();
			$list = $bb->query("SELECT rank FROM ranks ORDER BY price DESC");
			foreach ($list as $ls) {
				$datas[] = $ls["rank"];
			}
			if ($data == 0) {
				return;
			}
			foreach ($datas as $id => $name) {
				if($id == $data){
					$rdata = $bb->query("SELECT * FROM ranks WHERE rank = '$name'");
					$this->confirmForm($player, $rdata);
				}
			}
		});
		$form->setTitle(TE::GREEN . "§l§aRANKSHOP");
		$form->addButton(TE::RED . "EXIT");
		$db = $this->plugin->getDataBase();
		$dbs = $db->query("SELECT name FROM ranks ORDER BY price DESC");
		foreach ($dbs as $bn => $ranks) {
			$form->addButton($ranks["name"]);
			
		}
		$form->sendToPlayer($player);
		return $form;
	}
	public function confirmForm($player, $rank) {
		$rinfo = $rank->fetchAll(\PDO::FETCH_ASSOC);
		foreach ($rinfo as $info) {
			$tr = $info["rank"];
			$tp = $info["price"];
			$td = $info["description"];
		}
		$form = new ModalForm(function(Player $player, $data) use ($tr, $tp, $td){
			if($data === true) {
				$this->setRank($player, $tr, $tp);
			} else {
				$player->sendMessage(TE::RED . "Buy cancelled");
			}
			
		});
		$form->setTitle(TE::GREEN . "CONFIRMATION");
		$form->setContent("§l§3RANK: §b" . $tr . " \n\n\n§l§5PRICE: §d" . $tp . " \n\n\n§2DESCRIPTION: §a" . $td);
		$form->setButton1(TE::GREEN . "BUY");
		$form->setButton2(TE::RED . "CANCEL");
		$form->sendToPlayer($player);
		return $form;
	}
	public function setRank($player, $rank, $price){
		$db = $this->plugin->getDataBase();
		$economyapi = $this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI");
		$pp = $this->plugin->getServer()->getPluginManager()->getPlugin('PurePerms');
		if ($pp->getGroup($rank) === null) {
			$player->sendMessage("§l§aNuthMC-RANKSHOP: " . TE::RED . "Rank does not exist");
			return false;
		}
		if ($economyapi->myMoney($player) >= $price) {
			$group = $pp->getGroup($rank);
			$pp->setGroup($player, $group);
			$economyapi->reduceMoney($player, $price);
			$player->sendMessage("§l§aRANKSHOP: " . TE::AQUA . "You have successfully purchased a rank");
		} else {
			$player->sendMessage("§l§aNuthMC-RANKSHOP: " . TE::RED . "You dont have enough money to buy this rank. You need $" . $price - $economyapi->myMoney($player) . " more");
		}
	}
	
	public function addForm($player) {
		$form = new CustomForm(function(Player $player, array $data = null) {
			$db = $this->plugin->getDataBase();
			$dbs = $db->query("SELECT * FROM ranks");
			$idata = $dbs->fetchAll(\PDO::FETCH_ASSOC);
			if ($data === null) {
				return false;
			}
			if ($data[0] == null) {
				$player->sendMessage(TE::RED . "Please type display name");
			}
			foreach ($idata as $ass) {
			    if($ass["rank"] == $data[1]){
					$player->sendMessage(TE::RED . "Rank: " . $data[1] . " Already added to shop");
				 	return false;
			    }
			}
			if ($data[3] == null) {
				$player->sendMessage(TE::RED . "Please type description");
				return;
			}
			if ($data[2] == null) {
				$player->sendMessage(TE::RED . "Please type price");
			}
			if (!is_numeric($data[2])) {
				$player->sendMessage(TE::RED . "Must type number of price");
			}
			$db->query("INSERT INTO ranks(name, rank, price, description) VALUES ('$data[0]', '$data[1]', '$data[2]', '$data[3]')");
			$player->sendMessage(TE::GREEN . "Rank added successfully");
		});
		$form->setTitle("§l§aNuthMC-RANKSHOP: §bADD");
		$form->addInput("Dispay name", "Type display name of rank in form");
		$form->addInput("Rank", "Type Rank");
		$form->addInput("Price", "Type Price");
		$form->addInput("Description", "Type Description");
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function selectEditForm($player) {
		$db = $this->plugin->getDataBase(); 
		$arr = $db->query("SELECT rank FROM ranks");
		$button = $arr->fetchAll(\PDO::FETCH_ASSOC);
		$idata = array();
		$idata[] = "exit";
		foreach ($button as $d) {
			$idata[] = $d["rank"];
		}
		$form = new SimpleForm(function(Player $player, $data) use ($idata){
			if ($data == 0) {
				return;
			}
			foreach ($idata as $id => $idatas) {
				if ($data == $id) {
					$this->editForm($player, $idatas);
				}
			}
			
		});
		$form->setTitle("§l§aNuthMC-RANKSHOP: §dEDIT");
		$rdn = $db->query("SELECT name FROM ranks");
		$bton = $rdn->fetchAll(\PDO::FETCH_ASSOC); 
		$form->addButton(TE::RED . "EXIT");
		foreach ($bton as $buttons) {
			$form->addButton($buttons["name"]);
		}
		$form->sendToPlayer($player);
		return $form;
	}
	
	public function editForm($player, $rank) {
		$database = $this->plugin->getDataBase();
		$infomation = $database->query("SELECT * FROM ranks WHERE rank = '$rank'");
		foreach ($infomation as $info) {
			$ninfo = $info["name"];
			$rinfo = $info["rank"];
			$pinfo = $info["price"];
			$dinfo = $info["description"];
		}
		$form = new CustomForm(function (Player $player, array $data = null) use ($database, $rinfo) {
			if ($data === null) {
				return;
			}
			if ($rinfo === null) {
				return;
			}
			if ($data[0] == null) {
				$player->sendMessage(TE::RED . "Edit error: Please type display name");
				return;
			}
			if ($data[1] == null) {
				$player->sendMessage(TE::RED . "Edit error: Please type rank");
				return $form;
			}
			if (!is_numeric($data[2])) {
				$player->sendMessage(TE::RED . "Edit error: Please type price");
				return $form;
			}
			if ($data[3] == null) {
				$player->sendMessage(TE::RED . "Edit error: Please type description");
				return $form;
			}
			$player->sendMessage("§l§aNuthMC-RANKSHOP: §l§2Edited successfully");
			$database->exec("UPDATE ranks SET name = '$data[0]' WHERE rank = '$rinfo'");
			$database->exec("UPDATE ranks SET rank = '$data[1]' WHERE name = '$data[0]'");
			$database->exec("UPDATE ranks SET price = '$data[2]' WHERE name = '$data[0]'");
			$database->exec("UPDATE ranks SET description = '$data[3]' WHERE name = '$data[0]'");
		});
		$form->setTitle("§l§aNuthMC-RANKSHOP: §dEDIT");
		$form->addInput("DISPLAY NAME", "Edit display name", $ninfo);
		$form->addInput("RANK", "Edit rank", $rinfo);
		$form->addInput("PRICE", "Edit price", $pinfo);
		$form->addInput("DESCRIPTION", "Edit description", $dinfo);
		$form->sendToPlayer($player);
		return $form;
	}
	public function selectRemoveForm($player) {
		$db = $this->plugin->getDataBase(); 
		$arr = $db->query("SELECT rank FROM ranks");
		$button = $arr->fetchAll(\PDO::FETCH_ASSOC);
		$ranks = $db->query("SELECT rank FROM ranks");
		$idata = array();
		$idata[] = "exit";
		foreach ($button as $d) {
			$idata[] = $d["rank"];
		}
		$form = new SimpleForm(function(Player $player, $data) use ($idata){
			if ($data == 0) {
				return;
			}
			foreach ($idata as $id => $idatas) {
				if ($data == $id) {
					$db = $this->plugin->getDataBase();
					$db->query("DELETE FROM ranks WHERE rank = '$idatas'");
					$player->sendMessage("§l§aNuthMC-RANKSHOP: " .TE::BLUE . "Rank removed successfully");
				}
			}
			
		});
		$form->setTitle("§l§aNuthMC-RANKSHOP: §4REMOVE");
		$rdn = $db->query("SELECT name FROM ranks")->fetchAll(\PDO::FETCH_ASSOC);
		$form->addButton(TE::RED . "EXIT");
		foreach ($rdn as $buttons) {
			$form->addButton($buttons["name"]);
		}
		$form->sendToPlayer($player);
		return $form;
	}
}
