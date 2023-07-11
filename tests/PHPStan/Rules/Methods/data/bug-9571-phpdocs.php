<?php

namespace Bug9571PhpDocs;

trait DiContainerTrait
{
	/**
	 * @param array<string, mixed> $properties
	 *
	 * @return $this
	 */
	public function setDefaults(array $properties)
	{
		return $this;
	}
}

class FactoryTestDefMock
{
	use DiContainerTrait {
		setDefaults as  _setDefaults;
	}
}
