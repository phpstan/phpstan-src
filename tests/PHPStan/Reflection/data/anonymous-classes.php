<?php

namespace AnonymousClassReflectionTest;

new class {};

new class {}; new class {}; new class {};

new class (new class {}) {
	public function __construct(object $object)
	{
	}
};
