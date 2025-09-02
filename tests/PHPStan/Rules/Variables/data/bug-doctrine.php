<?php

namespace BugDoctrine;

class HelloWorld
{
	/**
	 * @param string|array $a
	 * @param array        $b
	 */
	public function sayHello($a, $b): void
	{
			$b[$a] ?? 2;
	}
}
