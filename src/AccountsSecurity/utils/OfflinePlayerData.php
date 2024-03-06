<?php

namespace AccountsSecurity\utils;

use pocketmine\player\Player;
use pocketmine\entity\EntityDataHelper;
use pocketmine\inventory\Inventory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\data\bedrock\item\SavedItemStackData;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\data\bedrock\EffectIdMap;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\utils\Binary;

class OfflinePlayerData
{
    public static function setLocation(Player $player) : void
    {
        $server = $player->getServer();
        $nbt = $server->getOfflinePlayerData($player->getName());
        if ($nbt !== null)
        {
            $world = $server->getWorldManager()->getWorldByName($nbt->getString(Player::TAG_LEVEL, ""));
            if ($world !== null):
                $player->teleport(EntityDataHelper::parseLocation($nbt, $world));
            endif;
        }
    }

    private static function populateInventoryFromListTag(Inventory $inventory, array $items) : void
    {
        $listeners = $inventory->getListeners();
        $listeners->clear();
        $inventory->setContents($items);
        $listeners->add(...$listeners->toArray());
    }

    public static function setInventory(Player $player) : void
    {
        $inventory = $player->getInventory();
        $nbt = $player->getServer()->getOfflinePlayerData($player->getName());
        $inventory->clearAll();
        if ($nbt !== null)
        {
            $inventoryTag = $nbt->getListTag("Inventory");
            $offHandItemTag = $nbt->getCompoundTag("OffHandItem");
            if ($inventoryTag !== null)
            {
                $armorInventoryItems = [];
                $inventoryItems = [];
                /** @var CompoundTag $item */
                foreach ($inventoryTag as $item)
                {
                    $slot = $item->getByte(SavedItemStackData::TAG_SLOT);
                    if ($slot >= 100 and $slot < 104):
                        $armorInventoryItems[$slot - 100] = Item::nbtDeserialize($item);
                    elseif ($slot >= 9 and $slot < $inventory->getSize() + 9):
                        $inventoryItems[$slot - 9] = Item::nbtDeserialize($item);
                    endif;
                }
                self::populateInventoryFromListTag($inventory, $inventoryItems);
                self::populateInventoryFromListTag($player->getArmorInventory(), $armorInventoryItems);
            }
            if ($offHandItemTag !== null):
                $player->getOffHandInventory()->setItem(0, Item::nbtDeserialize($offHandItemTag));
            endif;
        }
    }

    public static function setEffects(Player $player) : void
    {
        $effects = $player->getEffects();
        $nbt = $player->getServer()->getOfflinePlayerData($player->getName());
        $effects->clear();
        if ($nbt !== null)
        {
            /** @var CompoundTag[]|ListTag|null $activeEffectsTag */
            $activeEffectsTag = $nbt->getListTag("ActiveEffects");
            if ($activeEffectsTag !== null)
            {
                foreach ($activeEffectsTag as $effect)
                {
                    $effectId = EffectIdMap::getInstance()->fromId($effect->getByte("Id"));
                    if ($effectId === null):
                        continue;
                    endif;
                    $effects->add(new EffectInstance($effectId, $effect->getInt("Duration"), Binary::unsignByte($effect->getByte("Amplifier")), $effect->getByte("ShowParticles", 1) !== 0, $effect->getByte("Ambient", 0) !== 0));
                }
            }
        }
    }

    public static function setXpLevel(Player $player) : void
    {
        $nbt = $player->getServer()->getOfflinePlayerData($player->getName());
        $xpManager = $player->getXpManager();
        if ($nbt !== null):
            $xpManager->setXpAndProgressNoEvent($nbt->getInt("XpLevel", 0), $nbt->getFloat("XpP", 0.0));
        else:
            $xpManager->setXpAndProgressNoEvent(0, 0.0);
        endif;
    }

    public static function setAll(Player $player) : void
    {
        self::setLocation($player);
        self::setInventory($player);
        self::setEffects($player);
        self::setXpLevel($player);
    }
}
