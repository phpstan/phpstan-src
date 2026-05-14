<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use JsonSerializable;
use Override;
use ReturnTypeWillChange;

final class FixedErrorDiff implements JsonSerializable
{

	public function __construct(
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
		return new self($properties['originalHash'], $properties['diff']);
	}

	/**
	 * @return array{originalHash: string, diff: string}
	 */
	#[ReturnTypeWillChange]
	#[Override]
	public function jsonSerialize()
	{
		return [
			'originalHash' => $this->originalHash,
			'diff' => $this->diff,
		];
	}

	/**
	 * @param array{originalHash: string, diff: string} $json
	 */
	public static function decode(array $json): self
	{
		return new self($json['originalHash'], $json['diff']);
	}

}
