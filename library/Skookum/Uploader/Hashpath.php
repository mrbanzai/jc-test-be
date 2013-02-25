<?php
class Skookum_Uploader_Hashpath
{
    public static function getPath($hash)
    {
        return substr($hash, 0, 1) .'/'. substr($hash, 0,2) . '/' . substr($hash, 0, 8);
    }
}