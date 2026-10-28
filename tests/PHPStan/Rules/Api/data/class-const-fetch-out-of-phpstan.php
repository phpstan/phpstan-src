<?php

namespace App\ClassConstFetch;

use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Turbo\TurboExtensionSelector;

class Foo
{

	public function doFoo()
	{
		echo ClassReflection::class;
		echo AnalyseCommand::class;
		echo NodeScopeResolver::class;
		echo ScopeFactory::class;

		echo NodeScopeResolver::FOO;

		echo Scope::SUPERGLOBAL_VARIABLES;
		echo MutatingScope::SUPERGLOBAL_VARIABLES;
		echo TurboExtensionSelector::EXTENSION_PATH_INI;
		echo TurboExtensionSelector::MINIMUM_PHP_VERSION_ID;
	}

}
