<?php

namespace Bug3396;

class HelloWorld
{

	public function takesString(string $s): void
	{
	}

	public function sayHello(): void
	{
		$stream = fopen("file.txt", "rb");
		if ($stream === false) throw new \Error("wtf");
		$this->takesString(stream_get_contents($stream));
		$this->takesString(stream_get_contents($stream, 1));
		$this->takesString(stream_get_contents($stream, 1, 1));
	}
}
