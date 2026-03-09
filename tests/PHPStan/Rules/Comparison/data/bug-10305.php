<?php // lint >= 8.0

namespace Bug10305;

class HelloWorld
{
	public null|string $prop;

	public function isPropertySet(): bool {
		return isset($this->prop) || null === $this->prop;
	}
}
