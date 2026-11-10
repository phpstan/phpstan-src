<?php

namespace Bug15329;

class WakeupThrows
{

	public function __wakeup(): void
	{
		throw new \RuntimeException();
	}

}

class UnserializeThrows
{

	public function __unserialize(array $data): void
	{
		throw new \LogicException();
	}

}

function wakeupThrows(): void
{
	$data = serialize(new WakeupThrows());

	try {
		unserialize($data);
	} catch (\RuntimeException $e) {
	}
}

function unserializeThrows(): void
{
	$data = serialize(new UnserializeThrows());

	try {
		unserialize($data);
	} catch (\LogicException $e) {
	}
}
