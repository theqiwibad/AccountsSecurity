<?php

namespace AccountsSecurity\utils;

class Hash
{
    public static function set(string $value) : string
    {
        return password_hash($value, PASSWORD_BCRYPT);
    }

    public static function correct(string $value, string $hash) : bool
    {
        return password_verify($value, $hash);
    }

    public static function correctHashes(array $values, array $hashes) : bool
    {
        $verified = [];
        foreach (array_combine($values, $hashes) as $value => $hash):
            $verified[] = self::correct($value, $hash);
        endforeach;
        return in_array(true, $verified, true);
    }
}
