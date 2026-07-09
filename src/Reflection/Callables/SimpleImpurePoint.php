<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PhpParser\Node\Arg;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function count;
use function sprintf;

/**
 * Represents a point where a callable may have side effects (impure behavior).
 *
 * Used by CallableParametersAcceptor::getImpurePoints() to describe what side effects
 * a closure or callable value may have. Each impure point has an identifier (e.g.
 * "functionCall", "methodCall"), a human-readable description, and a certainty flag.
 *
 * PHPStan uses impure points to:
 * - Detect calls to impure functions inside @phpstan-pure contexts
 * - Report unused return values of pure functions (expr.resultUnused)
 * - Determine whether expressions have side effects
 *
 * @phpstan-import-type ImpurePointIdentifier from ImpurePoint
 */
final class SimpleImpurePoint
{

	private const SIDE_EFFECT_FLIP_PARAMETERS = [
		// functionName => [name, pos, testName]
		'print_r' => ['return', 1, 'isTruthy'],
		'var_export' => ['return', 1, 'isTruthy'],
		'highlight_string' => ['return', 1, 'isTruthy'],
	];

	/**
	 * @param ImpurePointIdentifier $identifier
	 */
	public function __construct(
		private string $identifier,
		private string $description,
		private bool $certain,
	)
	{
	}

	/**
	 * Returns null if the function is known to be pure (no side effects).
	 *
	 * @param Arg[] $args
	 */
	public static function createFromVariant(FunctionReflection|ExtendedMethodReflection $function, ?ParametersAcceptor $variant, ?Scope $scope = null, array $args = []): ?self
	{
		if (!$function->hasSideEffects()->no()) {
			$certain = $function->isPure()->no();
			if ($variant !== null) {
				$certain = $certain || $variant->getReturnType()->isVoid()->yes();
			}

			if (!$certain && $scope !== null && $variant !== null) {
				// A function can carry both flags at once (e.g. preg_replace_callback,
				// which is pure unless its callback is impure or its $count is passed).
				// It stays pure only when both verdicts agree it is pure, so combine
				// them: Yes = pure, No = impure, Maybe = possibly impure.
				$verdict = self::resolvePureUnlessCallableIsImpureVerdict($variant, $scope, $args);
				$passedVerdict = self::resolvePureUnlessParameterPassedVerdict($variant, $args);
				if ($passedVerdict !== null) {
					$verdict = $verdict === null ? $passedVerdict : $verdict->and($passedVerdict);
				}

				if ($verdict !== null) {
					if ($verdict->yes()) {
						return null;
					}
					if ($verdict->no()) {
						$certain = true;
					}
				}
			}

			if ($function instanceof FunctionReflection) {
				if (isset(self::SIDE_EFFECT_FLIP_PARAMETERS[$function->getName()]) && $scope !== null) {
					[
						$flipParameterName,
						$flipParameterPosition,
						$testName,
					] = self::SIDE_EFFECT_FLIP_PARAMETERS[$function->getName()];

					$sideEffectFlipped = false;
					$hasNamedParameter = false;
					$checker = [
						'isNotNull' => static fn (Type $type) => $type->isNull()->no(),
						'isTruthy' => static fn (Type $type) => $type->toBoolean()->isTrue()->yes(),
					][$testName];

					foreach ($args as $i => $arg) {
						$isFlipParameter = false;

						if ($arg->name !== null) {
							$hasNamedParameter = true;
							if ($arg->name->name === $flipParameterName) {
								$isFlipParameter = true;
							}
						}

						if (!$hasNamedParameter && $i === $flipParameterPosition) {
							$isFlipParameter = true;
						}

						if ($isFlipParameter) {
							$sideEffectFlipped = $checker($scope->getType($arg->value));
							break;
						}
					}

					if ($sideEffectFlipped) {
						return null;
					}
				}

				return new SimpleImpurePoint(
					'functionCall',
					sprintf('call to function %s()', $function->getName()),
					$certain,
				);
			}

			return new SimpleImpurePoint(
				'methodCall',
				sprintf('call to method %s::%s()', $function->getDeclaringClass()->getDisplayName(), $function->getName()),
				$certain,
			);
		}

		return null;
	}

	/**
	 * Combined purity verdict of all arguments passed to parameters flagged
	 * with @pure-unless-callable-is-impure. Returns null when the variant has
	 * no such parameters (so the caller keeps its current behavior). Shared with
	 * NewHandler, which applies it to constructor calls.
	 *
	 * @param Arg[] $args
	 */
	public static function resolvePureUnlessCallableIsImpureVerdict(ParametersAcceptor $variant, Scope $scope, array $args): ?TrinaryLogic
	{
		$parameters = $variant->getParameters();
		$verdict = null;

		foreach ($parameters as $parameterIndex => $parameter) {
			if (!$parameter instanceof ExtendedParameterReflection) {
				continue;
			}
			if ($parameter->isPureUnlessCallableIsImpureParameter()->no()) {
				continue;
			}

			$verdict ??= TrinaryLogic::createYes();

			$matchedArg = null;
			$hasNamedParameter = false;
			foreach ($args as $i => $arg) {
				if ($arg->name !== null) {
					$hasNamedParameter = true;
					if ($arg->name->name === $parameter->getName()) {
						$matchedArg = $arg;
						break;
					}

					continue;
				}

				if (!$hasNamedParameter && $i === $parameterIndex) {
					$matchedArg = $arg;
					break;
				}
			}

			if ($matchedArg === null) {
				// Optional callback omitted (e.g. array_filter($arr)) - pure.
				continue;
			}

			$argType = $scope->getType($matchedArg->value);
			if ($argType->isNull()->yes()) {
				// Explicit null callback (e.g. array_filter($arr, null)) - pure.
				continue;
			}

			if (!$argType->isCallable()->yes()) {
				$verdict = $verdict->and(TrinaryLogic::createMaybe());
				continue;
			}

			$acceptors = $argType->getCallableParametersAcceptors($scope);
			if (count($acceptors) === 0) {
				$verdict = $verdict->and(TrinaryLogic::createMaybe());
				continue;
			}

			foreach ($acceptors as $acceptor) {
				$verdict = $verdict->and($acceptor->isPure());
			}
		}

		return $verdict;
	}

	/**
	 * Purity verdict for parameters flagged with @pure-unless-parameter-passed:
	 * the call stays pure as long as none of those (by-ref out) parameters
	 * received an argument. Returns Yes when no flagged parameter was passed,
	 * No when at least one was, and null when the variant has no such parameters
	 * (so the caller keeps its current behavior).
	 *
	 * @param Arg[] $args
	 */
	public static function resolvePureUnlessParameterPassedVerdict(ParametersAcceptor $variant, array $args): ?TrinaryLogic
	{
		$parameters = $variant->getParameters();
		$verdict = null;

		foreach ($parameters as $parameterIndex => $parameter) {
			if (!$parameter instanceof ExtendedParameterReflection) {
				continue;
			}
			if ($parameter->isPureUnlessParameterPassedParameter()->no()) {
				continue;
			}

			$verdict ??= TrinaryLogic::createYes();

			$matchedArg = null;
			$hasUnpackedArg = false;
			$hasNamedParameter = false;
			foreach ($args as $i => $arg) {
				if ($arg->unpack) {
					$hasUnpackedArg = true;
					continue;
				}

				if ($arg->name !== null) {
					$hasNamedParameter = true;
					if ($arg->name->name === $parameter->getName()) {
						$matchedArg = $arg;
						break;
					}

					continue;
				}

				if (!$hasNamedParameter && $i === $parameterIndex) {
					$matchedArg = $arg;
					break;
				}
			}

			if ($matchedArg === null) {
				if ($hasUnpackedArg) {
					// An unpacked argument list (...$args) might supply the flagged
					// by-ref parameter, so we cannot be sure the call stays pure.
					$verdict = $verdict->and(TrinaryLogic::createMaybe());
				}

				continue;
			}

			if ($parameter->isPureUnlessParameterPassedParameter()->yes()) {
				$verdict = $verdict->and(TrinaryLogic::createNo());
				continue;
			}

			// The flag itself is uncertain (e.g. only one variant of a union type
			// declares @pure-unless-parameter-passed), so passing an argument here
			// only makes the call possibly impure, not certainly impure.
			$verdict = $verdict->and(TrinaryLogic::createMaybe());
		}

		return $verdict;
	}

	/** @return ImpurePointIdentifier */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	public function isCertain(): bool
	{
		return $this->certain;
	}

}
