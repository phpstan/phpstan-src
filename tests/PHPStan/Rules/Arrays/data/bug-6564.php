<?php

namespace Bug6564;

class HelloWorld
{
	public function exportFileDataProvider(mixed $value): void
	{
		if ($this->isValueIterable()) {
			/** @var mixed[] $value */
			foreach ($value as $_value) {
			}
		}


	}
	public function isValueIterable(): bool {
		return true;
	}
}
