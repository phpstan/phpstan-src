<?php declare(strict_types = 1);

namespace StringReplaceWithoutEffectPhpDocTypes;

class Foo
{

	/**
	 * @param 'abc' $subject
	 * @param 'x' $search
	 */
	public function phpDocTypes(string $subject, string $search): void
	{
		echo str_replace('x', '/', $subject);
		echo str_replace($search, '/', 'abc');
		echo strtr($subject, 'x', 'y');
	}

}
