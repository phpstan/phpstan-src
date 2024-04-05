<?php

namespace MethodCallStatementNoSideEffects;

class Bzz
{
	function regular(string $a): string
	{
		return $a;
	}

	/**
	 * @phpstan-pure
	 */
	function pure1(string $a): string
	{
		return $a;
	}

	/**
	 * @psalm-pure
	 */
	function pure2(string $a): string
	{
		return $a;
	}

	/**
	 * @psalm-pure
	 */
	function pure3(string $a): string
	{
		return $a;
	}

	/**
	 * @phan-pure
	 */
	function pure4(string $a): string
	{
		return $a;
	}

	/**
	 * @phan-side-effect-free
	 */
	function pure5(string $a): string
	{
		return $a;
	}
}

function(): void {
	(new Bzz())->regular('test');
	(new Bzz())->pure1('test');
	(new Bzz())->pure2('test');
	(new Bzz())->pure3('test');
	(new Bzz())->pure4('test');
	(new Bzz())->pure5('test');
};
