<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use ReturnTypeWillChange;
use function count;

final class ExportedAttributeNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param array<int|string, string> $args argument name or index(string|int) => value expression (string)
	 */
	public function __construct(
		private string $name,
		private array $args,
	)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		if ($this->name !== $node->name) {
			return false;
		}

		if (count($this->args) !== count($node->args)) {
			return false;
		}

		foreach ($this->args as $argName => $argValue) {
			if (!isset($node->args[$argName]) || $argValue !== $node->args[$argName]) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param mixed[] $properties
	 * @return self
	 */
	public static function __set_state(array $properties): ExportedNode
	{
		return new self(
			$properties['name'],
			$properties['args'],
		);
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
				'name' => $this->name,
				'args' => $this->args,
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
			$data['args'],
		);
	}

}
