<?php declare(strict_types = 1);

namespace PHPStan\Dependency\ExportedNode;

use JsonSerializable;
use Override;
use PHPStan\Dependency\ExportedNode;
use PHPStan\Dependency\RootExportedNode;
use PHPStan\ShouldNotHappenException;
use ReturnTypeWillChange;
use function array_map;
use function count;

final class ExportedConstantsNode implements RootExportedNode, JsonSerializable
{

	/**
	 * @param ExportedConstantNode[] $constants
	 */
	public function __construct(private array $constants)
	{
	}

	public function getType(): string
	{
		return self::TYPE_CONSTANT;
	}

	public function getName(): string
	{
		return $this->constants[0]->getName();
	}

	public function equals(ExportedNode $node): bool
	{
		if (!$node instanceof self) {
			return false;
		}

		if (count($this->constants) !== count($node->constants)) {
			return false;
		}

		foreach ($this->constants as $i => $constant) {
			if (!$constant->equals($node->constants[$i])) {
				return false;
			}
		}

		return true;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self(
			$properties['constants'],
		);
	}

	/**
	 * @param mixed[] $data
	 */
	public static function decode(array $data): self
	{
		return new self(
			array_map(static function (array $constantData): ExportedConstantNode {
				if ($constantData['type'] !== ExportedConstantNode::class) {
					throw new ShouldNotHappenException();
				}

				return ExportedConstantNode::decode($constantData['data']);
			}, $data['constants']),
		);
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
				'constants' => $this->constants,
			],
		];
	}

}
