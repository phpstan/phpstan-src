<?php // lint >= 8.4

namespace RememberReadOnlyConstructorInPropertyHookBodies;

use function PHPStan\Testing\assertType;

if (PHP_VERSION_ID >= 80500) {
	// Since PHP 8.5, "clone with" can reinitialize any readonly property, so the
	// value assigned in the constructor is no longer remembered in other scopes.
	class UserCloneWith
	{
		public string $name {
			get {
				assertType('int', $this->type);
				return $this->name ;
			}
			set {
				if (strlen($value) === 0) {
					throw new ValueError("Name must be non-empty");
				}
				assertType('int', $this->type);
				$this->name = $value;
			}
		}

		private readonly int $type;

		public function __construct(
			string $name
		) {
			$this->name = $name;
			if (rand(0,1)) {
				$this->type = 1;
			} else {
				$this->type = 2;
			}
		}
	}
} else {
	class User
	{
		public string $name {
			get {
				assertType('1|2', $this->type);
				return $this->name ;
			}
			set {
				if (strlen($value) === 0) {
					throw new ValueError("Name must be non-empty");
				}
				assertType('1|2', $this->type);
				$this->name = $value;
			}
		}

		private readonly int $type;

		public function __construct(
			string $name
		) {
			$this->name = $name;
			if (rand(0,1)) {
				$this->type = 1;
			} else {
				$this->type = 2;
			}
		}
	}
}
