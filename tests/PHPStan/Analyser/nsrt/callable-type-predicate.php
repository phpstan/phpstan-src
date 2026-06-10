<?php // lint >= 8.1

namespace CallableTypePredicate;

use function PHPStan\Testing\assertType;

final class ConfigReader {
	/**
	 * @template T of mixed[]
	 * @param mixed[] $testObj
	 * @param (callable(T|mixed[] $test): ($test is T ? true : false))|null $validator
	 * @param T|null $default
	 * @return ($default is T ? T : T|null)
	 */
	public function returnIfValid(array $testObj, ?callable $validator = null, ?array $default = null): ?array {
		assertType('array<mixed>', $testObj);

		if ($validator !== null && $validator($testObj)) {
			// the predicate returned true => $testObj is narrowed to T
			assertType('T of array<mixed> (method CallableTypePredicate\ConfigReader::returnIfValid(), argument)', $testObj);
			return $testObj;
		}

		// no narrowing happened (validator absent or returned false)
		assertType('array<mixed>', $testObj);

		return $default;
	}
}

/**
 * @phpstan-assert-if-true array{name: string, age: int} $value
 * @param mixed[] $value
 */
function isUserShape(array $value): bool {
	return isset($value['name'], $value['age']) && is_string($value['name']) && is_int($value['age']);
}

/**
 * @param mixed[] $data
 */
function testCallSites(ConfigReader $reader, array $data): void {
	// T inferred from a first-class callable to a function with @phpstan-assert-if-true,
	// $default is null => conditional return type picks the T|null branch
	$result = $reader->returnIfValid($data, isUserShape(...));
	assertType('array{name: string, age: int}|null', $result);

	// $default is T => conditional return type picks the T branch
	$result2 = $reader->returnIfValid($data, isUserShape(...), ['name' => 'John', 'age' => 42]);
	assertType('array{name: string, age: int}', $result2);

	// no validator, no default => T stays unresolved, falls back to its bound
	$result3 = $reader->returnIfValid($data);
	assertType('array<mixed>|null', $result3);

	// plain closure without any predicate information => T cannot be inferred
	$result5 = $reader->returnIfValid($data, static fn (array $arr): bool => $arr !== []);
	assertType('array<mixed>|null', $result5);
}

/**
 * Two-sided predicate: true => int, false => not int.
 *
 * @param callable(mixed $value): ($value is int ? true : false) $isInt
 * @param mixed $x
 */
function testDirectPredicate(callable $isInt, $x): void {
	assertType('callable(mixed $value): ($value is int ? true : false)', $isInt);

	if ($isInt($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed~int', $x);
	}

	assertType('mixed', $x);
}

/**
 * Negated two-sided predicate.
 *
 * @param callable(mixed $value): ($value is not int ? true : false) $isNotInt
 * @param mixed $x
 */
function testNegatedPredicate(callable $isNotInt, $x): void {
	if ($isNotInt($x)) {
		assertType('mixed~int', $x);
	} else {
		assertType('int', $x);
	}
}

/**
 * One-sided predicate: returning true proves int, returning false proves nothing
 * (the truthy branch admits any bool).
 *
 * @param callable(mixed $value): ($value is int ? bool : false) $maybeInt
 * @param mixed $x
 */
function testIfTrueOnlyPredicate(callable $maybeInt, $x): void {
	assertType('callable(mixed $value): ($value is int ? bool : false)', $maybeInt);

	if ($maybeInt($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed', $x);
	}
}

/**
 * One-sided predicate: returning false proves NOT int, returning true proves nothing
 * (the falsy branch admits any bool).
 *
 * @param callable(mixed $value): ($value is int ? true : bool) $surelyIntWhenFalse
 * @param mixed $x
 */
function testIfFalseOnlyPredicate(callable $surelyIntWhenFalse, $x): void {
	assertType('callable(mixed $value): ($value is int ? true : bool)', $surelyIntWhenFalse);

	if ($surelyIntWhenFalse($x)) {
		assertType('mixed', $x);
	} else {
		assertType('mixed~int', $x);
	}
}

/**
 * Closure syntax works the same as callable syntax.
 *
 * @param \Closure(mixed $value): ($value is string ? true : false) $isString
 * @param mixed $x
 */
function testClosurePredicate(\Closure $isString, $x): void {
	assertType('Closure(mixed $value): ($value is string ? true : false)', $isString);

	if ($isString($x)) {
		assertType('string', $x);
	} else {
		assertType('mixed~string', $x);
	}
}

/**
 * First-class callables of native type-checking functions carry the predicate
 * from their stub's conditional return type.
 *
 * @param mixed $x
 */
function testNativeFirstClassCallable($x): void {
	$isInt = is_int(...);
	assertType('Closure(mixed $value): ($value is int ? true : false)', $isInt);

	if ($isInt($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed~int', $x);
	}
}

/**
 * The partition use case from phpstan/phpstan#11139 — the predicate narrows
 * the template types inside the function body.
 *
 * @template T0
 * @template T1
 * @param iterable<T0|T1> $values
 * @param callable(T0|T1 $value): ($value is T0 ? true : false) $predicate
 * @return array{list<T0>, list<T1>}
 */
function partition(iterable $values, callable $predicate): array {
	$partitions = [[], []];

	foreach ($values as $value) {
		if ($predicate($value)) {
			assertType('T0 (function CallableTypePredicate\partition(), argument)', $value);
			$partitions[0][] = $value;
		} else {
			assertType('T1 (function CallableTypePredicate\partition(), argument)', $value);
			$partitions[1][] = $value;
		}
	}

	return $partitions;
}

/**
 * At the call site, the predicate determines T0 = int exactly; T1 is then
 * inferred as the remainder of the iterable's value type (int|string minus int).
 *
 * @param iterable<int|string> $values
 */
function testPartition(iterable $values): void {
	$result = partition($values, is_int(...));
	assertType('array{list<int>, list<string>}', $result);
}

/**
 * The filter use case — separate template for the predicate target — works in full,
 * like TypeScript's `Array.prototype.filter(predicate: (value: T) => value is S): S[]`.
 *
 * @template T
 * @template S
 * @param list<T> $items
 * @param callable(T $item): ($item is S ? true : false) $predicate
 * @return list<S>
 */
function filterBy(array $items, callable $predicate): array {
	$result = [];
	foreach ($items as $item) {
		if ($predicate($item)) {
			$result[] = $item;
		}
	}
	return $result;
}

/**
 * @phpstan-assert-if-true non-empty-string $v
 * @param mixed $v
 */
function isNonEmptyString($v): bool {
	return is_string($v) && $v !== '';
}

/**
 * @param list<int|string> $items
 */
function testFilter(array $items): void {
	assertType('list<int>', filterBy($items, is_int(...)));
	assertType('list<string>', filterBy($items, is_string(...)));
	assertType('list<non-empty-string>', filterBy($items, isNonEmptyString(...)));
}

class GenericValidator {

	/**
	 * @phpstan-assert-if-true array{name: string} $value
	 * @param mixed[] $value
	 */
	public function isNamed(array $value): bool {
		return isset($value['name']) && is_string($value['name']);
	}

	/**
	 * @param mixed $value
	 * @return ($value is positive-int ? true : false)
	 */
	public static function isPositiveInt($value): bool {
		return is_int($value) && $value > 0;
	}

}

/**
 * @param mixed[] $data
 * @param mixed $x
 */
function testMethodFirstClassCallables(GenericValidator $validator, array $data, $x): void {
	$isNamed = $validator->isNamed(...);
	if ($isNamed($data)) {
		assertType('array{name: string}', $data);
	}

	$isPositive = GenericValidator::isPositiveInt(...);
	if ($isPositive($x)) {
		assertType('int<1, max>', $x);
	} else {
		assertType('mixed~int<1, max>', $x);
	}
}

/**
 * @param mixed $x
 */
function testClosureFromCallable($x): void {
	$isInt = \Closure::fromCallable('is_int');
	if ($isInt($x)) {
		assertType('int', $x);
	}

	$isNonEmpty = \Closure::fromCallable('CallableTypePredicate\\isNonEmptyString');
	if ($isNonEmpty($x)) {
		assertType('non-empty-string', $x);
	}
}

/**
 * @param mixed $x
 */
function testStringCallable($x): void {
	$isInt = 'is_int';
	if ($isInt($x)) {
		assertType('int', $x);
	}

	$isNonEmpty = 'CallableTypePredicate\\isNonEmptyString';
	if ($isNonEmpty($x)) {
		assertType('non-empty-string', $x);
	}
}

/**
 * @param mixed[] $data
 */
function testArrayCallable(GenericValidator $validator, array $data): void {
	$isNamed = [$validator, 'isNamed'];
	if ($isNamed($data)) {
		assertType('array{name: string}', $data);
	}
}

class IsIntInvokable {

	/**
	 * @param mixed $value
	 * @return ($value is int ? true : false)
	 */
	public function __invoke($value): bool {
		return is_int($value);
	}

}

/**
 * @param mixed $x
 */
function testInvokableObject(IsIntInvokable $isInt, $x): void {
	if ($isInt($x)) {
		assertType('int', $x);
	}
}

/**
 * @param list<int|string> $items
 */
function testOtherCallableSourcesInference(array $items): void {
	assertType('list<int>', filterBy($items, 'is_int'));
	assertType('list<int<1, max>>', filterBy($items, GenericValidator::isPositiveInt(...)));
	assertType('list<int>', filterBy($items, \Closure::fromCallable('is_int')));
}

/**
 * Generic callable whose predicate target is the callable's own template type —
 * an instanceof-like predicate. The template is resolved from the other argument
 * when the callable is invoked.
 *
 * @param callable<T of object>(mixed $value, class-string<T> $class): ($value is T ? true : false) $isInstanceOf
 * @param mixed $x
 */
function testGenericCallablePredicate(callable $isInstanceOf, $x): void {
	assertType('callable<T of object>(mixed $value, class-string<T>): ($value is T ? true : false)', $isInstanceOf);

	if ($isInstanceOf($x, \DateTimeImmutable::class)) {
		assertType('DateTimeImmutable', $x);
	} else {
		assertType('mixed~DateTimeImmutable', $x);
	}
}

/**
 * @param \Closure<T of object>(mixed $value, class-string<T> $class): ($value is T ? true : false) $isInstanceOf
 * @param mixed $x
 */
function testGenericClosurePredicate(\Closure $isInstanceOf, $x): void {
	assertType('Closure<T of object>(mixed $value, class-string<T>): ($value is T ? true : false)', $isInstanceOf);

	if ($isInstanceOf($x, \DateTimeImmutable::class)) {
		assertType('DateTimeImmutable', $x);
	}
}

/**
 * A conditional return type on the callable's own template type (not on a parameter)
 * is not a predicate — it resolves through the inferred template type when
 * the callable is invoked and does not narrow the argument.
 *
 * @param callable<T>(T $value): (T is int ? true : false) $isIntTemplate
 * @param mixed $x
 */
function testTemplateSubjectConditional(callable $isIntTemplate, $x): void {
	assertType('true', $isIntTemplate(1));
	assertType('false', $isIntTemplate('foo'));

	if ($isIntTemplate($x)) {
		// not narrowed - the conditional is about T, not about $value
		assertType('mixed', $x);
	}
}

class ShadowedConfigReader {

	/**
	 * The callable's $test parameter shadows the method's $test parameter —
	 * inside the callable's conditional return type, the callable's own
	 * parameter wins.
	 *
	 * @template T of mixed[]
	 * @param mixed[] $test
	 * @param (callable(T|mixed[] $test): ($test is T ? true : false))|null $validator
	 * @param T|null $default
	 * @return ($default is T ? T : T|null)
	 */
	public function returnIfValid(array $test, ?callable $validator = null, ?array $default = null): ?array {
		if ($validator !== null && $validator($test)) {
			assertType('T of array<mixed> (method CallableTypePredicate\ShadowedConfigReader::returnIfValid(), argument)', $test);
			return $test;
		}

		assertType('array<mixed>', $test);

		return $default;
	}

}

/**
 * @param mixed[] $data
 */
function testShadowedCallSites(ShadowedConfigReader $reader, array $data): void {
	$result = $reader->returnIfValid($data, isUserShape(...));
	assertType('array{name: string, age: int}|null', $result);
}

/**
 * The predicate's $flag parameter shadows the function's $flag parameter;
 * the function's own conditional return type still resolves by the outer
 * $flag argument.
 *
 * @param callable(mixed $flag): ($flag is int ? true : false) $isInt
 * @param mixed $x
 * @return ($flag is true ? int : string)
 */
function shadowedPredicateParameter(bool $flag, callable $isInt, $x) {
	assertType('callable(mixed $flag): ($flag is int ? true : false)', $isInt);

	if ($isInt($x)) {
		assertType('int', $x);
	} else {
		assertType('mixed~int', $x);
	}

	return $flag ? 1 : 'a';
}

/**
 * @param mixed $x
 */
function testShadowedPredicateParameter($x): void {
	assertType('int', shadowedPredicateParameter(true, is_int(...), $x));
	assertType('string', shadowedPredicateParameter(false, is_int(...), $x));
}
