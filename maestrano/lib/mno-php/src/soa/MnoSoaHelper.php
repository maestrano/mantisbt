<?php

/**
 * Helper functions
 *
 * @author root
 */
class MnoSoaHelper 
{
    public function getNumeric($str) 
    {
        $result = preg_replace("/[^0-9.]/","",$str);
        if (empty($result) || !is_numeric($result)) return 0;
        return intval($result);
    }

    public function array_key_has_value($key, $array)
    {
        return array_key_exists($key, $array) && $array->$key != null;
    }

    public function set_if_array_key_has_value(&$target, $key, &$array)
    {
        if (MnoSoaHelper::array_key_has_value($key, $array)) {
            $target = $array->$key;
        }
    }

    public function push_set_or_delete_value(&$source, $empty_value="")
    {
        if (!empty($source)) { return $source; }
        else { return $empty_value; }
    }

    public function pull_set_or_delete_value(&$source, $empty_value="")
    {
        if ($source == null) { return null; }
        else if (!empty($source)) { return $source; }
        else { return $empty_value; }
    }
}