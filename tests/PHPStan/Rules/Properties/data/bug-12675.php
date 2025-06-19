<?php

namespace Bug12675;

class HelloWorld
{
	private string $username = "";
	private string $domain = "";

	public function with_shift(string $email): void
	{
		$pieces = explode("@", $email);
		if (2 !== count($pieces)) {

			throw new \Exception("Bad, very bad...");
		}

		$this->username = array_shift($pieces);
		$this->domain = array_shift($pieces);

		echo "{$this->username}@{$this->domain}";
	}

	public function with_pop(string $email): void
	{
		$pieces = explode("@", $email);
		if (2 !== count($pieces)) {

			throw new \Exception("Bad, very bad...");
		}

		$this->domain = array_pop($pieces);
		$this->username = array_pop($pieces);

		echo "{$this->username}@{$this->domain}";
	}
}
