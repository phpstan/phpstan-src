<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function count;

/**
 * Matches declared and actual types to collect constraints on unresolved
 * template arguments. All accumulation is local to a call; the returned
 * constraints and the scope's inference context are immutable.
 */
#[AutowiredService]
final class TemplateArgumentObserver
{

	public function collectSites(Type $type): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$constraints): Type {
			if ($type instanceof UnresolvedTemplateArgumentType) {
				$constraints = $constraints->withSite($type);
				$initial = $type->getInitialType();
				if ($initial !== null) {
					$traverse($initial);
				}
				return $type;
			}
			return $traverse($type);
		});
		return $constraints;
	}

	/** Skip ordinary recursive generic relationships that cannot contribute a constraint. */
	private function containsMarker(Type $type): bool
	{
		$contains = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$contains): Type {
			if ($type instanceof UnresolvedTemplateArgumentType) {
				$contains = true;
			}
			return $contains ? $type : $traverse($type);
		});
		return $contains;
	}

	public function collectSend(Type $declared, Type $actual): TemplateArgumentConstraints
	{
		return $this->observeSend(TemplateArgumentConstraints::createEmpty(), $declared, $actual);
	}

	public function collectArgument(Type $parameterType, Type $argumentType): TemplateArgumentConstraints
	{
		return $this->observeArgument(TemplateArgumentConstraints::createEmpty(), $parameterType, $argumentType);
	}

	/**
	 * $actual flows into $declared: a property's writable type, a parameter
	 * type, a declared return type, a @var type.
	 */
	private function observeSend(TemplateArgumentConstraints $constraints, Type $declared, Type $actual): TemplateArgumentConstraints
	{
		if ($declared instanceof TemplateType || !$this->containsMarker($actual)) {
			return $constraints;
		}
		if ($actual instanceof UnionType) {
			foreach ($actual->getTypes() as $member) {
				$constraints = $this->observeSend($constraints, $declared, $member);
			}

			return $constraints;
		}
		if ($declared instanceof UnionType) {
			foreach ($declared->getTypes() as $member) {
				$constraints = $this->observeSend($constraints, $member, $actual);
			}

			return $constraints;
		}
		if ($actual instanceof UnresolvedTemplateArgumentType) {
			// a bare marker is a derived value (Foo<T>::get()) and never constrains
			return $constraints;
		}
		if ($actual instanceof NeverType) {
			// never holds no markers, and is its own iterable key and value type
			return $constraints;
		}

		$actualReflections = $actual->getObjectClassReflections();
		if (count($actualReflections) === 1) {
			$declaredReflections = $declared->getObjectClassReflections();
			if (count($declaredReflections) !== 1) {
				return $constraints;
			}
			$declaredReflection = $declaredReflections[0];

			// the declared type names an ancestor: its arguments map onto the
			// object's through @extends/@implements
			$ancestor = $actualReflections[0]->getAncestorWithClassName($declaredReflection->getName());
			if ($ancestor === null || !$ancestor->isGeneric()) {
				return $constraints;
			}

			$templates = $ancestor->typeMapToList($ancestor->getTemplateTypeMap());
			$declaredArguments = $declaredReflection->typeMapToList($declaredReflection->getActiveTemplateTypeMap());
			$declaredVariances = $declaredReflection->getCallSiteVarianceMap();
			foreach ($ancestor->typeMapToList($ancestor->getActiveTemplateTypeMap()) as $i => $argument) {
				$template = $templates[$i] ?? null;
				if (!$template instanceof TemplateType || !isset($declaredArguments[$i])) {
					continue;
				}
				$declaredArgument = $declaredArguments[$i];
				if (!$argument instanceof UnresolvedTemplateArgumentType) {
					$constraints = $this->observeSend($constraints, $declaredArgument, $argument);
					continue;
				}
				if (self::isUninformativeSendTarget($declaredArgument)) {
					if ($declaredArgument instanceof MixedType && !$declaredArgument instanceof TemplateType) {
						// mixed accepts every argument, so it decides nothing - but the
						// object did leave the body through it, which is more than the
						// untouched `new Foo()` that resolves to never. A target that
						// still carries template types is not such a signal: it is not
						// a target yet.
						$constraints = $constraints->withUnconstrainingSend($argument);
					}

					continue;
				}

				$callSiteVariance = $declaredVariances->getVariance($template->getName()) ?? TemplateTypeVariance::createInvariant();
				$effectiveVariance = $callSiteVariance->invariant() ? $template->getVariance() : $callSiteVariance;
				$constraints = $constraints->withSend($argument, $declaredArgument, $effectiveVariance);

				// a site whose inferred argument itself carries markers (wrap(new Foo(1)))
				$initial = $argument->getInitialType();
				if ($initial === null) {
					continue;
				}
				$constraints = $this->observeSend($constraints, $declaredArgument, $initial);
			}

			return $constraints;
		}

		if (count($actualReflections) > 0 || $actual->isObject()->yes()) {
			return $constraints;
		}

		if (!$actual->isIterable()->yes() || !$declared->isIterable()->yes()) {
			return $constraints;
		}

		$constraints = $this->observeSend($constraints, $declared->getIterableKeyType(), $actual->getIterableKeyType());
		$constraints = $this->observeSend($constraints, $declared->getIterableValueType(), $actual->getIterableValueType());

		return $constraints;
	}

	/**
	 * An argument was passed to a parameter: the argument's markers are sent to
	 * the parameter type, and a parameter type carrying the receiver's markers
	 * (add(T $x) on Foo<unresolved>) puts the argument as a lower bound on them.
	 */
	private function observeArgument(TemplateArgumentConstraints $constraints, Type $parameterType, Type $argumentType): TemplateArgumentConstraints
	{
		$constraints = $this->observeSend($constraints, $parameterType, $argumentType);
		$constraints = $this->observeLowerBound($constraints, $parameterType, $argumentType);

		return $constraints;
	}

	private function observeLowerBound(TemplateArgumentConstraints $constraints, Type $parameterType, Type $argumentType): TemplateArgumentConstraints
	{
		if (!$this->containsMarker($parameterType)) {
			return $constraints;
		}
		if ($parameterType instanceof UnresolvedTemplateArgumentType) {
			$constraints = $constraints->withLowerBound($parameterType, $argumentType);
			return $constraints;
		}
		if ($parameterType instanceof NeverType) {
			// never is its own iterable key and value type
			return $constraints;
		}
		if ($parameterType instanceof TemplateType || $parameterType->isCallable()->yes()) {
			// callable parameters put the template in a contravariant position:
			// what they say about it is an upper bound, not something flowing in
			return $constraints;
		}
		if ($parameterType instanceof UnionType) {
			foreach ($parameterType->getTypes() as $member) {
				$constraints = $this->observeLowerBound($constraints, $member, $argumentType);
			}

			return $constraints;
		}
		if ($argumentType instanceof UnionType) {
			foreach ($argumentType->getTypes() as $member) {
				$constraints = $this->observeLowerBound($constraints, $parameterType, $member);
			}

			return $constraints;
		}

		$parameterReflections = $parameterType->getObjectClassReflections();
		if (count($parameterReflections) === 1) {
			$parameterReflection = $parameterReflections[0];
			if (!$parameterReflection->isGeneric()) {
				return $constraints;
			}
			$argumentReflections = $argumentType->getObjectClassReflections();
			if (count($argumentReflections) !== 1) {
				return $constraints;
			}
			$ancestor = $argumentReflections[0]->getAncestorWithClassName($parameterReflection->getName());
			if ($ancestor === null) {
				return $constraints;
			}
			$ancestorArguments = $ancestor->typeMapToList($ancestor->getActiveTemplateTypeMap());
			foreach ($parameterReflection->typeMapToList($parameterReflection->getActiveTemplateTypeMap()) as $i => $parameterArgument) {
				if (!isset($ancestorArguments[$i])) {
					continue;
				}
				$constraints = $this->observeLowerBound($constraints, $parameterArgument, $ancestorArguments[$i]);
			}

			return $constraints;
		}

		if (count($parameterReflections) > 0 || $parameterType->isObject()->yes()) {
			return $constraints;
		}

		if (!$parameterType->isIterable()->yes() || !$argumentType->isIterable()->yes()) {
			return $constraints;
		}

		$constraints = $this->observeLowerBound($constraints, $parameterType->getIterableKeyType(), $argumentType->getIterableKeyType());
		$constraints = $this->observeLowerBound($constraints, $parameterType->getIterableValueType(), $argumentType->getIterableValueType());

		return $constraints;
	}

	private static function isUninformativeSendTarget(Type $declaredArgument): bool
	{
		// Foo<mixed> accepts every Foo<X> (TemplateTypeVariance::isValidVariance)
		// and a declared argument with unresolved template types is no target yet
		return ($declaredArgument instanceof MixedType && !$declaredArgument instanceof TemplateType)
			|| $declaredArgument->hasTemplateOrLateResolvableType();
	}

}
