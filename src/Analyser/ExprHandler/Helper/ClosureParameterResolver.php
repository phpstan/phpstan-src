<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\Analyser\ClosureParameterTypes;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

/**
 * Resolves closure parameters for the body walk: the contextual parameters
 * (ContextualClosureParameterResolver), refined from the arguments of a call
 * that invokes the closure in place. Pricing those arguments needs the closure
 * types of nested closures, which is why this part is separate from what
 * ClosureTypeResolver itself builds parameters through.
 */
#[AutowiredService]
final class ClosureParameterResolver
{

	public function __construct(
		private NodeScopeResolver $nodeScopeResolver,
		private ClosureTypeResolver $closureTypeResolver,
		private ContextualClosureParameterResolver $contextualClosureParameterResolver,
	)
	{
	}

	/** @param Node\Arg[]|null $callArgs */
	public function resolve(
		MutatingScope $scope,
		Node\Expr\Closure|Node\Expr\ArrowFunction $expr,
		?ExpressionResultStorage $storage,
		?array $callArgs,
		?Type $passedToType,
		?Type $nativePassedToType,
	): ClosureParameterTypes
	{
		if ($callArgs === null || $this->contextualClosureParameterResolver->hasIntrinsicArgs($expr)) {
			return $this->contextualClosureParameterResolver->resolve($scope, $expr, $storage, $passedToType, $nativePassedToType);
		}

		return new ClosureParameterTypes(
			$this->createCallArgsParameters($scope, $expr, $callArgs, fn (MutatingScope $s, Expr $e): Type => $this->resolveCallableTypeForScope($e, $s)),
			$this->createCallArgsParameters($scope, $expr, $callArgs, fn (MutatingScope $s, Expr $e): Type => $this->resolveCallableTypeForScope($e, $s->doNotTreatPhpDocTypesAsCertain())),
		);
	}

	/**
	 * Resolves the type of an expression a callable parameter is derived from -
	 * either the closure/arrow function whose acceptors describe the parameters,
	 * or a call argument refining them. A closure/arrow function is resolved
	 * directly through ClosureTypeResolver (as Scope::getType() would), not by
	 * processing it on demand: createCallArgsParameters() runs while that very
	 * closure is being processed, so on-demand processing would re-enter
	 * ClosureProcessor::processClosureNodeInternal() endlessly.
	 */
	public function resolveCallableTypeForScope(Expr $expr, MutatingScope $scope): Type
	{
		if ($expr instanceof Expr\Closure || $expr instanceof Expr\ArrowFunction) {
			return $this->closureTypeResolver->getClosureType($scope, $expr, false, $scope->getCurrentExpressionResultStorage());
		}

		return $this->nodeScopeResolver->readTypeOfMaybeStored($expr, $scope);
	}

	/**
	 * @param Node\Arg[] $args
	 * @param Closure(MutatingScope, Expr): Type $typeGetter
	 * @return ParameterReflection[]|null
	 */
	private function createCallArgsParameters(MutatingScope $scope, Expr $closureExpr, array $args, Closure $typeGetter): ?array
	{
		$closureType = $typeGetter($scope, $closureExpr);
		if ($closureType->isCallable()->no()) {
			return null;
		}

		$callableParameters = null;
		$acceptors = $closureType->getCallableParametersAcceptors($scope);
		if (count($acceptors) === 1) {
			$callableParameters = $acceptors[0]->getParameters();

			foreach ($callableParameters as $index => $callableParameter) {
				if (!isset($args[$index])) {
					continue;
				}

				if ($callableParameter->isVariadic()) {
					$argTypes = [];
					$argNumber = count($args);
					for ($j = $index; $j < $argNumber; $j++) {
						$argTypes[] = $typeGetter($scope, $args[$j]->value);
					}
					$type = TypeCombinator::union(...$argTypes);
				} else {
					$type = $typeGetter($scope, $args[$index]->value);
				}
				$callableParameters[$index] = new NativeParameterReflection(
					$callableParameter->getName(),
					$callableParameter->isOptional(),
					$type,
					$callableParameter->passedByReference(),
					$callableParameter->isVariadic(),
					$callableParameter->getDefaultValue(),
				);
			}
		}

		return $callableParameters;
	}

}
