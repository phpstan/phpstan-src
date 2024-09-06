<?php

namespace Bug4960;

class HelloWorld
{
	public function sayHello(): void
	{
		$password = "123";
		$options = array('cost' => 11);

		password_hash($password, PASSWORD_DEFAULT, $options);
	}
}
