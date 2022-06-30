<?php declare(strict_types = 1);

namespace PHPStan\Type;

use Closure;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionParameter;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionType;
use PHPStan\BetterReflection\Reflection\ReflectionFunction as BetterReflectionFunction;
use PHPStan\BetterReflection\Reflection\ReflectionParameter as BetterReflectionParameter;
use PHPStan\Reflection\InitializerExprContext;
use PHPStan\Reflection\InitializerExprTypeResolver;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\PassedByReference;
use function array_map;

/** @api */
class ClosureTypeFactory
{

	public function __construct(private InitializerExprTypeResolver $initializerExprTypeResolver)
	{
	}

	/**
	 * @param Closure(): mixed $closure
	 */
	public function fromClosureObject(Closure $closure): ClosureType
	{
		$betterReflectionFunction = BetterReflectionFunction::createFromClosure($closure);

		$parameters = array_map(fn (BetterReflectionParameter $parameter) => new class($parameter, $this->initializerExprTypeResolver) implements ParameterReflection {

				public function __construct(private BetterReflectionParameter $reflection, private InitializerExprTypeResolver $initializerExprTypeResolver)
				{
				}

				public function getName(): string
				{
					return $this->reflection->getName();
				}

				public function isOptional(): bool
				{
					return $this->reflection->isOptional();
				}

				public function getType(): Type
				{
					return TypehintHelper::decideTypeFromReflection(ReflectionType::fromTypeOrNull($this->reflection->getType()), null, null, $this->reflection->isVariadic());
				}

				public function passedByReference(): PassedByReference
				{
					return $this->reflection->isPassedByReference()
						? PassedByReference::createCreatesNewVariable()
						: PassedByReference::createNo();
				}

				public function isVariadic(): bool
				{
					return $this->reflection->isVariadic();
				}

				public function getDefaultValue(): ?Type
				{
					if (! $this->reflection->isDefaultValueAvailable()) {
						return null;
					}

					return $this->initializerExprTypeResolver->getType($this->reflection->getDefaultValueExpr(), InitializerExprContext::fromReflectionParameter(new ReflectionParameter($this->reflection)));
				}

		}, $betterReflectionFunction->getParameters());

		return new ClosureType($parameters, TypehintHelper::decideTypeFromReflection(ReflectionType::fromTypeOrNull($betterReflectionFunction->getReturnType())), $betterReflectionFunction->isVariadic());
	}

}
