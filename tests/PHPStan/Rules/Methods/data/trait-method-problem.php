<?php

namespace TraitProblem;

trait X
{

	abstract public static function a(self $b): void;

}

class Y
{

	use X;

	public static function a(self $b): void {}

}
