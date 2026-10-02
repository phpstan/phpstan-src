<?php

namespace ResultCacheE2EValueDependency;

function usesIsAllowed(object $object): void
{
	if (isAllowed($object)) {
		$object->send();
	}
}
