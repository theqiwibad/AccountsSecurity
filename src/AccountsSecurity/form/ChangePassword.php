<?php

namespace AccountsSecurity\form;

use com\formconstructor\form\CustomForm;
use com\formconstructor\form\element\custom\Input;
use pocketmine\player\Player;
use com\formconstructor\form\response\CustomFormResponse;
use AccountsSecurity\utils\Hash;
use AccountsSecurity\database\Provider;
use AccountsSecurity\database\ColumnNames;

class ChangePassword extends CustomForm
{
    public function __construct(string $username, string $title = "§9Процесс обновления пароля")
    {
        parent::__construct($title);
        $this->addElement("password", new Input("§7Введите нынешний пароль от этой учётной записи", "", FormManager::getDefaultValue($username, "password")));
        $this->setHandler(
            function (Player $player, CustomFormResponse $response) use ($title) : void
            {
                $password = $response->getInput("password")->getValue();
                $username = $player->getName();
                if (empty($password))
                {
                    (clone new $this($username))->addContent("Не оставляйте вводное поле§9 пустым")->send($player);
                    return;
                }
                if (Hash::correct($password, Provider::getUserData($username, ColumnNames::PASSWORD))):
                    (new Registration($username, $title))->send($player);
                else:
                    (clone new $this($username))->addContent("§9Неправильный пароль.")->send($player);
                endif;
            }
        );
    }
}
