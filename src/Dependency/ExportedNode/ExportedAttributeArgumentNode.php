<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;

class ExportedAttributeArgumentNode implements ExportedNode, JsonSerializable
{

	public function __construct(
		private ?string $name,
		private string $value,
		private bool $byRef,
	)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->name === $node->name
			   && $this->value === $node->value
			   && $this->byRef === $node->byRef;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['name'],
			$properties['value'],
			$properties['byRef'],
		);
	}

	/**
	 * @return mixed
	 */
	public function jsonSerialize()
	{
		return [
			'type' => self::class,
			'data' => [
				'name' => $this->name,
				'value' => $this->value,
				'byRef' => $this->byRef,
			],
		];
	}

	/**
	 * @param mixed[] $data
	 * @return self
	 */
	public static function decode(array $data): ExportedNode
	{
		return new self(
			$data['name'],
			$data['value'],
			$data['byRef'],
		);
	}

}
