<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

/**
 * @internal
 */
final class FileFix
{

	/**
	 * @param list<array{line: int, identifier: string|null}> $errorRefs
	 */
	public function __construct(
		public readonly FixedErrorDiff $diff,
		public readonly array $errorRefs,
	)
	{
	}

	/**
	 * @param array{diff: FixedErrorDiff, errorRefs: list<array{line: int, identifier: string|null}>} $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['diff'], $properties['errorRefs']);
	}

}
