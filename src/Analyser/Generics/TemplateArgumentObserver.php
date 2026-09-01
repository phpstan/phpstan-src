<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use function count;

/**
 * Matches the type an object was sent to against the object's own type and
 * records what that tells the frame about the object's unresolved template
 * arguments. Pure over types; the hooks in the handlers decide when to call it.
 */
final class TemplateArgumentObserver
{

	/**
	 * $actual flows into $declared: a property's writable type, a parameter
	 * type, a declared return type, a @var type.
	 */
	public static function observeSend(TemplateArgumentFrame $frame, Type $declared, Type $actual): void
	{
		if ($declared instanceof TemplateType) {
			return;
		}
		if ($actual instanceof UnionType) {
			foreach ($actual->getTypes() as $member) {
				self::observeSend($frame, $declared, $member);
			}

			return;
		}
		if ($declared instanceof UnionType) {
			foreach ($declared->getTypes() as $member) {
				self::observeSend($frame, $member, $actual);
			}

			return;
		}
		if ($actual instanceof UnresolvedTemplateArgumentType) {
			// a bare marker is a derived value (Foo<T>::get()) and never constrains
			return;
		}

		$actualReflections = $actual->getObjectClassReflections();
		if (count($actualReflections) === 1) {
			$declaredReflections = $declared->getObjectClassReflections();
			if (count($declaredReflections) !== 1) {
				return;
			}
			$declaredReflection = $declaredReflections[0];

			// the declared type names an ancestor: its arguments map onto the
			// object's through @extends/@implements
			$ancestor = $actualReflections[0]->getAncestorWithClassName($declaredReflection->getName());
			if ($ancestor === null || !$ancestor->isGeneric()) {
				return;
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
					self::observeSend($frame, $declaredArgument, $argument);
					continue;
				}
				if (TemplateArgumentFrame::isUninformativeSendTarget($declaredArgument)) {
					continue;
				}

				$callSiteVariance = $declaredVariances->getVariance($template->getName()) ?? TemplateTypeVariance::createInvariant();
				$effectiveVariance = $callSiteVariance->invariant() ? $template->getVariance() : $callSiteVariance;
				$frame->recordSend($argument, $declaredArgument, $effectiveVariance);

				// a site whose inferred argument itself carries markers (wrap(new Foo(1)))
				$initial = $argument->getInitialType();
				if ($initial === null) {
					continue;
				}
				self::observeSend($frame, $declaredArgument, $initial);
			}

			return;
		}

		if (count($actualReflections) > 0 || $actual->isObject()->yes()) {
			return;
		}

		if (!$actual->isIterable()->yes() || !$declared->isIterable()->yes()) {
			return;
		}

		self::observeSend($frame, $declared->getIterableKeyType(), $actual->getIterableKeyType());
		self::observeSend($frame, $declared->getIterableValueType(), $actual->getIterableValueType());
	}

	/**
	 * An argument was passed to a parameter: the argument's markers are sent to
	 * the parameter type, and a parameter type carrying the receiver's markers
	 * (add(T $x) on Foo<unresolved>) puts the argument as a lower bound on them.
	 */
	public static function observeArgument(TemplateArgumentFrame $frame, Type $parameterType, Type $argumentType): void
	{
		self::observeSend($frame, $parameterType, $argumentType);
		self::observeLowerBound($frame, $parameterType, $argumentType);
	}

	private static function observeLowerBound(TemplateArgumentFrame $frame, Type $parameterType, Type $argumentType): void
	{
		if ($parameterType instanceof UnresolvedTemplateArgumentType) {
			$frame->recordLowerBound($parameterType, $argumentType);
			return;
		}
		if ($parameterType instanceof TemplateType || $parameterType->isCallable()->yes()) {
			// callable parameters put the template in a contravariant position:
			// what they say about it is an upper bound, not something flowing in
			return;
		}
		if ($parameterType instanceof UnionType) {
			foreach ($parameterType->getTypes() as $member) {
				self::observeLowerBound($frame, $member, $argumentType);
			}

			return;
		}
		if ($argumentType instanceof UnionType) {
			foreach ($argumentType->getTypes() as $member) {
				self::observeLowerBound($frame, $parameterType, $member);
			}

			return;
		}

		$parameterReflections = $parameterType->getObjectClassReflections();
		if (count($parameterReflections) === 1) {
			$parameterReflection = $parameterReflections[0];
			if (!$parameterReflection->isGeneric()) {
				return;
			}
			$argumentReflections = $argumentType->getObjectClassReflections();
			if (count($argumentReflections) !== 1) {
				return;
			}
			$ancestor = $argumentReflections[0]->getAncestorWithClassName($parameterReflection->getName());
			if ($ancestor === null) {
				return;
			}
			$ancestorArguments = $ancestor->typeMapToList($ancestor->getActiveTemplateTypeMap());
			foreach ($parameterReflection->typeMapToList($parameterReflection->getActiveTemplateTypeMap()) as $i => $parameterArgument) {
				if (!isset($ancestorArguments[$i])) {
					continue;
				}
				self::observeLowerBound($frame, $parameterArgument, $ancestorArguments[$i]);
			}

			return;
		}

		if (count($parameterReflections) > 0 || $parameterType->isObject()->yes()) {
			return;
		}

		if (!$parameterType->isIterable()->yes() || !$argumentType->isIterable()->yes()) {
			return;
		}

		self::observeLowerBound($frame, $parameterType->getIterableKeyType(), $argumentType->getIterableKeyType());
		self::observeLowerBound($frame, $parameterType->getIterableValueType(), $argumentType->getIterableValueType());
	}

}
