<?php // lint >= 8.0

namespace ReadingWriteOnlyProperties;

function (?Foo $foo): void
{
	echo $foo?->readOnlyProperty;
	echo $foo?->usualProperty;
	echo $foo?->asymmetricProperty;
	echo $foo?->writeOnlyProperty;
};
