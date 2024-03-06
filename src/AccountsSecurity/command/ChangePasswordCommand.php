<?php

namespace AccountsSecurity\command;

use pocketmine\command\Command;
use pocketmine\permission\DefaultPermissions;
use pocketmine\permission\Permission;
use pocketmine\permission\PermissionManager;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use AccountsSecurity\database\Provider;
use AccountsSecurity\database\ColumnNames;
use AccountsSecurity\form\ChangePassword;

class ChangePasswordCommand extends Command
{
    public const COMMAND_PERMISSION_CHANGE_PASSWORD = "command.permission.change.password";

    public function __construct()
    {
        parent::__construct("changepassword", "Изменить пароль учётной записи", null, ["chpw"]);
        DefaultPermissions::registerPermission(new Permission(self::COMMAND_PERMISSION_CHANGE_PASSWORD), [PermissionManager::getInstance()->getPermission(DefaultPermissions::ROOT_USER)]);
        $this->setPermission(self::COMMAND_PERMISSION_CHANGE_PASSWORD);
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args) : void
    {
        if ($sender instanceof Player)
        {
            $username = $sender->getName();
            if (Provider::isRegistered($username, ColumnNames::PASSWORD)):
                (new ChangePassword($sender->getName()))->send($sender);
            else:
                $sender->sendMessage("Вы зарегистрированы с помощью§9 Xbox§f и§9 не можете§f использовать эту команду");
            endif;
        } else
        {
            $sender->sendMessage("§cИспользуйте только в игре");
        }
    }
}
