<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PhpParser\Node\Arg;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\ExtendedParameterReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use function array_key_exists;
use function array_map;
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
				$verdict = self::resolveConditionalPurityVerdict($variant, $scope, $args);
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
	 * A function can carry both flags at once (e.g. preg_replace_callback, which is
	 * pure unless its callback is impure or its $count is passed), so the two
	 * verdicts are combined. Returns null when the variant declares neither flag.
	 *
	 * @param Arg[] $args
	 */
	public static function resolveConditionalPurityVerdict(ParametersAcceptor $variant, Scope $scope, array $args): ?TrinaryLogic
	{
		$verdict = self::resolvePureUnlessCallableIsImpureVerdict($variant, $scope, $args);
		$passedVerdict = self::resolvePureUnlessParameterPassedVerdict($variant, $args);
		if ($passedVerdict === null) {
			return $verdict;
		}

		return $verdict === null ? $passedVerdict : $verdict->and($passedVerdict);
	}

	/**
	 * @param SimpleImpurePoint[] $impurePoints
	 * @param Arg[] $args
	 * @return SimpleImpurePoint[]
	 */
	public static function narrowByConditionalPurity(array $impurePoints, ParametersAcceptor $variant, Scope $scope, array $args): array
	{
		$verdict = self::resolveConditionalPurityVerdict($variant, $scope, $args);
		if ($verdict === null || $verdict->maybe()) {
			return $impurePoints;
		}

		if ($verdict->yes()) {
			return [];
		}

		return array_map(
			static fn (self $impurePoint) => $impurePoint->isCertain()
				? $impurePoint
				: new self($impurePoint->getIdentifier(), $impurePoint->getDescription(), true),
			$impurePoints,
		);
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
		$declaredParameterNames = self::collectParameterNames($parameters);
		$verdict = null;

		foreach ($parameters as $parameterIndex => $parameter) {
			if (!$parameter instanceof ExtendedParameterReflection) {
				continue;
			}
			if ($parameter->isPureUnlessCallableIsImpureParameter()->no()) {
				continue;
			}

			$verdict ??= TrinaryLogic::createYes();

			[$matchedArg, $hasUnpackedArg] = self::matchArgForParameter($args, $parameter, $parameterIndex, $declaredParameterNames);

			if ($matchedArg === null) {
				if ($hasUnpackedArg) {
					// An unpacked argument list (...$args) might supply the flagged
					// callable, so we cannot be sure the call stays pure.
					$verdict = $verdict->and(TrinaryLogic::createMaybe());

					continue;
				}

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
	 * Returns null when the variant has no flagged parameters.
	 *
	 * @param Arg[] $args
	 */
	public static function resolvePureUnlessParameterPassedVerdict(ParametersAcceptor $variant, array $args): ?TrinaryLogic
	{
		$parameters = $variant->getParameters();
		$declaredParameterNames = self::collectParameterNames($parameters);
		$verdict = null;

		foreach ($parameters as $parameterIndex => $parameter) {
			if (!$parameter instanceof ExtendedParameterReflection) {
				continue;
			}
			if ($parameter->isPureUnlessParameterPassedParameter()->no()) {
				continue;
			}

			$verdict ??= TrinaryLogic::createYes();

			[$matchedArg, $hasUnpackedArg] = self::matchArgForParameter($args, $parameter, $parameterIndex, $declaredParameterNames);

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

	/**
	 * @param ParameterReflection[] $parameters
	 * @return array<string, true>
	 */
	private static function collectParameterNames(array $parameters): array
	{
		$names = [];
		foreach ($parameters as $parameter) {
			$names[$parameter->getName()] = true;
		}

		return $names;
	}

	/**
	 * Finds the argument a flagged parameter receives at a call site.
	 *
	 * A trailing variadic parameter collects every positional argument from its own
	 * position onwards, and - because a named argument that matches no declared
	 * parameter is collected by the variadic as a string-keyed element - those named
	 * arguments too.
	 *
	 * @param Arg[] $args
	 * @param array<string, true> $declaredParameterNames
	 * @return array{?Arg, bool} the matched argument (null when the parameter received
	 *                           none) and whether an unpacked argument list might have
	 *                           supplied it
	 */
	private static function matchArgForParameter(array $args, ExtendedParameterReflection $parameter, int $parameterIndex, array $declaredParameterNames): array
	{
		$isVariadic = $parameter->isVariadic();
		$hasUnpackedArg = false;
		$hasNamedArg = false;

		foreach ($args as $i => $arg) {
			if ($arg->unpack) {
				$hasUnpackedArg = true;
				continue;
			}

			if ($arg->name !== null) {
				$hasNamedArg = true;
				if ($arg->name->name === $parameter->getName()) {
					return [$arg, $hasUnpackedArg];
				}

				if ($isVariadic && !array_key_exists($arg->name->name, $declaredParameterNames)) {
					return [$arg, $hasUnpackedArg];
				}

				continue;
			}

			if ($hasNamedArg) {
				continue;
			}

			if ($i === $parameterIndex || ($isVariadic && $i > $parameterIndex)) {
				return [$arg, $hasUnpackedArg];
			}
		}

		return [null, $hasUnpackedArg];
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
