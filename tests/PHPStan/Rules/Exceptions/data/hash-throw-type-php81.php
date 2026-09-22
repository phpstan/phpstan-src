<?php

namespace HashThrowTypePhp81;

class Foo
{

	public function doFoo(string $data): void
	{
		try {
			$a = hash('xxh3', $data);
		} catch (\ValueError $e) {

		}

		try {
			$a = hash('murmur3f', $data, false, ['seed' => 1]);
		} catch (\ValueError $e) {

		}

		try {
			$a = hash('xxh128', $data, false, ['secret' => 'short']);
		} catch (\Error $e) {

		}
	}

}
