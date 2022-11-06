<?php

namespace Bug6147;

class Controller
{
	/**
	 * @param class-string<ControllerInterface> $class
	 * @return void
	 */
	public static function invokeController(string $class): void
	{
		if (/* Http::methodIs ("post") && */ method_exists($class, "methodPost")) {
			$class::methodPost(); // Call to an undefined static method ControllerInterface::methodPost()
		}
	}
}

interface ControllerInterface
{

}
