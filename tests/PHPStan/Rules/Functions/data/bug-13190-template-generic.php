<?php // lint >= 8.0
declare(strict_types = 1);

namespace Bug13190TemplateGeneric;

class Promise
{
}

interface Result
{
}

class ResultA implements Result
{
}

/**
 * @template TypeConcurrent of bool
 */
abstract class Communicator
{
	/** @param TypeConcurrent $concurrent */
	public function __construct(private bool $concurrent)
	{
	}
}

/**
 * @template TypeCommunicator of Communicator
 * @template TypeConcurrent of bool
 * @param class-string<TypeCommunicator> $communicatorClass
 * @param TypeConcurrent $concurrent
 * @return TypeCommunicator<TypeConcurrent>
 */
function communicatorFactory(string $communicatorClass, bool $concurrent): Communicator
{
	return new $communicatorClass($concurrent);
}
