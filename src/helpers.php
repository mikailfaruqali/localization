<?php

if (! function_exists('when')) {
    function when($condition, $value, $default = null)
    {
        $condition = $condition instanceof Closure ? $condition() : $condition;

        if ($condition) {
            return value($value, $condition);
        }

        return value($default, $condition);
    }
}

if (! function_exists('value')) {
    function value($value, ...$args)
    {
        return $value instanceof Closure ? $value(...$args) : $value;
    }
}
