<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PhpParser\Node\Expr;
use PHPStan\Analyser\OutOfClassScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\ClosureType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\UnionType;
use function array_filter;
use function array_merge;
use function count;
use function is_string;

/**
 * Matches declared and actual types to collect constraints on unresolved
 * template arguments. All accumulation is local to a call; the returned
 * constraints and the scope's inference context are immutable.
 */
#[AutowiredService]
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/TemplateArgumentObserver.cpp')]
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
	private function containsMarker(Type $type, bool $templateArgumentsOnly = false): bool
	{
		$contains = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$contains, $templateArgumentsOnly): Type {
			if ($type instanceof UnresolvedTemplateArgumentType && (!$templateArgumentsOnly || !ClosureSignatureInference::isClosureSignatureMarker($type))) {
				$contains = true;
			}
			return $contains ? $type : $traverse($type);
		});
		return $contains;
	}

	public function collectSend(Type $declared, Type $actual): TemplateArgumentConstraints
	{
		$constraints = $this->observeSend(TemplateArgumentConstraints::createEmpty(), $declared, $actual);

		return $this->observeClosureSend($constraints, $declared, $actual);
	}

	/**
	 * A call argument flows into its parameter: the closures it carries learn
	 * what they will be invoked with (see ClosureSignatureInference).
	 */
	public function collectClosureArgument(Type $parameterType, Type $argumentType): TemplateArgumentConstraints
	{
		return $this->observeClosureSend(TemplateArgumentConstraints::createEmpty(), $parameterType, $argumentType);
	}

	/**
	 * The call's arguments against the acceptor resolved from all of them - a
	 * generic callable(T) parameter is only informative once T is.
	 *
	 * A pure callee cannot invoke what it takes as mixed, so such a parameter
	 * is no escape.
	 *
	 * @param array<int|string, Type> $argumentTypes
	 */
	public function collectClosureArguments(ParametersAcceptor $acceptor, array $argumentTypes, bool $isPure): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		$parameters = null;
		$parametersByName = null;
		foreach ($argumentTypes as $i => $argumentType) {
			if (!$this->containsClosureSignatureMarker($argumentType)) {
				continue;
			}
			if ($parameters === null) {
				$parameters = $acceptor->getParameters();
				$parametersByName = [];
				foreach ($parameters as $parameter) {
					$parametersByName[$parameter->getName()] = $parameter;
				}
			}
			$parameter = is_string($i) ? ($parametersByName[$i] ?? null) : ($parameters[$i] ?? null);
			$parameter ??= $acceptor->isVariadic() && count($parameters) > 0 ? $parameters[count($parameters) - 1] : null;
			if ($parameter === null) {
				$constraints = $this->escapeClosures($constraints, $argumentType);
				continue;
			}
			$parameterType = $parameter->getType();
			if ($isPure && $parameterType instanceof MixedType && !$parameterType instanceof TemplateType) {
				continue;
			}
			$constraints = $this->observeClosureSend($constraints, $parameterType, $argumentType);
		}

		return $constraints;
	}

	/**
	 * The value leaves the body for somewhere nothing describes (a yielded value,
	 * an offset of a property): the closures it carries can be invoked with
	 * anything.
	 */
	public function collectEscape(Type $type): TemplateArgumentConstraints
	{
		if (!$this->containsClosureSignatureMarker($type)) {
			return TemplateArgumentConstraints::createEmpty();
		}

		return $this->escapeClosures(TemplateArgumentConstraints::createEmpty(), $type);
	}

	/** @param array<int|string, Type> $types */
	public function carriesClosureSignatureMarkers(array $types): bool
	{
		foreach ($types as $type) {
			if ($this->containsClosureSignatureMarker($type)) {
				return true;
			}
		}

		return false;
	}

	private function containsClosureSignatureMarker(Type $type): bool
	{
		$contains = false;
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$contains): Type {
			if ($type instanceof UnresolvedTemplateArgumentType && ClosureSignatureInference::isClosureSignatureMarker($type)) {
				$contains = true;
			}
			return $contains ? $type : $traverse($type);
		});
		return $contains;
	}

	/**
	 * $actual, carrying closures whose signature is being inferred, flows into
	 * $declared. A callable target with a signature puts its parameter types as
	 * lower bounds on the closure's parameters and its return type as an upper
	 * bound on the closure's return; any other target lets the closure escape -
	 * whoever ends up invoking it can pass anything.
	 */
	private function observeClosureSend(TemplateArgumentConstraints $constraints, Type $declared, Type $actual): TemplateArgumentConstraints
	{
		if (!$this->containsClosureSignatureMarker($actual)) {
			return $constraints;
		}
		if ($actual instanceof UnionType) {
			foreach ($actual->getTypes() as $member) {
				$constraints = $this->observeClosureSend($constraints, $declared, $member);
			}

			return $constraints;
		}
		if ($actual instanceof ClosureType) {
			return $this->observeClosureSendToCallable($constraints, $declared, $actual);
		}
		if (
			!$actual->isObject()->yes()
			&& $actual->isIterable()->yes()
			&& !$declared->isObject()->yes()
			&& $declared->isIterable()->yes()
		) {
			$constraints = $this->observeClosureSend($constraints, $declared->getIterableKeyType(), $actual->getIterableKeyType());

			return $this->observeClosureSend($constraints, $declared->getIterableValueType(), $actual->getIterableValueType());
		}

		return $this->escapeClosures($constraints, $actual);
	}

	private function observeClosureSendToCallable(TemplateArgumentConstraints $constraints, Type $declared, ClosureType $actual): TemplateArgumentConstraints
	{
		if ($declared instanceof UnionType && !$declared instanceof TemplateType) {
			$declared = TypeCombinator::union(...array_filter($declared->getTypes(), static fn (Type $member): bool => !$member->isCallable()->no()));
		}
		if ($declared instanceof MixedType || !$declared->isCallable()->yes()) {
			return $this->escapeClosures($constraints, $actual);
		}

		$closureParameters = $actual->getParameters();
		$returnMarker = $actual->getReturnType();
		foreach ($declared->getCallableParametersAcceptors(new OutOfClassScope()) as $acceptor) {
			$targetParameters = $acceptor->getParameters();
			if (count($targetParameters) === 0 && $acceptor->isVariadic()) {
				// callable, Closure: the parameters are not described
				$constraints = $this->escapeClosures($constraints, $actual);
				continue;
			}
			foreach ($closureParameters as $i => $closureParameter) {
				$marker = $closureParameter->getType();
				if (!$marker instanceof UnresolvedTemplateArgumentType || !ClosureSignatureInference::isClosureSignatureMarker($marker)) {
					continue;
				}
				if ($closureParameter->isVariadic()) {
					for ($j = $i; $j < count($targetParameters); $j++) {
						$constraints = $constraints->withLowerBound($marker, $targetParameters[$j]->getType());
					}
					continue;
				}
				if (isset($targetParameters[$i])) {
					$constraints = $constraints->withLowerBound($marker, $targetParameters[$i]->getType());
					continue;
				}
				if (!$acceptor->isVariadic() || count($targetParameters) === 0) {
					// never passed: the closure parameter keeps its default
					continue;
				}

				$constraints = $constraints->withLowerBound($marker, $targetParameters[count($targetParameters) - 1]->getType());
			}

			$targetReturnType = $acceptor->getReturnType();
			if ($returnMarker instanceof UnresolvedTemplateArgumentType && ClosureSignatureInference::isReturnMarker($returnMarker)) {
				if (!$targetReturnType->isVoid()->yes() && !$targetReturnType instanceof MixedType) {
					$constraints = $constraints->withSend($returnMarker, $targetReturnType, TemplateTypeVariance::createCovariant());
				}
				$returnedType = $returnMarker->getInitialType();
			} else {
				$returnedType = $returnMarker;
			}
			if ($returnedType === null) {
				continue;
			}

			// a closure returning closures: the returned ones are sent on
			$constraints = $this->observeClosureSend($constraints, $targetReturnType, $returnedType);
		}

		return $constraints;
	}

	/**
	 * The closures in $type go where nothing describes how they are invoked.
	 */
	private function escapeClosures(TemplateArgumentConstraints $constraints, Type $type): TemplateArgumentConstraints
	{
		TypeTraverser::map($type, static function (Type $type, callable $traverse) use (&$constraints): Type {
			if ($type instanceof UnresolvedTemplateArgumentType) {
				if (ClosureSignatureInference::isClosureSignatureMarker($type) && !ClosureSignatureInference::isReturnMarker($type)) {
					$constraints = $constraints->withUnconstrainingSend($type);
				}
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

	public function collectArgument(Type $parameterType, Type $argumentType, bool $isPure = false): TemplateArgumentConstraints
	{
		// A pure consumer accepting anything cannot initialize an empty object.
		if ($isPure && $parameterType instanceof MixedType && !$parameterType instanceof TemplateType) {
			return TemplateArgumentConstraints::createEmpty();
		}
		return $this->observeArgument(TemplateArgumentConstraints::createEmpty(), $parameterType, $argumentType);
	}

	/**
	 * Keep a call's inferable parameters shared across all of its arguments.
	 * Invariant uses relate fresh instances instead of fixing each one from
	 * the arguments seen so far. The call's return type uses the same site.
	 *
	 * @param array<int|string, Type> $argumentTypes
	 */
	public function collectCall(Expr $site, ParametersAcceptor $acceptor, array $argumentTypes, ?TemplateTypeMap $classTemplates = null): TemplateArgumentConstraints
	{
		$constraints = TemplateArgumentConstraints::createEmpty();
		if ($acceptor instanceof ResolvedFunctionVariant) {
			$acceptor = $acceptor->getOriginalParametersAcceptor();
		}
		$templates = new TemplateTypeMap(array_merge($classTemplates !== null ? $classTemplates->getTypes() : [], $acceptor->getTemplateTypeMap()->getTypes()));
		if ($templates->isEmpty()) {
			return $constraints;
		}
		$hasMarkers = false;
		foreach ($argumentTypes as $argumentType) {
			// a closure whose signature is being inferred is sent through
			// collectClosureArguments(), it holds no template argument
			if (!$this->containsMarker($argumentType, true)) {
				continue;
			}
			$hasMarkers = true;
			break;
		}
		if (!$hasMarkers) {
			return $constraints;
		}

		$parameters = $acceptor->getParameters();
		$parametersByName = [];
		foreach ($parameters as $parameter) {
			$parametersByName[$parameter->getName()] = $parameter;
		}
		foreach ($argumentTypes as $i => $argumentType) {
			$parameter = is_string($i) ? ($parametersByName[$i] ?? null) : ($parameters[$i] ?? null);
			$parameter ??= $acceptor->isVariadic() && count($parameters) > 0 ? $parameters[count($parameters) - 1] : null;
			if ($parameter === null) {
				continue;
			}
			$parameterType = $this->replaceInferableTemplates($parameter->getType(), $site, $templates, $constraints);
			$constraints = $this->observeArgument($constraints, $parameterType, $argumentType);
		}

		return $constraints;
	}

	/**
	 * Replaces the call's own template types by markers of the call site. A
	 * marker behaves as its delegate (mixed for a bare @template), so normalizing
	 * a union lets a naked marker absorb its siblings: T|null becomes the marker,
	 * which is what links Collection<T|null> to a Collection<unresolved> argument.
	 * A sibling that itself carries a marker of the call is kept next to the
	 * naked one instead - Foo<T>|T has to keep both members for the argument to
	 * be matched against Foo<T> first.
	 */
	private function replaceInferableTemplates(Type $type, Expr $site, TemplateTypeMap $templates, TemplateArgumentConstraints &$constraints): Type
	{
		// a template with a union bound is a union too - it is a template first
		if ($type instanceof UnionType && !$type instanceof TemplateType) {
			$naked = [];
			$structural = [];
			$members = [];
			foreach ($type->getTypes() as $member) {
				$member = $this->replaceInferableTemplates($member, $site, $templates, $constraints);
				$members[] = $member;
				if ($member instanceof UnresolvedTemplateArgumentType) {
					$naked[] = $member;
				} elseif ($this->containsMarker($member)) {
					$structural[] = $member;
				}
			}
			if ($naked === [] || $structural === []) {
				return TypeCombinator::union(...$members);
			}

			return new UnionType([...$naked, ...$structural]);
		}

		return TypeTraverser::map($type, function (Type $type, callable $traverse) use ($site, $templates, &$constraints): Type {
			if ($type instanceof UnionType && !$type instanceof TemplateType) {
				return $this->replaceInferableTemplates($type, $site, $templates, $constraints);
			}
			if (!$type instanceof TemplateType || $type->isArgument()) {
				return $traverse($type);
			}
			$template = $templates->getType($type->getName());
			if (!$template instanceof TemplateType || !$template->getScope()->equals($type->getScope())) {
				return $type;
			}
			$marker = new UnresolvedTemplateArgumentType($site, $type, null);
			$constraints = $constraints->withSite($marker);
			return $marker;
		});
	}

	/**
	 * $actual flows into $declared: a property's writable type, a parameter
	 * type, a declared return type, a @var type.
	 */
	private function observeSend(TemplateArgumentConstraints $constraints, Type $declared, Type $actual, bool $isCallArgument = false): TemplateArgumentConstraints
	{
		if ($declared instanceof TemplateType || !$this->containsMarker($actual)) {
			return $constraints;
		}
		if ($isCallArgument && $declared instanceof MixedType) {
			foreach ($this->collectSites($actual)->getFacts() as [$marker]) {
				$constraints = $constraints->withUnconstrainingSend($marker);
			}
			return $constraints;
		}
		if ($actual instanceof UnionType) {
			foreach ($actual->getTypes() as $member) {
				$constraints = $this->observeSend($constraints, $declared, $member, $isCallArgument);
			}

			return $constraints;
		}
		if ($declared instanceof UnionType) {
			foreach ($declared->getTypes() as $member) {
				$constraints = $this->observeSend($constraints, $member, $actual, $isCallArgument);
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
			// Omitted arguments are not explicit constraints to widen to the bounds.
			$declaredArguments = $declaredReflection->typeMapToList($declaredReflection->getPossiblyIncompleteActiveTemplateTypeMap());
			$declaredVariances = $declaredReflection->getCallSiteVarianceMap();
			foreach ($ancestor->typeMapToList($ancestor->getActiveTemplateTypeMap()) as $i => $argument) {
				$template = $templates[$i] ?? null;
				if (!$template instanceof TemplateType || !isset($declaredArguments[$i])) {
					continue;
				}
				$declaredArgument = $declaredArguments[$i];
				if (!$argument instanceof UnresolvedTemplateArgumentType) {
					$constraints = $this->observeSend($constraints, $declaredArgument, $argument, $isCallArgument);
					continue;
				}
				if (
					$isCallArgument
					&& ($argument->getInitialType() === null || $argument->getInitialType() instanceof NeverType)
					&& self::hasOnlyInferableTemplates($declaredArgument)
				) {
					$declaredArgument = TemplateTypeHelper::resolveToDefaults($declaredArgument);
				}
				if (self::isUninformativeSendTarget($declaredArgument)) {
					// An unresolved call parameter, like mixed, uses the object without
					// constraining it. Return/property templates are fixed by their
					// declaration and must keep an empty argument compatible with them.
					if (($isCallArgument && self::hasOnlyInferableTemplates($declaredArgument)) || ($declaredArgument instanceof MixedType && !$declaredArgument instanceof TemplateType)) {
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
				$constraints = $this->observeSend($constraints, $declaredArgument, $initial, $isCallArgument);
			}

			return $constraints;
		}

		if (count($actualReflections) > 0 || $actual->isObject()->yes()) {
			return $constraints;
		}

		if (!$actual->isIterable()->yes() || !$declared->isIterable()->yes()) {
			return $constraints;
		}

		$constraints = $this->observeSend($constraints, $declared->getIterableKeyType(), $actual->getIterableKeyType(), $isCallArgument);
		$constraints = $this->observeSend($constraints, $declared->getIterableValueType(), $actual->getIterableValueType(), $isCallArgument);

		return $constraints;
	}

	/**
	 * An argument was passed to a parameter: the argument's markers are sent to
	 * the parameter type, and a parameter type carrying the receiver's markers
	 * (add(T $x) on Foo<unresolved>) puts the argument as a lower bound on them.
	 */
	private function observeArgument(TemplateArgumentConstraints $constraints, Type $parameterType, Type $argumentType): TemplateArgumentConstraints
	{
		$constraints = $this->observeSend($constraints, $parameterType, $argumentType, true);
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
			// Mirrors UnionType::inferTemplateTypes(): an argument member that a
			// sibling takes - a marker-free member accepting it, or a member
			// carrying the call's markers matching it structurally (Foo<T> for a
			// Foo<X>) - does not flow into a naked marker next to it. T|Foo<T>
			// receiving Foo<X> binds T to X, not to Foo<X> as well.
			$argumentMembers = $argumentType instanceof UnionType ? $argumentType->getTypes() : [$argumentType];
			foreach ($argumentMembers as $argumentMember) {
				$taken = false;
				foreach ($parameterType->getTypes() as $member) {
					if ($member instanceof UnresolvedTemplateArgumentType) {
						continue;
					}
					if (!$this->containsMarker($member)) {
						if ($member->isSuperTypeOf($argumentMember)->yes()) {
							$taken = true;
						}
						continue;
					}
					$before = $constraints;
					$constraints = $this->observeLowerBound($constraints, $member, $argumentMember);
					if ($constraints === $before) {
						continue;
					}

					$taken = true;
				}
				if ($taken) {
					continue;
				}
				foreach ($parameterType->getTypes() as $member) {
					if (!$member instanceof UnresolvedTemplateArgumentType) {
						continue;
					}
					$constraints = $this->observeLowerBound($constraints, $member, $argumentMember);
				}
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
				if ($parameterArgument instanceof UnresolvedTemplateArgumentType) {
					$template = $parameterReflection->typeMapToList($parameterReflection->getTemplateTypeMap())[$i] ?? null;
					if ($template instanceof TemplateType) {
						$variance = $parameterReflection->getCallSiteVarianceMap()->getVariance($template->getName()) ?? TemplateTypeVariance::createInvariant();
						$variance = $variance->invariant() ? $template->getVariance() : $variance;
						if ($variance->invariant()) {
							$constraints = $constraints->withSend($parameterArgument, $ancestorArguments[$i], $variance);
							continue;
						}
						if ($variance->contravariant()) {
							$constraints = $constraints->withSend($parameterArgument, $ancestorArguments[$i], TemplateTypeVariance::createCovariant());
							continue;
						}
						if ($variance->bivariant()) {
							continue;
						}
					}
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

	private static function hasOnlyInferableTemplates(Type $type): bool
	{
		$references = $type->getReferencedTemplateTypes(TemplateTypeVariance::createInvariant());
		foreach ($references as $reference) {
			if ($reference->getType()->isArgument()) {
				return false;
			}
		}

		return count($references) > 0;
	}

}
