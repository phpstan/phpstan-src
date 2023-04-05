<?php

namespace ReadingWriteOnlyProperties;

use AllowDynamicProperties;

/**
 * @property-read int $readOnlyProperty
 * @property int $usualProperty
 * @property-read int $asymmetricProperty
 * @property-write int|string $asymmetricProperty
 * @property-write int $writeOnlyProperty
 */
#[AllowDynamicProperties]
class Foo
{

	public function doFoo()
	{
		echo $this->readOnlyProperty;
		echo $this->usualProperty;
		echo $this->asymmetricProperty;
		echo $this->writeOnlyProperty;

		$self = new self();
		echo $self->readOnlyProperty;
		echo $self->usualProperty;
		echo $self->asymmetricProperty;
		echo $self->writeOnlyProperty;
	}

}
