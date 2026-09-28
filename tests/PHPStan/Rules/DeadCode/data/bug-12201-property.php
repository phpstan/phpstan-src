<?php declare(strict_types = 1);

namespace Bug12201Property;

// The traits live in bug-12201-property-traits.php which is not analysed on purpose:
// it stands for a dependency living outside of the analysed paths.
class AppKernel
{

	use MicroKernelTrait;

	/** @var list<string> */
	private array $allowedEnvs = [];

}

class AnotherKernel
{

	use MicroKernelTrait;

	private ?string $unused = null;

}

class StaticKernel
{

	use StaticKernelTrait;

	/** @var list<string> */
	private static array $staticAllowedEnvs = [];

}

class ParentKernel
{

	use MicroKernelTrait;

}

// The trait is used by the parent, so it cannot reach this separate private slot.
class ChildKernel extends ParentKernel
{

	/** @var list<string> */
	private array $allowedEnvs = [];

}

trait NeverReadTrait
{

	/** @var list<string> */
	private array $neverRead = [];

}

class UsesNeverReadTrait
{

	use NeverReadTrait;

}

trait RedeclaredTrait
{

	/** @var list<string> */
	private array $redeclared = [];

}

// This trait is analysed, so its usages are visible and nothing needs to be assumed.
class RedeclaresAnalysedTrait
{

	use RedeclaredTrait;

	/** @var list<string> */
	private array $redeclared = [];

}
