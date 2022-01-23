<?php

namespace Bug6306;

trait someTrait {
	public function someNumber():int {
		return 10;
	}
}

class ParentClass {
	use someTrait {
		someNumber as myNumber;
	}
}

class SubClass extends ParentClass {
	public function number():int {
		return $this->myNumber();
	}
}
