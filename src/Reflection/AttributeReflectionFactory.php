<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\AttributeGroup;
use PhpParser\Node\Expr;
use PHPStan\BetterReflection\Reflection\Adapter\FakeReflectionAttribute;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionAttribute;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ReflectionProvider\ReflectionProviderProvider;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function count;
use function is_int;

#[AutowiredService]
final class AttributeReflectionFactory
{

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ReflectionProviderProvider $reflectionProviderProvider,
	)
	{
	}

	/**
	 * @param list<ReflectionAttribute|FakeReflectionAttribute> $reflections
	 * @return list<AttributeReflection>
	 */
	public function fromNativeReflection(array $reflections, InitializerExprContext $context): array
	{
		$attributes = [];
		foreach ($reflections as $reflection) {
			$attribute = $this->fromNameAndArgumentExpressions($reflection->getName(), $reflection->getArgumentsExpressions(), $context);
			if ($attribute === null) {
				continue;
			}

			$attributes[] = $attribute;
		}

		return $attributes;
	}

	/**
	 * @param AttributeGroup[] $attrGroups
	 * @return list<AttributeReflection>
	 */
	public function fromAttrGroups(array $attrGroups, InitializerExprContext $context): array
	{
		$attributes = [];
		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$arguments = [];
				foreach ($attr->args as $i => $arg) {
					if ($arg->name === null) {
						$argName = $i;
					} else {
						$argName = $arg->name->toString();
					}

					$arguments[$argName] = $arg->value;
				}
				$attributeReflection = $this->fromNameAndArgumentExpressions($attr->name->toString(), $arguments, $context);
				if ($attributeReflection === null) {
					continue;
				}

				$attributes[] = $attributeReflection;
			}
		}

		return $attributes;
	}

	/**
	 * @param array<int|string, Expr> $arguments
	 */
	private function fromNameAndArgumentExpressions(string $name, array $arguments, InitializerExprContext $context): ?AttributeReflection
	{
		if (count($arguments) === 0) {
			return new AttributeReflection($name, []);
		}

		$reflectionProvider = $this->reflectionProviderProvider->getReflectionProvider();
		if (!$reflectionProvider->hasClass($name)) {
			return null;
		}

		$classReflection = $reflectionProvider->getClass($name);
		if (!$classReflection->hasConstructor()) {
			return null;
		}

		if (!$classReflection->isAttributeClass()) {
			return null;
		}

		$constructor = $classReflection->getConstructor();
		$parameters = $constructor->getOnlyVariant()->getParameters();
		$namedArgTypes = [];
		foreach ($arguments as $i => $argExpr) {
			if (is_int($i)) {
				if (isset($parameters[$i])) {
					$namedArgTypes[$parameters[$i]->getName()] = $this->initializerExprTypeResolver->getType($argExpr, $context);
					continue;
				}
				if (count($parameters) > 0) {
					$lastParameter = $parameters[count($parameters) - 1];
					if ($lastParameter->isVariadic()) {
						$parameterName = $lastParameter->getName();
						if (array_key_exists($parameterName, $namedArgTypes)) {
							$namedArgTypes[$parameterName] = TypeCombinator::union($namedArgTypes[$parameterName], $this->initializerExprTypeResolver->getType($argExpr, $context));
							continue;
						}
						$namedArgTypes[$parameterName] = $this->initializerExprTypeResolver->getType($argExpr, $context);
					}
				}
				continue;
			}

			foreach ($parameters as $parameter) {
				if ($parameter->getName() !== $i) {
					continue;
				}

				$namedArgTypes[$i] = $this->initializerExprTypeResolver->getType($argExpr, $context);
				break;
			}
		}

		return new AttributeReflection($classReflection->getName(), $namedArgTypes);
	}

}
