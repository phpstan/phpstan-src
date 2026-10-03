<?php // lint >= 8.0

declare(strict_types = 1);

namespace ClosureBindNamedArguments;

use Closure;

class Target
{

	protected const C = 'c';

}

// Every form binds the scope to Target, so accessing its protected constant is allowed
// no matter how the arguments are written.
Closure::bind(static fn () => self::C, null, Target::class);
Closure::bind(closure: static fn () => self::C, newThis: null, newScope: Target::class);
Closure::bind(closure: static fn () => self::C, newScope: Target::class, newThis: null);
Closure::bind(newScope: Target::class, closure: static fn () => self::C, newThis: null);
Closure::bind(static fn () => self::C, null, newScope: Target::class);
Closure::bind(static fn () => self::C, newScope: Target::class, newThis: null);
