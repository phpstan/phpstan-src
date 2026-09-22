<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Error;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\DynamicFunctionThrowTypeExtension;
use PHPStan\Type\ObjectType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;
use TypeError;
use function count;

/**
 * get_class() throws TypeError for an argument that is not an object, and
 * Error when called without an argument outside of a class.
 */
#[AutowiredService]
final class GetClassFunctionThrowTypeExtension implements DynamicFunctionThrowTypeExtension
{

	public function __construct(private PhpVersion $phpVersion)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'get_class';
	}

	public function getThrowTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, Scope $scope): ?Type
	{
		if (!$this->phpVersion->throwsValueErrorForInternalFunctions()) {
			return new VoidType();
		}

		$args = $funcCall->getArgs();
		foreach ($args as $arg) {
			if ($arg->unpack || $arg->name !== null) {
				return $functionReflection->getThrowType();
			}
		}

		if (count($args) === 0) {
			return $scope->isInClass() ? new VoidType() : new ObjectType(Error::class);
		}

		if ($scope->getNativeType($args[0]->value)->isObject()->yes()) {
			return new VoidType();
		}

		return new ObjectType(TypeError::class);
	}

}
