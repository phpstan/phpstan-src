<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use ReturnTypeWillChange;

class ExportedTraitNode implements ExportedNode, JsonSerializable
{

	public function __construct(private string $traitName)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		return false;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self($properties['traitName']);
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public static function decode(array $data): ExportedNode
	{
		return new self($data['traitName']);
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	public function jsonSerialize()
	{
		return [
			'type' => self::class,
			'data' => [
				'traitName' => $this->traitName,
			],
		];
	}

}
