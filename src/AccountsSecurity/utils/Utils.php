<?php

namespace AccountsSecurity\utils;

use pocketmine\player\Player;
use com\formconstructor\form\CustomForm;
use AccountsSecurity\form\FormManager;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;

class Utils
{
    public static function isValidPassword(string $password, string $repeated, Player $player, CustomForm $form) : bool
    {
        $username = $player->getName();
        if (empty($password) or empty($repeated)):
            $message = "Заполните все§9 обязательные§f поля";
        elseif (strlen($password) < 8 or strlen($password) > 64):
            $message = "Придумайте пароль, состоящий§9 от 8 до 64 символов§f в длину";
        elseif (!preg_match("/^[a-zA-Z0-9!@#$%^&*()\-_=+;:.\/?]+$/", $password)):
            $message = "Придумайте пароль, состоящий из латинских букв§8 (§9a-z§f, §9A-Z§8)§f, цифр§8 (§90-9§8)§f и символов§8 (§9!@#$%^&*()-_=+;:./?§8)";
        elseif ($repeated !== $password):
            $message = "§9Проверьте правильность повторно введенного пароля§f и убедитесь, что повторно введенный пароль совпадает с исходным";
        elseif (strcasecmp($password, $username) == 0):
            $message = "Ваш пароль§9 слишком простой§f и легко угадываемый, придумай пароль§9 надёжнее";
        else:
            return true;
        endif;
        FormManager::setDefaultValue($username, ["password" => $password, "repeated" => $repeated]);
        (clone new $form($username))->addContent($message)->send($player);
        return false;
    }

    public static function showOrHidePlayers(Player $player, bool $show) : void
    {
        foreach ($player->getWorld()->getPlayers() as $players)
        {
            $show ? $player->showPlayer($players) : $player->hidePlayer($players);
            $show ? $players->showPlayer($player) : $players->hidePlayer($player);
        }
    }

    public static function getFormItem(int $registered) : Item
    {
        return VanillaItems::BOOK()->setCustomName("Открыть форму " . [true => "авторизации", false => "регистрации"][$registered]);
    }
}
