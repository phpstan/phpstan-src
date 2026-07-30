<?php

namespace Bug15027Property;

/**
 * @template Value of string|list<string>
 */
final class LanguageProperty
{
	/** @var Value */
	public $value;

	/**
	 * @param Value   $value
	 */
	public function __construct($value)
	{
		$this->value = $value;
	}
}

class Holder
{

	/** @var LanguageProperty<list<string>>|null */
	public $lp = null;

	/** @var LanguageProperty<string>|null */
	public static $lps = null;

}

function doFoo(): string
{
	return 'hallo';
}

function test(): void
{
	$h = new Holder();
	$h->lp = new LanguageProperty(['abc']);
	Holder::$lps = new LanguageProperty('abc' . doFoo());
}
