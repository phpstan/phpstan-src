<?php

namespace Bug12267;

/**
 * @template TModel = mixed
 */
trait PrintSomething
{
	/** @var TModel */
	protected $model;

	public function printIt(): void
	{
		if (!$this->model) {
			return;
		}

		echo $this->model;
	}
}

class Class1
{
	/** @use PrintSomething<null> */
	use PrintSomething;

	public function what(): void
	{
		$this->printIt();
	}
}

class Class2
{
	/** @use PrintSomething<\Exception> */
	use PrintSomething;

	public function what(): void
	{
		$this->printIt();
	}
}
