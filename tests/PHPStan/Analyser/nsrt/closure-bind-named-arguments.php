<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindNamedArguments;

use Closure;
use function PHPStan\Testing\assertNativeType;
use function PHPStan\Testing\assertType;

class Target
{

	private int $priv = 1;

}

function (Target $target): void {
	// $newThis only
	Closure::bind(function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
	}, $target);
	Closure::bind(closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
	}, newThis: $target);
	Closure::bind(newThis: $target, closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
	});
	Closure::bind(function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
	}, newThis: $target);

	// $newThis and $newScope in every order
	Closure::bind(function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, $target, Target::class);
	Closure::bind(closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, newThis: $target, newScope: Target::class);
	Closure::bind(closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, newScope: Target::class, newThis: $target);
	Closure::bind(newThis: $target, closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, newScope: Target::class);
	Closure::bind(newThis: $target, newScope: Target::class, closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	});
	Closure::bind(newScope: Target::class, closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, newThis: $target);
	Closure::bind(newScope: Target::class, newThis: $target, closure: function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	});

	// positional and named arguments mixed
	Closure::bind(function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, $target, newScope: Target::class);
	Closure::bind(function () {
		assertType('ClosureBindNamedArguments\Target', $this);
		assertNativeType('ClosureBindNamedArguments\Target', $this);
		assertType('int', $this->priv);
	}, newScope: Target::class, newThis: $target);
};
