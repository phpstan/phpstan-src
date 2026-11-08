<?php // lint >= 8.0

namespace Bug15664;

/**
 * @no-named-arguments
 */
final class ParallelConfigFactory
{

	public static function detect(
		?int $filesPerProcess = null,
		?int $processTimeout = null,
		?int $maxProcesses = null
	): void
	{
	}

}

function doFoo(): void
{
	\call_user_func_array([ParallelConfigFactory::class, 'detect'], ['processTimeout' => 300]);

	\call_user_func_array([ParallelConfigFactory::class, 'detect'], [
		'maxProcesses' => 1,
		'processTimeout' => 300,
		'filesPerProcess' => 5,
	]);

	\call_user_func_array([ParallelConfigFactory::class, 'detect'], ['filesPerProcess' => 7]);

	\call_user_func_array([ParallelConfigFactory::class, 'detect'], ['maxProcesses' => 1]);

	\call_user_func([ParallelConfigFactory::class, 'detect'], processTimeout: 300);

	\call_user_func([ParallelConfigFactory::class, 'detect'], filesPerProcess: 7);
}
