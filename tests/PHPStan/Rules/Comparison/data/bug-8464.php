<?php // lint >= 8.0

namespace Bug8464;

final class ObjectUtil
{
	/**
	 * @param class-string $type
	 */
	public static function instanceOf(mixed $object, string $type): bool
	{
		return \is_object($object)
			&& (
				$object::class === $type ||
				is_subclass_of($object, $type)
			);
	}
}
