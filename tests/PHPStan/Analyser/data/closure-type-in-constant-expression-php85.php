<?php

namespace ClosureTypeInConstantExpressionPhp85;

use Attribute;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\Type\CallableType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Type;
use PHPStan\Type\MixedType;
use function PHPStan\Testing\assertType;

class StaticMethodParameterClosureTypeExtension implements \PHPStan\Type\StaticMethodParameterClosureTypeExtension
{

	public function isStaticMethodSupported(MethodReflection $methodReflection, ParameterReflection $parameter): bool
	{
		if ($methodReflection->getName() !== '__construct') {
			return false;
		}

		if ($methodReflection->getDeclaringClass()->getName() !== Idempotency::class) {
			return false;
		}

		return $parameter->getName() === 'key';
	}

	public function getTypeFromStaticMethodCall(
		MethodReflection $methodReflection,
		StaticCall $methodCall,
		ParameterReflection $parameter,
		Scope $scope
	): ?Type {
		// Just some garbage, that doesn't throw an error anyway.
		return new CallableType(
			[
				new NativeParameterReflection('test', false, new FloatType(), PassedByReference::createNo(), false, null),
			],
			new MixedType()
		);

		// What we need here, is a way to find out that this is being called from `__invoke`
		// using $scope->getFunctionName()
		// Then we would want to find out the 1st parameter of __invoke
		// It would be SomeCommand.
		// Then we would want to return that the callable signature is `static function (SomeCommand): string`
		// But I'm not sure if this is the correct extension point at all...
		// It seems it's not.
	}
}

class SomeCommand {}

#[Attribute(flags: Attribute::TARGET_METHOD)]
final readonly class Idempotency
{
	/**
	 * @param Closure(object): string $key
	 */
	public function __construct(
		public Closure $key,
	) {
	}
}

class SomeHandler
{
	#[Idempotency(key: static function (object $command) : string {
		assertType(SomeCommand::class, $command);

		return 'hello';
	})]
	public function __invoke(SomeCommand $command): void
	{
	}
}

