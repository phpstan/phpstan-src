<?php

namespace ReflectionMethodOfClassStringOfNonExistentClass;

function inspect(DoesNotExist $object): void
{
	new \ReflectionMethod(\get_class($object), 'run');
}
