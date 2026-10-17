<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PhpParser\Node;
use PHPStan\Turbo\ShadowedByTurboExtension;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/ClassNameNodePair.cpp')]
final class ClassNameNodePair
{

	/**
	 * @param non-empty-string $className
	 */
	public function __construct(private string $className, private Node $node)
	{
	}

	/**
	 * @return non-empty-string
	 */
	public function getClassName(): string
	{
		return $this->className;
	}

	public function getNode(): Node
	{
		return $this->node;
	}

}
