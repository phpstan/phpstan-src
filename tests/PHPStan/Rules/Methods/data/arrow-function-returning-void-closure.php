<?php

namespace ArrowFunctionReturningVoidClosure;

class ArrowFunctionReturningVoidClosure
{
	/**
	 * @return \Closure(): void
	 */
	public function getClosure(): \Closure
	{
		return fn () => $this->returnVoid();
	}

	public function returnVoid(): void
	{

	}
}
