<?php

declare(strict_types=1);

namespace kelvinlolz\InventorySync;

use kelvinlolz\InventorySync\utils\InventorySerializer;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\plugin\PluginBase;
use poggit\libasynql\DataConnector;
use poggit\libasynql\libasynql;
use poggit\libasynql\SqlError;

class Main extends PluginBase implements Listener {
    /** @var DataConnector */
    private $database;

    public function onEnable() : void {
        $this->saveDefaultConfig();
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
        try {
            $this->database = libasynql::create($this, $this->getConfig()->get("database"), [
                "sqlite" => "sqlite.sql",
                "mysql" => "mysql.sql"
            ]);
            $this->database->executeGeneric("sync.init");
        } catch (SqlError $error){
            $this->getLogger()->error($error->getMessage() . ". Disabling plugin...");
            $this->getServer()->getPluginManager()->disablePlugin($this);
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        $this->database->executeSelect("sync.load", ["uuid" => $event->getPlayer()->getUniqueId()->toString()], function(array $rows) use($player) : void {
            $result = current($rows);
            if($result !== false){
                if($player->isOnline()){
                    $player->getInventory()->setContents(InventorySerializer::deserialize($result["inventory"]));
                    $player->getArmorInventory()->setContents(InventorySerializer::deserialize($result["armor"]));
                    $this->getLogger()->info("Restored " . $player->getName() . "(" . $player->getUniqueId()->toString() .") inventory.");

                    $this->database->executeChange("sync.delete", ["uuid" => $player->getUniqueId()->toString()]);
                } else {
                    $this->getLogger()->info($player->getName() . "(" . $player->getUniqueId()->toString() .") disconnected before inventory could be restored.");
                }
            }
        });
    }

    public function onQuit(PlayerQuitEvent $event) {
        $this->database->executeInsert("sync.save", [
            "uuid" => $event->getPlayer()->getUniqueId()->toString(),
            "inventory" => InventorySerializer::serialize($event->getPlayer()->getInventory()),
            "armor" => InventorySerializer::serialize($event->getPlayer()->getArmorInventory())
        ]);
        $event->getPlayer()->getInventory()->clearAll();
        $event->getPlayer()->getArmorInventory()->clearAll();
        $this->getLogger()->info("Saved " . $event->getPlayer()->getName() . "(" . $event->getPlayer()->getUniqueId()->toString() .") inventory.");
    }

    public function onDisable() : void {
        if(isset($this->database)) $this->database->close();
    }
}
