<?php

namespace AccountsSecurity\scheduler;

use pocketmine\scheduler\Task;
use pocketmine\Server;
use AccountsSecurity;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\entity\effect\VanillaEffects;
use pocketmine\world\sound\PopSound;
use AccountsSecurity\database\Provider;
use AccountsSecurity\database\ColumnNames;

class TaskHandler extends Task
{
    public function onRun() : void
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $player)
        {
            $username = $player->getName();
            if (!AccountsSecurity::isAuthorized($username))
            {
                $accessTime = AccountsSecurity::getAccessTime($username);
                $player->getEffects()->add(new EffectInstance(VanillaEffects::BLINDNESS(), 1200, 0, false));
                if ($accessTime !== null)
                {
                    if ($accessTime > 0)
                    {
                        $player->getXpManager()->setXpLevel($accessTime);
                        AccountsSecurity::$accessTime[$username]--;
                        match ($accessTime)
                        {
                            90, 60, 30, 15, 10, 5, 3, 2, 1 => $player->getWorld()->addSound($player->getLocation(), new PopSound()),
                            default => null
                        };
                    } else
                    {
                        $registered = Provider::isRegistered($username, ColumnNames::PASSWORD);
                        $registeredText = [true => "авторизации", false => "регистрации"][$registered];
                        AccountsSecurity::setAccessTime($username, null);
                        $player->kick("§9Время§f для {$registeredText}§9 истекло§f\n" . "На " . [true => "авторизацию", false => "регистрацию"][$registered] . " предоставляется §9ограниченное§f время —§9 " . [true => "1 минута", false => "2 минуты"][$registered] . "§f\n" . "§9Повторите§f попытку $registeredText снова");
                    }
                }
            }
        }
    }
}
