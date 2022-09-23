<?php

namespace ReadingWriteOnlyProperties;

use AllowDynamicProperties;

/**
 * @property-read int $readOnlyProperty
 * @property int $usualProperty
 * @property-write int $writeOnlyProperty
 */
#[AllowDynamicProperties]
class Foo
{

	public function doFoo()
	{
		echo $this->readOnlyProperty;
		echo $this->usualProperty;
		echo $this->writeOnlyProperty;

		$self = new self();
		echo $self->readOnlyProperty;
		echo $self->usualProperty;
		echo $self->writeOnlyProperty;
	}

}
