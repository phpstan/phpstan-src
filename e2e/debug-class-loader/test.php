<?php declare(strict_types = 1);

namespace DebugClassLoaderE2e;

class Consumer
{

	// Nothing ever instantiates the mistyped name, so no autoloader is invoked for it at
	// runtime. PHPStan resolves every name it sees, so it does ask - and the exception used
	// to abort the analysis of this file with an internal error instead of reporting the
	// unknown class. See https://github.com/phpstan/phpstan/issues/14976
	/** @param Widget $widget */
	public function doFoo($widget): void
	{
	}

}
