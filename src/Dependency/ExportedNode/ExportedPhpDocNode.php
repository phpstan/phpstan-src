<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Dependency\ExportedNode;
use ReturnTypeWillChange;

final class ExportedPhpDocNode implements ExportedNode, JsonSerializable
{

	/**
	 * @param array<string, string> $uses alias(string) => fullName(string)
	 * @param array<string, string> $constUses alias(string) => fullName(string)
	 */
	public function __construct(private string $phpDocString, private ?string $namespace, private array $uses, private array $constUses)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->phpDocString === $node->phpDocString
			&& $this->namespace === $node->namespace
			&& $this->uses === $node->uses
			&& $this->constUses === $node->constUses;
	}

	/**
	 * @return mixed
	 */
	#[ReturnTypeWillChange]
	#[Override]
	public function jsonSerialize()
	{
		return [
			'type' => self::class,
			'data' => [
				'phpDocString' => $this->phpDocString,
				'namespace' => $this->namespace,
				'uses' => $this->uses,
				'constUses' => $this->constUses,
			],
		];
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['phpDocString'], $properties['namespace'], $properties['uses'], $properties['constUses'] ?? []);
	}

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data): self
	{
		return new self($data['phpDocString'], $data['namespace'], $data['uses'], $data['constUses'] ?? []);
	}

}
