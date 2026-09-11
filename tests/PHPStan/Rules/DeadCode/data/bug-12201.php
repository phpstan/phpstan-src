<?php declare(strict_types = 1);

namespace Bug12201;

// The traits live in bug-12201-traits.php which is not analysed on purpose:
// it stands for a dependency living outside of the analysed paths.
class AppKernel
{
	use MicroKernelTrait;

	/**
	 * @return list<string>
	 */
	private function getAllowedEnvs(): array
	{
		return ['prod', 'dev', 'test'];
	}
}

class AnotherKernel
{
	use MicroKernelTrait;

	private function doNothing(): void
	{
	}
}
