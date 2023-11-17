<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\ClassPropertyNode;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\CallableType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function sprintf;

/**
 * @implements Rule<ClassPropertyNode>
 */
class InvalidCallablePropertyTypeRule implements Rule
{

	public function getNodeType(): string
	{
		return ClassPropertyNode::class;
	}

	public function processNode(Node $node, Scope $scope): array
	{
		$classReflection = $node->getClassReflection();
		$propertyReflection = $classReflection->getNativeProperty($node->getName());

		if (!$propertyReflection->hasNativeType()) {
			return [];
		}

		$nativeType = $propertyReflection->getNativeType();
		$callableTypes = [];

		TypeTraverser::map($nativeType, static function (Type $type, callable $traverse) use (&$callableTypes): Type {
			if ($type instanceof UnionType || $type instanceof IntersectionType) {
				return $traverse($type);
			}

			if ($type instanceof CallableType) {
				$callableTypes[] = $type;
			}

			return $type;
		});

		if ($callableTypes === []) {
			return [];
		}

		return [
			RuleErrorBuilder::message(sprintf(
				'Property %s::$%s cannot have callable in its type declaration.',
				$classReflection->getDisplayName(),
				$node->getName(),
			))->identifier('property.callableType')->nonIgnorable()->build(),
		];
	}

}
