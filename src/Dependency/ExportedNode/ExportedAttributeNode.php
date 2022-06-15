<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;
use PHPStan\ShouldNotHappenException;
use function array_map;
use function count;

class ExportedAttributeNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param ExportedAttributeArgumentNode[] $args
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

		if (count($this->args) !== count($node->args)) {
			return false;
		}

		foreach ($this->args as $i => $ourAttribute) {
			$theirAttribute = $node->args[$i];
			if (!$ourAttribute->equals($theirAttribute)) {
				return false;
			}
		}

		return $this->name === $node->name;
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
			array_map(static function (array $parameterData): ExportedAttributeArgumentNode {
				if ($parameterData['type'] !== ExportedAttributeArgumentNode::class) {
					throw new ShouldNotHappenException();
				}
				return ExportedAttributeArgumentNode::decode($parameterData['data']);
			}, $data['args']),
		);
	}

}
