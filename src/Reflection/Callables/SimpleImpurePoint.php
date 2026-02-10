<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Callables;

use PhpParser\Node\Arg;
use PHPStan\Analyser\ImpurePoint;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Type\Type;
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
	 * @param ImpurePointIdentifier $identifier Category of the side effect
	 * @param string $description Human-readable description of the impure action
	 * @param bool $certain Whether the side effect is certain (true) or possible (false)
	 */
	public function __construct(
		private string $identifier,
		private string $description,
		private bool $certain,
	)
	{
	}

	/**
	 * Creates a SimpleImpurePoint from a function/method and its selected variant.
	 *
	 * Returns null if the function is known to be pure (no side effects).
	 * Handles special cases like print_r() where a parameter can flip the
	 * function between impure (prints to output) and pure (returns string).
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
	 * Returns the category identifier for this side effect (e.g. "functionCall", "methodCall").
	 *
	 * @return ImpurePointIdentifier
	 */
	public function getIdentifier(): string
	{
		return $this->identifier;
	}

	/** Returns a human-readable description of the impure action. */
	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * Whether the side effect is certain (vs. merely possible).
	 *
	 * Certain when the function is known to be impure (e.g. void return, or
	 * explicitly marked @phpstan-impure). Uncertain when purity is unknown.
	 */
	public function isCertain(): bool
	{
		return $this->certain;
	}

}
