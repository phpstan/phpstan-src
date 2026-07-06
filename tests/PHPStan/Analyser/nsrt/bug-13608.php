<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug13608;

use function class_exists;
use function enum_exists;
use function function_exists;
use function interface_exists;
use function trait_exists;
use function PHPStan\Testing\assertType;

function impure(): void {}

// reported bug: an impure call between two function_exists() checks may define
// the missing function, so the negative result must not be remembered.
function withImpureCall(string $function): void
{
	if (function_exists($function)) {
		return;
	}

	impure();

	assertType('bool', function_exists($function));
}

function withRequire(string $function): void
{
	if (function_exists($function)) {
		return;
	}

	require __DIR__ . '/does-not-matter.php';

	assertType('bool', function_exists($function));
}

function withEval(string $function): void
{
	if (function_exists($function)) {
		return;
	}

	eval(sprintf('function %s() {}', $function));

	assertType('bool', function_exists($function));
}

function positiveResultIsKept(): void
{
	if (function_exists('foo123')) {
		assertType('true', function_exists('foo123'));
		impure();
		// a defined function cannot become undefined
		assertType('true', function_exists('foo123'));
	}
}

function negativeConstantResultForgotten(): void
{
	if (!function_exists('foo123')) {
		assertType('false', function_exists('foo123'));
		impure();
		assertType('bool', function_exists('foo123'));
	}
}

function pureCallKeepsResult(): void
{
	if (!function_exists('foo123')) {
		assertType('false', function_exists('foo123'));
		$x = strlen('foo');
		assertType('false', function_exists('foo123'));
	}
}

// analogous existence checks
function classExists(string $class): void
{
	if (class_exists($class)) {
		return;
	}

	impure();

	assertType('bool', class_exists($class));
}

function interfaceExists(string $interface): void
{
	if (interface_exists($interface)) {
		return;
	}

	impure();

	assertType('bool', interface_exists($interface));
}

function traitExists(string $trait): void
{
	if (trait_exists($trait)) {
		return;
	}

	impure();

	assertType('bool', trait_exists($trait));
}

function enumExists(string $enum): void
{
	if (enum_exists($enum)) {
		return;
	}

	impure();

	assertType('bool', enum_exists($enum));
}

// declaring a function may define the missing one, so the negative result is forgotten
function withFunctionDeclaration(string $function): void
{
	if (function_exists($function)) {
		return;
	}

	function declaredByBug13608(): void {}

	assertType('bool', function_exists($function));
}

// declaring a differently-named function cannot define the checked one
function functionDeclarationKeepsOtherName(): void
{
	if (!function_exists('some_missing_function_xyz')) {
		assertType('false', function_exists('some_missing_function_xyz'));

		function otherDeclaredByBug13608(): void {}

		assertType('false', function_exists('some_missing_function_xyz'));
	}
}

// declaring a function cannot make class_exists() true
function functionDeclarationKeepsClassExists(string $class): void
{
	if (!class_exists($class)) {
		assertType('false', class_exists($class));

		function yetAnotherDeclaredByBug13608(): void {}

		assertType('false', class_exists($class));
	}
}

// declaring a class may define the missing one, so the negative result is forgotten
function withClassDeclaration(string $class): void
{
	if (class_exists($class)) {
		return;
	}

	class DeclaredByBug13608 {}

	assertType('bool', class_exists($class));
}

// declaring a class cannot make function_exists() true
function classDeclarationKeepsFunctionExists(string $function): void
{
	if (!function_exists($function)) {
		assertType('false', function_exists($function));

		class OtherDeclaredByBug13608 {}

		assertType('false', function_exists($function));
	}
}

// the class_exists() argument carries a leading backslash while the declared class name
// does not, so only ltrim normalization matches them and forgets the negative result
function classDeclarationMatchesLeadingBackslashArgument(): void
{
	if (!class_exists('\Bug13608\LeadingBackslashClass')) {
		assertType('false', class_exists('\Bug13608\LeadingBackslashClass'));

		class LeadingBackslashClass {}

		assertType('bool', class_exists('\Bug13608\LeadingBackslashClass'));
	}
}

// declaring a trait may define the missing one, so the negative result is forgotten
function withTraitDeclaration(string $trait): void
{
	if (trait_exists($trait)) {
		return;
	}

	trait DeclaredTraitByBug13608 {}

	assertType('bool', trait_exists($trait));
}

// declaring a differently-named trait cannot define the checked one
function traitDeclarationKeepsOtherName(): void
{
	if (!trait_exists('Bug13608\\SomeMissingTrait')) {
		assertType('false', trait_exists('Bug13608\\SomeMissingTrait'));

		trait OtherDeclaredTraitByBug13608 {}

		assertType('false', trait_exists('Bug13608\\SomeMissingTrait'));
	}
}

// declaring a trait cannot make class_exists() true
function traitDeclarationKeepsClassExists(string $class): void
{
	if (!class_exists($class)) {
		assertType('false', class_exists($class));

		trait YetAnotherDeclaredTraitByBug13608 {}

		assertType('false', class_exists($class));
	}
}

// declaring an interface may define the missing one, so the negative result is forgotten
function withInterfaceDeclaration(string $interface): void
{
	if (interface_exists($interface)) {
		return;
	}

	interface DeclaredInterfaceByBug13608 {}

	assertType('bool', interface_exists($interface));
}

// declaring a differently-named interface cannot define the checked one
function interfaceDeclarationKeepsOtherName(): void
{
	if (!interface_exists('Bug13608\\SomeMissingInterface')) {
		assertType('false', interface_exists('Bug13608\\SomeMissingInterface'));

		interface OtherDeclaredInterfaceByBug13608 {}

		assertType('false', interface_exists('Bug13608\\SomeMissingInterface'));
	}
}

// declaring an enum may define the missing one, so the negative enum_exists() result is forgotten
function withEnumDeclaration(string $enum): void
{
	if (enum_exists($enum)) {
		return;
	}

	enum DeclaredEnumByBug13608 {}

	assertType('bool', enum_exists($enum));
}

// an enum is also a class, so declaring one forgets the negative class_exists() result too
function enumDeclarationForgetsClassExists(string $class): void
{
	if (class_exists($class)) {
		return;
	}

	enum AnotherDeclaredEnumByBug13608 {}

	assertType('bool', class_exists($class));
}
