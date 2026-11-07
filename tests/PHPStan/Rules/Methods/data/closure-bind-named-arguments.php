<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindNamedArgumentsMethod;

use Closure;

class Target
{

	protected function prot(): int
	{
		return 1;
	}

}

class Other
{

}

function (Target $target): void {
	// Every form binds $this to $target and the scope to Target, so calling
	// its protected method is allowed no matter how the arguments are written.
	Closure::bind(function () {
		return $this->prot();
	}, $target, Target::class);
	Closure::bind(closure: function () {
		return $this->prot();
	}, newThis: $target, newScope: Target::class);
	Closure::bind(closure: function () {
		return $this->prot();
	}, newScope: Target::class, newThis: $target);
	Closure::bind(newThis: $target, closure: function () {
		return $this->prot();
	}, newScope: Target::class);
	Closure::bind(newThis: $target, newScope: Target::class, closure: function () {
		return $this->prot();
	});
	Closure::bind(newScope: Target::class, closure: function () {
		return $this->prot();
	}, newThis: $target);
	Closure::bind(newScope: Target::class, newThis: $target, closure: function () {
		return $this->prot();
	});
	Closure::bind(function () {
		return $this->prot();
	}, $target, newScope: Target::class);
	Closure::bind(function () {
		return $this->prot();
	}, newScope: Target::class, newThis: $target);

	// Bound to another scope, the method stays inaccessible.
	Closure::bind(newScope: Other::class, closure: function () use ($target) {
		return $target->prot();
	}, newThis: $target);
};
