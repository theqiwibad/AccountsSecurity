<?php

namespace AccountsSecurity;

use pocketmine\player\Player;
use AccountsSecurity\database\DatabaseManager;
use AccountsSecurity\utils\Hash;
use AccountsSecurity\database\Provider;
use AccountsSecurity\database\ColumnNames;
use AccountsSecurity;
use AccountsSecurity\utils\Utils;
use AccountsSecurity\utils\OfflinePlayerData;
use pocketmine\world\sound\XpLevelUpSound;

class User
{
    public static function register(Player $player, ?string $password = null) : void
    {
        $username = $player->getName();
        $extraData = $player->getPlayerInfo()->getExtraData();
        $query = DatabaseManager::getDatabase()->prepare(
            "INSERT OR REPLACE INTO users
            (
                username, password, lowerPassword, xboxUserId, address, clientRandomId, deviceModel
            ) VALUES
            (
                LOWER(:username), :password, :lowerPassword, :xboxUserId, :address, :clientRandomId, :deviceModel
            )"
        );
        $query->bindValue(":username", $username);
        $query->bindValue(":password", $password ? Hash::set($password) : (Provider::getUserData($username, ColumnNames::PASSWORD) ?? null));
        $query->bindValue(":lowerPassword", $password ? Hash::set(strtolower($password)) : (Provider::getUserData($username, ColumnNames::LOWER_PASSWORD) ?? null));
        $query->bindValue(":xboxUserId", ($password or Provider::getUserData($username, ColumnNames::PASSWORD)) ? null : Hash::set($player->getXuid()));
        $query->bindValue(":address", Hash::set($player->getNetworkSession()->getIp()));
        $query->bindValue(":clientRandomId", Hash::set($extraData["ClientRandomId"]));
        $query->bindValue(":deviceModel", Hash::set($extraData["DeviceModel"]));
        $query->execute();
    }

    public static function login(Player $player, ?string $password = null) : void
    {
        $username = $player->getName();
        AccountsSecurity::setAuthorization($username, true);
        AccountsSecurity::setAccessTime($username, null);
        AccountsSecurity::setAttempts($username, null);
        Utils::showOrHidePlayers($player, true);
        OfflinePlayerData::setAll($player);
        self::register($player, $password);
        $player->setNoClientPredictions(false);
        $player->sendTitle(" ", "§l§9BedrockTime");
        $player->getWorld()->addSound($player->getLocation(), new XpLevelUpSound(5));
    }
}
