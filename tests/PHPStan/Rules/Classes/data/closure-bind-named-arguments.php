<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindNamedArgumentsConstant;

use Closure;

class Target
{

	protected const C = 'c';

}

class Other
{

}

// Every form binds the scope to Target, so accessing its protected constant
// is allowed no matter how the arguments are written.
Closure::bind(function () {
	return Target::C;
}, null, Target::class);
Closure::bind(closure: function () {
	return Target::C;
}, newThis: null, newScope: Target::class);
Closure::bind(closure: function () {
	return Target::C;
}, newScope: Target::class, newThis: null);
Closure::bind(newThis: null, closure: function () {
	return Target::C;
}, newScope: Target::class);
Closure::bind(newThis: null, newScope: Target::class, closure: function () {
	return Target::C;
});
Closure::bind(newScope: Target::class, closure: function () {
	return Target::C;
}, newThis: null);
Closure::bind(newScope: Target::class, newThis: null, closure: function () {
	return Target::C;
});
Closure::bind(function () {
	return Target::C;
}, null, newScope: Target::class);
Closure::bind(function () {
	return Target::C;
}, newScope: Target::class, newThis: null);

// Bound to another scope, the constant stays inaccessible.
Closure::bind(newScope: Other::class, closure: function () {
	return Target::C;
}, newThis: new Target());
