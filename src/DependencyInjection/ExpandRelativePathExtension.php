<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Override;

final class ExpandRelativePathExtension extends CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema
	{
		return Expect::listOf('string');
	}

}
