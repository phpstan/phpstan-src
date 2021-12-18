<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Broker\ClassNotFoundException;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Rules\Rule;

class AnonymousClassNameRule implements Rule
{

	private ReflectionProvider $reflectionProvider;

	public function __construct(ReflectionProvider $reflectionProvider)
	{
		$this->reflectionProvider = $reflectionProvider;
	}

	public function getNodeType(): string
	{
		return Class_::class;
	}

	/**
	 * @param Class_ $node
	 * @return string[]
	 */
	public function processNode(Node $node, Scope $scope): array
	{
		$className = isset($node->namespacedName)
			? (string) $node->namespacedName
			: (string) $node->name;
		try {
			$this->reflectionProvider->getClass($className);
		} catch (ClassNotFoundException) {
			return ['not found'];
		}

		return ['found'];
	}

}
