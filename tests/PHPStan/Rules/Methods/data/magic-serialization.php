<?php

namespace MagicSerialization;

class Ok {

	/** @return array<mixed> */
	public function __serialize(): array
	{
		return [];
	}

	/** @param array<mixed> $data */
	public function __unserialize(array $data): void
	{
	}
}

class WrongSignature {

	public function __serialize()
	{
		return '';
	}

	public function __unserialize($data)
	{
		return '';
	}
}
