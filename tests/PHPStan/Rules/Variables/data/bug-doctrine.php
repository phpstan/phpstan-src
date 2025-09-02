<?php

namespace BugDoctrine;

class HelloWorld
{
	public function sayHello(string|array $a, array $b): void
	{
			$b[$a] ?? 2;
	}
}
