<?php

namespace ClxProductNet\Recombee\Model;

/**
 * @template-covariant Value of string|list<string>
 *
 * @immutable
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
