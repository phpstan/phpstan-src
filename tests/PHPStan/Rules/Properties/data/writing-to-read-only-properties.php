<?php

namespace WritingToReadOnlyProperties;

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
		$this->readOnlyProperty = 1;
		$this->readOnlyProperty += 1;

		$this->usualProperty = 1;
		$this->usualProperty .= 1;

		$this->writeOnlyProperty = 1;
		$this->writeOnlyProperty .= 1;

		$self = new self();
		$self->readOnlyProperty = 1;
		$self->readOnlyProperty += 1;

		$self->usualProperty = 1;
		$self->usualProperty .= 1;

		$self->asymmetricProperty = "1";
		$self->asymmetricProperty = 1;

		$self->writeOnlyProperty = 1;
		$self->writeOnlyProperty .= 1;

		$s = 'foo';
		$self->readOnlyProperty = &$s;
	}

}
