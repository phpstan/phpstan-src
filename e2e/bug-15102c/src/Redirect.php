<?php declare(strict_types = 1);

namespace E2eFacadeAlias;

// The class behind the alias. Unlike bug-15102b's fixture it is NOT loaded during
// bootstrap - only Composer can autoload it - so resolving the alias makes
// class_alias() trigger a nested file read while the probe's trap is active.
class Redirect
{

	public function doFoo(): void
	{
	}

}
