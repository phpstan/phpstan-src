<?php

namespace CallToConstructorWithoutImpurePointsTransitive;

function pureFunc(): int
{
	return 1;
}

final class Helper
{

	public static function pureStatic(): int
	{
		return 1 + 1;
	}

}

class PureCtor
{

	public function __construct()
	{
		pureFunc();
		Helper::pureStatic();
	}

}

class ImpureCtor
{

	public function __construct()
	{
		echo 'x';
	}

}

function (): void {
	new PureCtor();
	new ImpureCtor();
};
