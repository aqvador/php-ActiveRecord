<?php
/**
 * Project: aqvador\activeRecord
 * User: achelnokov
 * Date: 24.06.2020г.
 * Time: 18:46
 */

namespace aqvador\ActiveRecord;

/**
 * base class to stord attributes in one array.
 */
class Base
{
    public function __construct($config = [])
    {
        foreach ($config as $key => $val) {
            $this->$key = $val;
        }
    }

    public function __set($var, $val)
    {
        $this->$var = $val;
    }

    public function & __get($var)
    {
        $result = isset($this->$var) ? $this->$var : null;
        return $result;
    }
}