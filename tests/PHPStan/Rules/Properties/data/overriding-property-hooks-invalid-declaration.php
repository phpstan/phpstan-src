<?php // lint >= 8.4

namespace OverridingPropertyHooksInvalidDeclaration;

class ParentClass
{

	public int $wrongParameterType {
		set(int|string $value) {
		}
	}

	public int $tooManyParameters {
		set(int|string $value) {
		}
	}

}

class Child extends ParentClass
{

	public int $wrongParameterType {
		set(string $value) {
		}
	}

	public int $tooManyParameters {
		set(int|string $value, $extra) {
		}
	}

}

class InvalidParent
{

	public int $prop {
		set(int $value, $extra) {
		}
	}

}

class ChildOfInvalidParent extends InvalidParent
{

	public int $prop {
		set(int $value) {
		}
	}

}
