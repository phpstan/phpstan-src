<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name\FullyQualified;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;
use function array_slice;
use function count;
use function implode;
use function sprintf;

/**
 * The function or method a first-class callable or a partial function application
 * was created from, with the arguments the partial application bound. Calling the
 * closure replays the call on the callee (FuncCallHandler), so its dynamic return
 * type extensions, type-specifying extensions and by-ref effects apply.
 *
 * Not shadowed by a native twin: it runs only for closures created from callables.
 */
#[ReferencedByTurboExtension(key: 'closureOrigin')]
final class ClosureOrigin
{

	/**
	 * @param list<array{non-empty-string|null, Type|null}>|null $partialApplicationArgs the name and the bound type (null for `?`) of each
	 *                                                                         argument of a partial function application before `...`,
	 *                                                                         null for a first-class callable
	 * @param list<string> $boundParameterNames the callee's parameters the partial function application bound
	 */
	private function __construct(
		private ?string $functionName,
		private ?Type $calledOnType,
		private ?string $methodName,
		private bool $isStaticCall,
		private ?array $partialApplicationArgs,
		private bool $hasVariadicPlaceholder,
		private array $boundParameterNames = [],
	)
	{
	}

	public static function forFunction(string $functionName): self
	{
		return new self($functionName, null, null, false, null, true);
	}

	public static function forMethod(Type $calledOnType, string $methodName, bool $isStaticCall): self
	{
		return new self(null, $calledOnType, $methodName, $isStaticCall, null, true);
	}

	/**
	 * @param array<Arg|ArgPlaceholder|VariadicPlaceholder> $args
	 * @param array<int, Type> $boundArgTypes keyed like $args
	 */
	public function withPartialApplication(array $args, array $boundArgTypes): self
	{
		$partialApplicationArgs = [];
		$hasVariadicPlaceholder = false;
		foreach ($args as $i => $arg) {
			if ($arg instanceof VariadicPlaceholder) {
				$hasVariadicPlaceholder = true;
				continue;
			}
			$partialApplicationArgs[] = [
				$arg->name?->toString(),
				$arg instanceof Arg ? $boundArgTypes[$i] ?? new MixedType() : null,
			];
		}

		return new self($this->functionName, $this->calledOnType, $this->methodName, $this->isStaticCall, $partialApplicationArgs, $hasVariadicPlaceholder);
	}

	/**
	 * @param list<string> $boundParameterNames
	 */
	public function withBoundParameterNames(array $boundParameterNames): self
	{
		return new self($this->functionName, $this->calledOnType, $this->methodName, $this->isStaticCall, $this->partialApplicationArgs, $this->hasVariadicPlaceholder, $boundParameterNames);
	}

	/**
	 * The names a call of the closure cannot pass by name: PHP reports that such an argument
	 * overwrites the bound one when the closure is variadic.
	 *
	 * @return list<string>
	 */
	public function getBoundParameterNames(): array
	{
		return $this->boundParameterNames;
	}

	/**
	 * The answers of a union's members combined per closure the union can hold. Closures
	 * created from the same callable with the same arguments are one closure, overloaded
	 * with the variants of its callee: like the variants of a callable string, any of its
	 * signatures that holds is enough.
	 *
	 * @param list<Type> $types
	 * @param list<AcceptsResult> $results the answer for each of $types
	 * @return list<AcceptsResult>
	 */
	public static function combineOverloadAcceptsResults(array $types, array $results): array
	{
		$combined = [];
		foreach (self::groupOverloadResults($types, $results) as $overloads) {
			$result = $overloads[0];
			foreach (array_slice($overloads, 1) as $overload) {
				$result = $result->or($overload);
			}
			$combined[] = $result;
		}

		return $combined;
	}

	/**
	 * @see combineOverloadAcceptsResults()
	 * @param list<Type> $types
	 * @param list<IsSuperTypeOfResult> $results the answer for each of $types
	 * @return list<IsSuperTypeOfResult>
	 */
	public static function combineOverloadIsSuperTypeOfResults(array $types, array $results): array
	{
		$combined = [];
		foreach (self::groupOverloadResults($types, $results) as $overloads) {
			$combined[] = count($overloads) === 1 ? $overloads[0] : $overloads[0]->or(...array_slice($overloads, 1));
		}

		return $combined;
	}

	/**
	 * @template TResult
	 * @param list<Type> $types
	 * @param list<TResult> $results
	 * @return list<non-empty-list<TResult>>
	 */
	private static function groupOverloadResults(array $types, array $results): array
	{
		$origins = [];
		$groups = [];
		foreach ($types as $i => $type) {
			$origin = $type instanceof ClosureType ? $type->getOrigin() : null;
			if ($origin !== null) {
				foreach ($origins as $index => $groupOrigin) {
					if ($groupOrigin !== null && $groupOrigin->equals($origin)) {
						$groups[$index][] = $results[$i];
						continue 2;
					}
				}
			}
			$origins[] = $origin;
			$groups[] = [$results[$i]];
		}

		return $groups;
	}

	public function equals(self $other): bool
	{
		if (
			$this->functionName !== $other->functionName
			|| $this->methodName !== $other->methodName
			|| $this->isStaticCall !== $other->isStaticCall
			|| $this->hasVariadicPlaceholder !== $other->hasVariadicPlaceholder
			|| $this->boundParameterNames !== $other->boundParameterNames
		) {
			return false;
		}
		if ($this->calledOnType !== null && ($other->calledOnType === null || !$this->calledOnType->equals($other->calledOnType))) {
			return false;
		}
		if ($this->partialApplicationArgs === null || $other->partialApplicationArgs === null) {
			return $this->partialApplicationArgs === $other->partialApplicationArgs;
		}
		if (count($this->partialApplicationArgs) !== count($other->partialApplicationArgs)) {
			return false;
		}
		foreach ($this->partialApplicationArgs as $i => [$name, $type]) {
			[$otherName, $otherType] = $other->partialApplicationArgs[$i];
			if ($name !== $otherName) {
				return false;
			}
			if ($type === null || $otherType === null) {
				if ($type !== $otherType) {
					return false;
				}
				continue;
			}
			if (!$type->equals($otherType)) {
				return false;
			}
		}

		return true;
	}

	public function describe(): string
	{
		if ($this->functionName !== null) {
			$callee = $this->functionName;
		} elseif ($this->calledOnType !== null) {
			$callee = sprintf('%s%s%s', $this->calledOnType->describe(VerbosityLevel::cache()), $this->isStaticCall ? '::' : '->', $this->methodName);
		} else {
			$callee = '';
		}
		if ($this->partialApplicationArgs === null) {
			return $callee . '(...)';
		}

		$args = [];
		foreach ($this->partialApplicationArgs as [$name, $type]) {
			$args[] = ($name !== null ? $name . ': ' : '') . ($type !== null ? $type->describe(VerbosityLevel::cache()) : '?');
		}
		if ($this->hasVariadicPlaceholder) {
			$args[] = '...';
		}

		return sprintf('%s(%s)', $callee, implode(', ', $args));
	}

	/**
	 * @param callable(Type): Type $cb
	 */
	public function traverse(callable $cb): self
	{
		$changed = false;
		$calledOnType = $this->calledOnType;
		if ($calledOnType !== null) {
			$calledOnType = $cb($calledOnType);
			$changed = $calledOnType !== $this->calledOnType;
		}
		$partialApplicationArgs = $this->partialApplicationArgs;
		if ($partialApplicationArgs !== null) {
			foreach ($partialApplicationArgs as $i => [$name, $type]) {
				if ($type === null) {
					continue;
				}
				$newType = $cb($type);
				if ($newType === $type) {
					continue;
				}
				$partialApplicationArgs[$i] = [$name, $newType];
				$changed = true;
			}
		}
		if (!$changed) {
			return $this;
		}

		return new self($this->functionName, $calledOnType, $this->methodName, $this->isStaticCall, $partialApplicationArgs, $this->hasVariadicPlaceholder, $this->boundParameterNames);
	}

	/**
	 * The call of the callee that calling the closure as $call makes, null when its
	 * arguments do not map onto the callee - the call is then analysed on the closure's
	 * signature alone.
	 */
	public function createCall(FuncCall $call, ClosureType $closure): ?CallLike
	{
		$args = $this->createArgs($call, $closure);
		if ($args === null) {
			return null;
		}

		$attributes = $call->getAttributes();
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		if ($this->functionName !== null && $this->functionName !== '') {
			return new FuncCall(new FullyQualified($this->functionName), $args, $attributes);
		}
		if ($this->calledOnType === null || $this->methodName === null || $this->methodName === '') {
			return null;
		}
		if ($this->isStaticCall) {
			return new StaticCall(new TypeExpr($this->calledOnType), new Identifier($this->methodName), $args, $attributes);
		}

		return new MethodCall(new TypeExpr($this->calledOnType), new Identifier($this->methodName), $args, $attributes);
	}

	/**
	 * @return list<Arg>|null
	 */
	private function createArgs(FuncCall $call, ClosureType $closure): ?array
	{
		$callArgs = $call->getArgs();
		if ($this->partialApplicationArgs === null) {
			return $callArgs;
		}

		// the closure's parameters are the positional placeholders, the named
		// placeholders and, with `...`, the callee's unbound parameters and its variadic
		$parameters = $closure->getParameters();
		$declaredCount = count($parameters);
		if ($closure->isVariadic() && $declaredCount > 0 && $parameters[$declaredCount - 1]->isVariadic()) {
			$declaredCount--;
		}
		$indexesByName = [];
		foreach (array_slice($parameters, 0, $declaredCount) as $i => $parameter) {
			$indexesByName[$parameter->getName()] = $i;
		}

		$argsByParameter = [];
		$extraPositional = [];
		$extraNamed = [];
		$position = 0;
		foreach ($callArgs as $arg) {
			if ($arg->unpack) {
				return null;
			}
			if ($arg->name === null) {
				if ($position < $declaredCount) {
					$argsByParameter[$position++] = $arg;
					continue;
				}
				$extraPositional[] = $arg;
				continue;
			}
			$index = $indexesByName[$arg->name->toString()] ?? null;
			if ($index === null) {
				$extraNamed[] = $arg;
				continue;
			}
			$argsByParameter[$index] = $arg;
		}
		if (!$closure->isVariadic() && ($extraPositional !== [] || $extraNamed !== [])) {
			return null;
		}

		$calleeArgs = [];
		$parameterIndex = 0;
		// past a named argument, the remaining parameters go by name too
		$named = $this->hasNamedArgs();
		foreach ([false, true] as $namedPass) {
			foreach ($this->partialApplicationArgs as [$name, $type]) {
				if (($name !== null) !== $namedPass) {
					continue;
				}
				$nameIdentifier = $name !== null ? new Identifier($name) : null;
				if ($type !== null) {
					$calleeArgs[] = new Arg(new TypeExpr($type), name: $nameIdentifier);
					continue;
				}
				$arg = $argsByParameter[$parameterIndex++] ?? null;
				if ($arg === null) {
					return null;
				}
				$calleeArgs[] = new Arg($arg->value, attributes: $arg->getAttributes(), name: $nameIdentifier);
			}
		}

		if ($this->hasVariadicPlaceholder) {
			for (; $parameterIndex < $declaredCount; $parameterIndex++) {
				$arg = $argsByParameter[$parameterIndex] ?? null;
				if ($arg === null) {
					// an optional parameter left out: the ones after it go by name
					$named = true;
					continue;
				}
				$parameterName = $parameters[$parameterIndex]->getName();
				if ($named && $parameterName === '') {
					return null;
				}
				$calleeArgs[] = new Arg($arg->value, attributes: $arg->getAttributes(), name: $named ? new Identifier($parameterName) : null);
			}
		}
		if ($extraPositional !== [] && $named) {
			return null;
		}

		return [...$calleeArgs, ...$extraPositional, ...$extraNamed];
	}

	private function hasNamedArgs(): bool
	{
		foreach ($this->partialApplicationArgs ?? [] as [$name]) {
			if ($name !== null) {
				return true;
			}
		}

		return false;
	}

}
