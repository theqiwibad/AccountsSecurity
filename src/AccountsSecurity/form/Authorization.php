<?php

namespace AccountsSecurity\form;

use com\formconstructor\form\CustomForm;
use com\formconstructor\form\element\custom\Input;
use pocketmine\player\Player;
use com\formconstructor\form\response\CustomFormResponse;
use AccountsSecurity\utils\Hash;
use AccountsSecurity\database\Provider;
use AccountsSecurity\database\ColumnNames;
use AccountsSecurity\User;
use AccountsSecurity;

class Authorization extends CustomForm
{
    public function __construct(string $username, string $title = "§9Процесс авторизации")
    {
        parent::__construct($title);
        $this->addElement("password", new Input("§7Введите пароль от этой учётной записи", "", FormManager::getDefaultValue($username, "password")));
        $this->setHandler(
            function (Player $player, CustomFormResponse $response) : void
            {
                $password = $response->getInput("password")->getValue();
                $username = $player->getName();
                if (empty($password))
                {
                    (clone new $this($username))->addContent("Не оставляйте вводное поле§9 пустым")->send($player);
                    return;
                }
                if (Hash::correct($password, Provider::getUserData($username, ColumnNames::PASSWORD)))
                {
                    User::login($player);
                } else
                {
                    AccountsSecurity::$attempts[$username]--;
                    $attempts = AccountsSecurity::getAttempts($username);
                    if ($attempts > 0)
                    {
                        (clone new $this($username))->addContent("§9Неправильный пароль.§f У вас осталось§9 " . [2 => "две попытки", 1 => "одна попытка"][$attempts])->send($player);
                    } else
                    {
                        AccountsSecurity::setAttempts($username, null);
                        $player->getServer()->getNetwork()->blockAddress($player->getNetworkSession()->getIp());
                        $player->kick("Вы исчерпали§9 все три попытки\nПовторите попытку авторизации§9 через 5 минут\nЕсли вы§9 утратили доступ§f к учётной записи, обратитесь в§9 тех. поддержку§f проекта");
                    }
                }
            }
        );
    }
}
