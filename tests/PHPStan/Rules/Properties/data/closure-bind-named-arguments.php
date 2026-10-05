<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindNamedArgumentsProperty;

use Closure;

class Target
{

	private int $priv = 1;

}

class Other
{

}

function (Target $target): void {
	// Every form binds $this to $target and the scope to Target, so reading
	// its private property is allowed no matter how the arguments are written.
	Closure::bind(function () {
		return $this->priv;
	}, $target, Target::class);
	Closure::bind(closure: function () {
		return $this->priv;
	}, newThis: $target, newScope: Target::class);
	Closure::bind(closure: function () {
		return $this->priv;
	}, newScope: Target::class, newThis: $target);
	Closure::bind(newThis: $target, closure: function () {
		return $this->priv;
	}, newScope: Target::class);
	Closure::bind(newThis: $target, newScope: Target::class, closure: function () {
		return $this->priv;
	});
	Closure::bind(newScope: Target::class, closure: function () {
		return $this->priv;
	}, newThis: $target);
	Closure::bind(newScope: Target::class, newThis: $target, closure: function () {
		return $this->priv;
	});
	Closure::bind(function () {
		return $this->priv;
	}, $target, newScope: Target::class);
	Closure::bind(function () {
		return $this->priv;
	}, newScope: Target::class, newThis: $target);

	// Bound to another scope, the property stays inaccessible.
	Closure::bind(newScope: Other::class, closure: function () use ($target) {
		return $target->priv;
	}, newThis: $target);
};
