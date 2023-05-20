<?php declare(strict_types = 1);

namespace Bug9039;

/**
 * @template T
 */
class Voter
{
}

/**
 * @template-extends Voter<self::*>
 */
class Test extends Voter
{
	public const FOO = 'Foo';
	private const RULES = [self::FOO];
}
