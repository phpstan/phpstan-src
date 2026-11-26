<?php declare(strict_types = 1);

namespace Bug15843;

/** @param mixed $method */
function slotMethod(object $instance, $method): void
{
	call_user_func_array([$instance, $method], []);
}

/** @param mixed $classOrObject */
function slotClass($classOrObject): void
{
	call_user_func([$classOrObject, 'terminate']);
}

/** @param mixed $callback */
function direct($callback): void
{
	call_user_func($callback); // not reported, before or after
}
