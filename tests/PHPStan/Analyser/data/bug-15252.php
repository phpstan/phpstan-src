<?php declare(strict_types = 1);

namespace Bug15252;

class Consumer
{

	public function run(): string
	{
		$helper = new Helper();

		return $helper->name();
	}

	public function runInvalidName(): string
	{
		$invalidName = new InvalidName();

		return $invalidName->name();
	}

}
