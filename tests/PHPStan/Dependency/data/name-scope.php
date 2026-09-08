<?php declare(strict_types = 1);

namespace NameScopeA {

	use PHPStan\Analyser\Scope;
	use PHPStan\Analyser\MutatingScope as Mutating;
	use function strlen;
	use const PHP_EOL;
	use PHPStan\Type\{Type, VerbosityLevel};
	use const PHPStan\Type\{CONST_A, CONST_B};
	use PHPStan\Reflection\{ClassReflection, function someFunction, const SOME_CONST};

	/** first */
	class First
	{

		/** a property */
		public ?Scope $scope = null;

	}

}

namespace NameScopeB {

	use PHPStan\Reflection\ClassReflection;

	/** second */
	class Second
	{
	}

}
