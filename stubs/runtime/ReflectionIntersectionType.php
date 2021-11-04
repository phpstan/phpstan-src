<?php

if (\PHP_VERSION_ID < 80100) {
	if (class_exists('ReflectionIntersectionType', false)) {
		return;
	}

	class ReflectionIntersectionType extends ReflectionType
	{

		/** @return ReflectionType[] */
		public function getTypes()
		{
			return [];
		}

	}
}
