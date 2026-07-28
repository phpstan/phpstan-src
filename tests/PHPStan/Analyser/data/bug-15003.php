<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15003;

use Closure;

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
	return 5;
});

interface InvokableRule
{

	/**
	 * @param Closure(string): string $fail
	 */
	public function __invoke(string $attribute, mixed $value, Closure $fail): void;

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

function rules(): Field
{
	return (new Field())->rules(function ($attribute, $value, $fail) {
	});
}

function creationRules(): Field
{
	return (new Field())->creationRules([
		function ($attribute, $value, $fail) {
		},
	]);
}
