<?php declare(strict_types = 1);

namespace PHPStan\PhpStormMeta;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\Type;
use function array_map;
use function count;
use function in_array;
use function sprintf;
use function strtolower;

class NonStaticMethodReturnTypeResolver implements DynamicMethodReturnTypeExtension
{

	/** @var list<string> */
	private readonly array $methodNames;

	/**
	 * @param list<string> $methodNames
	 */
	public function __construct(
		private readonly TypeFromMetaResolver $metaResolver,
		private readonly string $className,
		array $methodNames,
	)
	{
		$this->methodNames = array_map(strtolower(...), $methodNames);
	}

	public function getClass(): string
	{
		return $this->className;
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		$methodName = strtolower($methodReflection->getName());

		return in_array($methodName, $this->methodNames, true);
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope,
	): ?Type
	{
		$methodName = strtolower($methodReflection->getName());
		$args = $methodCall->getArgs();

		if (count($args) > 0) {
			$fqn = sprintf('%s::%s', $this->className, $methodName);
			return $this->metaResolver->resolveReferencedType($fqn, ...$args);
		}

		return null;
	}

}
