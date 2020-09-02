<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use PHPStan\Dependency\ExportedNode;

class ExportedClassConstantNode implements ExportedNode, JsonSerializable
{

	private string $name;

	private string $value;

	private bool $public;

	private bool $private;

	public function __construct(string $name, string $value, bool $public, bool $private)
	{
		$this->name = $name;
		$this->value = $value;
		$this->public = $public;
		$this->private = $private;
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->name === $node->name
			&& $this->value === $node->value
			&& $this->public === $node->public
			&& $this->private === $node->private;
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
			$properties['public'],
			$properties['private']
		);
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
			$data['public'],
			$data['private']
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
				'public' => $this->public,
				'private' => $this->private,
			],
		];
	}

}
