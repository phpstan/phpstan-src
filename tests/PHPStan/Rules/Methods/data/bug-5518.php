<?php declare(strict_types = 1);

namespace Bug5518;

/** @template T */
interface TypeParse
{
}

/** @extends TypeParse<non-empty-string> */
interface TypeNonEmptyString extends TypeParse
{
}

interface Params
{
	/**
	 * @param TypeParse<T> $type
	 * @template T
	 */
	public function get(TypeParse ...$type): void;
}

class Test {
	public function exec(Params $params, TypeNonEmptyString $string): void {
		$params->get($string);
	}
}
