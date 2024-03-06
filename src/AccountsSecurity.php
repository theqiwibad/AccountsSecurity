<?php

use pocketmine\plugin\PluginBase;
use AccountsSecurity\database\DatabaseManager;
use AccountsSecurity\event\EventHandler;
use AccountsSecurity\scheduler\TaskHandler;
use AccountsSecurity\command\ChangePasswordCommand;

class AccountsSecurity extends PluginBase
{
    private static array $authorization = [];
    public static array $accessTime, $attempts = [];

    public function onEnable() : void
    {
        DatabaseManager::init($this->getDataFolder());
        $this->getServer()->getPluginManager()->registerEvents(new EventHandler(), $this);
        $this->getScheduler()->scheduleRepeatingTask(new TaskHandler(), 20);
        $this->getServer()->getCommandMap()->register("", new ChangePasswordCommand());
    }

    public static function isAuthorized(string $username) : bool
    {
        return isset(self::$authorization[$username]);
    }

    public static function setAuthorization(string $username, bool $mode) : void
    {
        if ($mode):
            self::$authorization[$username] = true;
        else:
            unset(self::$authorization[$username]);
        endif;
    }

    public static function getAccessTime(string $username) : ?int
    {
        return self::$accessTime[$username] ?? null;
    }

    public static function setAccessTime(string $username, ?int $seconds) : void
    {
        if ($seconds):
            self::$accessTime[$username] = $seconds;
        else:
            unset(self::$accessTime[$username]);
        endif;
    }

    public static function getAttempts(string $username) : ?int
    {
        return self::$attempts[$username] ?? null;
    }

    public static function setAttempts(string $username, ?int $count) : void
    {
        if ($count):
            self::$attempts[$username] = $count;
        else:
            unset(self::$attempts[$username]);
        endif;
    }
}
