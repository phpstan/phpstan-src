<?php declare(strict_types = 1);

namespace Bug11484;

class HelloWorld
{
	public function sayHello(): void
	{
		if (filesize("file.txt") > 100) {
			file_put_contents("file.txt", str_repeat('aaaaaaa', rand(1,100)));
			if (filesize("file.txt") > 50) {

			}
		}

	}
}
