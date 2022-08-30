<?php

namespace kelvinlolz\InventorySync\utils;

use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\nbt\BigEndianNbtSerializer;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\TreeRoot;

final class InventorySerializer {

    public static function serialize(Inventory $inventory) : string {
        $items = [];
        foreach ($inventory->getContents() as $slot => $item){
            $items[] = $item->nbtSerialize($slot);
        }
        return (new BigEndianNbtSerializer())->write(new TreeRoot(CompoundTag::create()->setTag("Inventory", new ListTag($items, NBT::TAG_Compound))));
    }

    public static function deserialize(string $data): array {
        $inventory = [];
        /** @var CompoundTag $nbt */
        foreach ((new BigEndianNbtSerializer())->read($data)->mustGetCompoundTag()->getListTag("Inventory") as $nbt){
            $inventory[$nbt->getByte("Slot")] = Item::nbtDeserialize($nbt);
        }
        return $inventory;
    }
}
