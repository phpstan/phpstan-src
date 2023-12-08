<?php declare(strict_types = 1);

namespace PHPStan\Testing;

use PHPStan\Rules\RuleLevelHelper;

abstract class RuleLevelHelperHack extends RuleLevelHelper
{

	public static function isCheckExplicitMixed(RuleLevelHelper $helper): bool
	{
		return $helper->checkExplicitMixed;
	}

	public static function setCheckExplicitMixed(RuleLevelHelper $helper, bool $checkExplicitMixed): void
	{
		$helper->checkExplicitMixed = $checkExplicitMixed;
	}

	public static function isCheckImplicitMixed(RuleLevelHelper $helper): bool
	{
		return $helper->checkImplicitMixed;
	}

	public static function setCheckImplicitMixed(RuleLevelHelper $helper, bool $checkImplicitMixed): void
	{
		$helper->checkImplicitMixed = $checkImplicitMixed;
	}

	public static function isCheckThisOnly(RuleLevelHelper $helper): bool
	{
		return $helper->checkThisOnly;
	}

	public static function setCheckThisOnly(RuleLevelHelper $helper, bool $checkThisOnly): void
	{
		$helper->checkThisOnly = $checkThisOnly;
	}

}
