<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15003;

use Closure;
use function PHPStan\Testing\assertType;

/**
 * @phpstan-type Foo InvokableClass|callable(string, mixed): int
 */
class TypeImportShortcut {}

interface InvokableClass
{
	/**
	 * @param  Closure(string, ?string=): string  $fail
	 */
	public function __invoke(string $foo, Closure $fail): int;
}

/** @phpstan-import-type Foo from TypeImportShortcut */
class A
{

	/** @param callable(string):Foo|Foo $param */
	public function foo($param): void {}

}

(new A)->foo(function(string $foo) {
	assertType('string', $foo);
	return 5;
});

interface InvokableRule
{

	/**
	 * @param Closure(string): string $fail
	 */
	public function __invoke(string $attribute, mixed $value, Closure $fail);

}

/**
 * @phpstan-type FieldValidationRule InvokableRule|(callable(string, mixed, Closure): void)
 * @phpstan-type ValidationRules array<int, FieldValidationRule>|FieldValidationRule
 */
final class Field
{

	/**
	 * @param (callable(string): ValidationRules)|ValidationRules $rules
	 */
	public function rules($rules): self
	{
		return $this;
	}

	/**
	 * @param (callable(string): ValidationRules)|ValidationRules ...$rules
	 */
	public function creationRules($rules): self
	{
		return $this;
	}

}

(new Field())->rules(function ($attribute, $value, $fail) {
	assertType('string', $attribute);
	assertType('mixed', $value);
	assertType('Closure', $fail);
});

(new Field())->creationRules([
	function ($attribute, $value, $fail) {
		assertType('mixed', $attribute);
		assertType('mixed', $value);
		assertType('mixed', $fail);
	},
]);
