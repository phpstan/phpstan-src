<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Analyser\NamespaceUses;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\ExportedNodeDecoder;
use ReturnTypeWillChange;

final class ExportedPhpDocNode implements ExportedNode, JsonSerializable
{

	/**
	 * The NamespaceUses is shared by the PHPDocs written under the same use statements, so the
	 * result cache stores the imports of a file once instead of once per PHPDoc.
	 */
	public function __construct(private string $phpDocString, private NamespaceUses $namespaceUses)
	{
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		return $this->phpDocString === $node->phpDocString
			&& $this->namespaceUses->equals($node->namespaceUses);
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
				'namespace' => $this->namespaceUses->getNamespace(),
				'uses' => $this->namespaceUses->getUses(),
				'constUses' => $this->namespaceUses->getConstUses(),
			],
		];
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self($properties['phpDocString'], $properties['namespaceUses']);
	}

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data, ExportedNodeDecoder $decoder): self
	{
		return new self($data['phpDocString'], $decoder->getNamespaceUses($data['namespace'], $data['uses'], $data['constUses']));
	}

}
