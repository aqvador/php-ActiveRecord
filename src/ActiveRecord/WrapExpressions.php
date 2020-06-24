<?php
/**
 * Project: aqvador\activeRecord
 * User: achelnokov
 * Date: 24.06.2020г.
 * Time: 18:46
 */

namespace aqvador\ActiveRecord;


class WrapExpressions extends Expressions
{
    public function __toString()
    {
        return ($this->start ? $this->start : '(').implode(($this->delimiter ? $this->delimiter : ','), $this->target).($this->end ? $this->end : ')');
    }
}