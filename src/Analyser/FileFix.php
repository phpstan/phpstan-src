<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use JsonSerializable;
use Override;
use ReturnTypeWillChange;

/**
 * @internal
 */
final class FileFix implements JsonSerializable
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

	/**
	 * @return array{diff: FixedErrorDiff, errorRefs: list<array{line: int, identifier: string|null}>}
	 */
	#[ReturnTypeWillChange]
	#[Override]
	public function jsonSerialize()
	{
		return [
			'diff' => $this->diff,
			'errorRefs' => $this->errorRefs,
		];
	}

	/**
	 * @param array{diff: array{originalHash: string, diff: string}, errorRefs: list<array{line: int, identifier: string|null}>} $json
	 */
	public static function decode(array $json): self
	{
		return new self(FixedErrorDiff::decode($json['diff']), $json['errorRefs']);
	}

}
