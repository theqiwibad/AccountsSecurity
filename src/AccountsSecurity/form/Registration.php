<?php

namespace AccountsSecurity\form;

use com\formconstructor\form\CustomForm;
use com\formconstructor\form\element\custom\Input;
use pocketmine\player\Player;
use com\formconstructor\form\response\CustomFormResponse;
use AccountsSecurity\utils\Utils;
use AccountsSecurity\User;

class Registration extends CustomForm
{
    public function __construct(string $username, string $title = "§9Процесс регистрации")
    {
        parent::__construct($title);
        $this->addElement("password", new Input("§7Придумайте и введите надёжный пароль", "", FormManager::getDefaultValue($username, "password")));
        $this->addElement("repeated", new Input("§7Введите придуманный пароль повторно", "", FormManager::getDefaultValue($username, "repeated")));
        $this->setHandler(
            function (Player $player, CustomFormResponse $response) : void
            {
                $password = $response->getInput("password")->getValue();
                $repeated = $response->getInput("repeated")->getValue();
                if (Utils::isValidPassword($password, $repeated, $player, $this)):
                    User::login($player, $password);
                endif;
            }
        );
    }
}
