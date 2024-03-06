<?php

namespace AccountsSecurity\event;

use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use AccountsSecurity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\player\Player;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerChatEvent;
use AccountsSecurity\utils\Hash;
use AccountsSecurity\database\Provider;
use AccountsSecurity\database\ColumnNames;
use pocketmine\event\player\PlayerDataSaveEvent;
use AccountsSecurity\form\FormManager;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerEmoteEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use AccountsSecurity\utils\Utils;
use AccountsSecurity\form\Authorization;
use AccountsSecurity\form\Registration;
use pocketmine\event\player\PlayerJoinEvent;
use AccountsSecurity\User;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\Server;
use pocketmine\player\XboxLivePlayerInfo;
use pocketmine\event\server\CommandEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;

class EventHandler implements Listener
{
    public function BlockBreakEvent(BlockBreakEvent $event) : void
    {
        if (!AccountsSecurity::isAuthorized($event->getPlayer()->getName())):
            $event->cancel();
        endif;
    }

    public function EntityDamageEvent(EntityDamageEvent $event) : void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player and !AccountsSecurity::isAuthorized($entity->getName())):
            $event->cancel();
        endif;
        if ($event instanceof EntityDamageByEntityEvent)
        {
            $damager = $event->getDamager();
            if ($damager instanceof Player and !AccountsSecurity::isAuthorized($damager->getName())):
                $event->cancel();
            endif;
        }
    }

    public function EntityItemPickupEvent(EntityItemPickupEvent $event) : void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player and !AccountsSecurity::isAuthorized($entity->getName())):
            $event->cancel();
        endif;
    }

    public function EntityRegainHealthEvent(EntityRegainHealthEvent $event) : void
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player and !AccountsSecurity::isAuthorized($entity->getName())):
            $event->cancel();
        endif;
    }

    public function PlayerChatEvent(PlayerChatEvent $event) : void
    {
        $player = $event->getPlayer();
        $username = $player->getName();
        if (AccountsSecurity::isAuthorized($username))
        {
            foreach (explode(" ", $event->getMessage()) as $word)
            {
                if (Hash::correct(strtolower($word), Provider::getUserData($username, ColumnNames::LOWER_PASSWORD)))
                {
                    $player->sendMessage("§9Не разглашайте свой пароль§f в чате или других публичных местах");
                    $event->cancel();
                }
            }
        } else
        {
            $event->cancel();
        }
    }

    public function PlayerDataSaveEvent(PlayerDataSaveEvent $event) : void
    {
        $player = $event->getPlayer();
        if (!$player->isConnected())
        {
            $username = $player->getName();
            FormManager::setDefaultValue($username, null);
            if (AccountsSecurity::isAuthorized($username)):
                AccountsSecurity::setAuthorization($username, false);
            else:
                $event->cancel();
            endif;
        }
    }

    public function PlayerDropItemEvent(PlayerDropItemEvent $event) : void
    {
        if (!AccountsSecurity::isAuthorized($event->getPlayer()->getName())):
            $event->cancel();
        endif;
    }

    public function PlayerEmoteEvent(PlayerEmoteEvent $event) : void
    {
        if (!AccountsSecurity::isAuthorized($event->getPlayer()->getName())):
            $event->cancel();
        endif;
    }

    public function PlayerInteractEvent(PlayerInteractEvent $event) : void
    {
        if (!AccountsSecurity::isAuthorized($event->getPlayer()->getName())):
            $event->cancel();
        endif;
    }

    public function PlayerItemUseEvent(PlayerItemUseEvent $event) : void
    {
        $player = $event->getPlayer();
        $username = $player->getName();
        if (!AccountsSecurity::isAuthorized($username))
        {
            $registered = Provider::isRegistered($username, ColumnNames::PASSWORD);
            if ($event->getItem()->getTypeId() === Utils::getFormItem($registered)->getTypeId()):
                ($registered ? new Authorization($username) : new Registration($username))->send($player);
            else:
                $event->cancel();
            endif;
        }
    }

    public function PlayerJoinEvent(PlayerJoinEvent $event) : void
    {
        $player = $event->getPlayer();
        $username = $player->getName();
        $player->teleport($player->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn(), 90, 0);
        $player->setNoClientPredictions();
        if (Provider::isRegistered($username, ColumnNames::PASSWORD))
        {
            if (!AccountsSecurity::isAuthorized($username))
            {
                $extraData = $player->getPlayerInfo()->getExtraData();
                if (Hash::correctHashes([$player->getNetworkSession()->getIp(), $extraData["ClientRandomId"], $extraData["DeviceModel"]], [Provider::getUserData($username, ColumnNames::ADDRESS), Provider::getUserData($username, ColumnNames::CLIENT_RANDOM_ID), Provider::getUserData($username, ColumnNames::DEVICE_MODEL)]))
                {
                    User::login($player);
                } else
                {
                    AccountsSecurity::setAccessTime($username, 60);
                    AccountsSecurity::setAttempts($username, 3);
                    Utils::showOrHidePlayers($player, false);
                    (new Authorization($username))->send($player);
                    $player->getInventory()->clearAll();
                    $player->getOffHandInventory()->clearAll();
                    $player->getArmorInventory()->clearAll();
                    $player->getEffects()->clear();
                    $player->getInventory()->setItem(0, Utils::getFormItem(true));
                }
            }
        } else
        {
            if (empty($player->getXuid()))
            {
                AccountsSecurity::setAccessTime($username, 120);
                (new Registration($username))->send($player);
                Utils::showOrHidePlayers($player, false);
                $player->getInventory()->setItem(0, Utils::getFormItem(false));
            } else
            {
                User::login($player);
            }
        }
    }

    public function PlayerPreLoginEvent(PlayerPreLoginEvent $event) : void
    {
        $playerInfo = $event->getPlayerInfo();
        $username = $playerInfo->getUsername();
        if (Server::getInstance()->getPlayerExact($username) !== null)
        {
            $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_PLUGIN, "Выбранное вами имя пользователя§9 уже занятно в игре\n§fСтатус учётной записи:§9 " . [true => [true => "авторизован", false => "в процессе авторизации"], false => ["в процессе регистрации"]][Provider::isRegistered($username, ColumnNames::PASSWORD) or Provider::isRegistered($username, ColumnNames::XBOX_USER_ID)][AccountsSecurity::isAuthorized($username)]);
        } else
        {
            if (!$playerInfo instanceof XboxLivePlayerInfo and Provider::isRegistered($username, ColumnNames::XBOX_USER_ID)):
                $event->setKickFlag(PlayerPreLoginEvent::KICK_FLAG_PLUGIN, "Эта учетная запись§9 уже зарегистрирована§f с помощью§9 Xbox.\n§fЧтобы войти на сервер,§9 выполните вход в данную учетную запись§9 Xbox§f.");
            endif;
        }
    }

    public function CommandEvent(CommandEvent $event) : void
    {
        $sender = $event->getSender();
        if ($sender instanceof Player and !AccountsSecurity::isAuthorized($sender->getName())):
            $event->cancel();
        endif;
    }

    public function DataPacketReceiveEvent(DataPacketReceiveEvent $event) : void
    {
        $player = $event->getOrigin()->getPlayer();
        $packet = $event->getPacket();
        if ($player !== null and !AccountsSecurity::isAuthorized($player->getName()) and $packet instanceof InteractPacket and $packet->action === InteractPacket::ACTION_OPEN_INVENTORY):
            $event->cancel();
        endif;
    }
}
