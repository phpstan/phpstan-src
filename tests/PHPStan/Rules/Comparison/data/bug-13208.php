<?php

namespace Bug13208;

class HelloWorld
{
	public function main()
	{
		$file = new \SplFileObject('/tmp/file', "wd+");
		if ($file->fwrite('a') === false) {
			throw new \Exception("write failed !");
		}
	}
}
