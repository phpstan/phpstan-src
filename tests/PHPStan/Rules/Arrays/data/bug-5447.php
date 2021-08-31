<?php

namespace Bug5447;

class A {
	const FIELD_VERSION = 'VERSION';
	const FIELD_STAMP = 'STAMP';
	const FIELD_RCV_ID = 'RCV_ID';
	const FIELD_AMOUNT = 'AMOUNT';
	const FIELD_REF = 'REF';
	const FIELD_DATE = 'DATE';
	const FIELD_RETURN = 'RETURN';
	const FIELD_CANCEL = 'CANCEL';
	const FIELD_REJECT = 'REJECT';

	/**
	 * @phpstan-var array<self::FIELD_*, string>
	 */
	private $parameters = [];

	/**
	 * @phpstan-param self::FIELD_* $key
	 */
	public function setParameter(string $key, string $value) : void
	{
		$this->parameters[$key] = $value;
	}

	/**
	 * @phpstan-return array<self::FIELD_*, string>
	 */
	public function getParameters() : array
	{
		return $this->parameters;
	}
}
