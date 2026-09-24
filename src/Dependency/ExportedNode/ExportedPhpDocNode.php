<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Dependency\ExportedNameScope;
use PHPStan\Dependency\ExportedNode;
use ReturnTypeWillChange;

final class ExportedPhpDocNode implements ExportedNode, JsonSerializable
{

	/**
	 * The name scope is shared by all PHPDocs exported from the same part of a file, and serialize()
	 * keeps shared objects shared, so the result cache stores the uses of a file once, not per PHPDoc.
	 */
	public function __construct(private string $phpDocString, private ExportedNameScope $nameScope)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->phpDocString === $node->phpDocString
			&& $this->nameScope->equals($node->nameScope);
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
				'namespace' => $this->nameScope->getNamespace(),
				'uses' => $this->nameScope->getUses(),
				'constUses' => $this->nameScope->getConstUses(),
			],
		];
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['phpDocString'], new ExportedNameScope($properties['namespace'], $properties['uses'], $properties['constUses'] ?? []));
	}

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data): self
	{
		return new self($data['phpDocString'], ExportedNameScope::decode($data['namespace'], $data['uses'], $data['constUses'] ?? []));
	}

}
