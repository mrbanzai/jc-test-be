<?php
class Skookum_String
{
    public static function toUri($str)
    {
        return preg_replace("/[^a-z0-9]/", "", preg_replace("/\s+/", "-", strtolower($str)));
    }

    public static function truncate($str, $wordcount = 40, $append = "")
    {
        $obj = new stdClass();

        $txt = explode(" ", $str, $wordcount);
        $obj->summary = implode(" ", array_splice($txt, 0, $wordcount - 1));

        $obj->summary . $append;

        if (!empty($txt)) {
            $obj->overflow = current($txt);
        } else {
            $obj->overflow = null;
        }

        return $obj;
    }
}