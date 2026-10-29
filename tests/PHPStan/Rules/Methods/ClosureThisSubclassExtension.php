<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\Stmt\Class_;
use PHPStan\Analyser\DependencyTracker;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\FunctionParameterClosureThisExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use function in_array;

class ClosureThisSubclassExtension implements FunctionParameterClosureThisExtension
{

	public function __construct(private ReflectionProvider $reflectionProvider)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return in_array($functionReflection->getName(), ['ClosureThisSubclass\\runInSubclass', 'ClosureThisSubclass\\runInBase'], true)
			&& $parameter->getName() === 'callback';
	}

	public function getClosureThisTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, ParameterReflection $parameter, Scope&DependencyTracker $scope): ?Type
	{
		if ($functionReflection->getName() === 'ClosureThisSubclass\\runInBase') {
			return new ObjectType('ClosureThisSubclass\\BaseTestCase');
		}

		$classNode = new Class_(null, [
			'extends' => new FullyQualified('ClosureThisSubclass\\BaseTestCase'),
		], $functionCall->getAttributes());
		$classReflection = $this->reflectionProvider->getAnonymousClassReflection($classNode, $scope);

		return new ObjectType($classReflection->getName(), classReflection: $classReflection);
	}

}
