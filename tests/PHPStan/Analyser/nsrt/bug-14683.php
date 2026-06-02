<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14683;

use function PHPStan\Testing\assertType;

interface SomeInterface {}
class SomeClass {}
trait SomeTrait {}
enum SomeEnum {}

function classExistsOnConstantStringInterface(): void
{
	if (class_exists(SomeInterface::class)) {
		assertType('*NEVER*', SomeInterface::class);
	}
}

function classExistsOnConstantStringTrait(): void
{
	if (class_exists(SomeTrait::class)) {
		assertType('*NEVER*', SomeTrait::class);
	}
}

function interfaceExistsOnConstantStringClass(): void
{
	if (interface_exists(SomeClass::class)) {
		assertType('*NEVER*', SomeClass::class);
	}
}

function enumExistsOnConstantStringEnum(): void
{
	if (enum_exists(SomeEnum::class)) {
		assertType('\'Bug14683\\\\SomeEnum\'', SomeEnum::class);
	}
}

function classExistsOnEnumConstantString(): void
{
	// enums are classes in PHP, so this is NOT impossible
	if (class_exists(SomeEnum::class)) {
		assertType('\'Bug14683\\\\SomeEnum\'', SomeEnum::class);
	}
}
