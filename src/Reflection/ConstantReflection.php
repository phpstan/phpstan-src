<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface ConstantReflection extends ClassMemberReflection, GlobalConstantReflection
{

	/**
	 * @return mixed
	 */
	public function getValue();

}
