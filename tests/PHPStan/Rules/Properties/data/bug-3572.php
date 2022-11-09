<?php declare(strict_types = 1);

namespace Bug3572;

class A
{
	private string $field;

	public function setField(string $value): void
	{
		$this->field = $value;
	}

	public static function castToB(A $a): B
	{
		$self = new B();
		$self->field = $a->field;
		return $self;
	}
}

class B extends A
{
}
