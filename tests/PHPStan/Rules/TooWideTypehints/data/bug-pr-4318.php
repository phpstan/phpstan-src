<?php

namespace BugPR4318;

class Client
{
	private bool $isConnected = false;

	public function connect(): void
	{
		$driver = new \Redis();

		if ($driver->isConnected()) {
			return;
		}

		$driver->connect('');
		$this->isConnected = $driver->isConnected();
		echo $this->isConnected;
	}

}
