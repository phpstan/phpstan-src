<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug12201Constant;

// The traits live in bug-12201-constant-traits.php which is not analysed on purpose:
// it stands for a dependency living outside of the analysed paths.
class AppKernel
{

	use MicroKernelTrait;

	private const ALLOWED_ENVS = ['prod', 'dev', 'test'];

}

class AnotherKernel
{

	use MicroKernelTrait;

	private const UNUSED = 'unused';

}

trait NeverFetchedTrait
{

	private const NEVER_FETCHED = 'never fetched';

}

class UsesNeverFetchedTrait
{

	use NeverFetchedTrait;

}

class ParentKernel
{

	use MicroKernelTrait;

}

// The trait is used by the parent, so it cannot reach this separate private slot.
class ChildKernel extends ParentKernel
{

	private const ALLOWED_ENVS = ['prod', 'dev', 'test'];

}

trait RedeclaredTrait
{

	private const REDECLARED = 'redeclared';

}

// This trait is analysed, so its fetches are visible and nothing needs to be assumed.
class RedeclaresAnalysedTrait
{

	use RedeclaredTrait;

	private const REDECLARED = 'redeclared';

}
