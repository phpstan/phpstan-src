<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * A unified diff of one file that fixes an error, computed against the contents with $originalHash.
 */
final class FixedErrorDiff
{

	public function __construct(
		public readonly string $file,
		public readonly string $originalHash,
		public readonly string $diff,
	)
	{
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['file'], $properties['originalHash'], $properties['diff']);
	}

}
