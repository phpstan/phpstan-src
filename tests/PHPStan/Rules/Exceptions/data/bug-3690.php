<?php declare(strict_types = 1);

namespace Bug3690;

class HelloWorld
{
	public function sayHello(): bool
	{
		try
		{
			return eval('');
		}
		catch (\ParseError $e)
		{
			return false;
		}
	}
}
