<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;

class ExportedParameterNode implements ExportedNode, JsonSerializable
{

	private string $name;

	private ?string $type;

	private bool $byRef;

	private bool $variadic;

	private bool $hasDefault;

	public function __construct(
		string $name,
		?string $type,
		bool $byRef,
		bool $variadic,
		bool $hasDefault
	)
	{
		$this->name = $name;
		$this->type = $type;
		$this->byRef = $byRef;
		$this->variadic = $variadic;
		$this->hasDefault = $hasDefault;
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->name === $node->name
			&& $this->type === $node->type
			&& $this->byRef === $node->byRef
			&& $this->variadic === $node->variadic
			&& $this->hasDefault === $node->hasDefault;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['name'],
			$properties['type'],
			$properties['byRef'],
			$properties['variadic'],
			$properties['hasDefault']
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
				'type' => $this->type,
				'byRef' => $this->byRef,
				'variadic' => $this->variadic,
				'hasDefault' => $this->hasDefault,
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
			$data['type'],
			$data['byRef'],
			$data['variadic'],
			$data['hasDefault']
		);
	}

}
