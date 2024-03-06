<?php

namespace AccountsSecurity\form;

class FormManager
{
    public static ?array $defaultValue = [];

    public static function getDefaultValue(string $username, string $value) : array|string
    {
        return self::$defaultValue[$username][$value] ?? "";
    }

    public static function setDefaultValue(string $username, ?array $value) : void
    {
        if ($value):
            self::$defaultValue[$username] = $value;
        else:
            unset(self::$defaultValue[$username]);
        endif;
    }
}
