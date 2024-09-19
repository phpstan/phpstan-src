<?php

namespace AnonymousClassReflectionTest;

new class {};

new class {}; new class {}; new class {};

new class (new class {}) {
	public function __construct(object $object)
	{
	}
};

class A {}

new class extends A {}; new class extends A {};

new class (new class extends A {}) extends A {
	public function __construct(object $object)
	{
	}
};

interface U {}

interface V {}

new class implements U {};

new class implements U, V {};

new class implements V, U {};
