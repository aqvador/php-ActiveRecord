<?php
/**
 * Project: aqvador\activeRecord
 * User: achelnokov
 * Date: 24.06.2020г.
 * Time: 23:31
 */

namespace aqvador\ActiveRecord\traites;


trait ArrayAssess
{
    /**
     * Returns whether there is an element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `isset($model[$offset])`.
     *
     * @param  mixed  $offset  the offset to check on.
     *
     * @return bool whether or not an offset exists.
     */
    public function offsetExists($offset)
    {
        return isset($this->$offset);
    }

    /**
     * Returns the element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$value = $model[$offset];`.
     *
     * @param  mixed  $offset  the offset to retrieve element.
     *
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        return $this->$offset;
    }

    /**
     * Sets the element at the specified offset.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `$model[$offset] = $item;`.
     *
     * @param  int    $offset  the offset to set element
     * @param  mixed  $item    the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->$offset = $item;
    }

    /**
     * Sets the element value at the specified offset to null.
     * This method is required by the SPL interface [[\ArrayAccess]].
     * It is implicitly called when you use something like `unset($model[$offset])`.
     *
     * @param  mixed  $offset  the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->$offset = null;
    }

}