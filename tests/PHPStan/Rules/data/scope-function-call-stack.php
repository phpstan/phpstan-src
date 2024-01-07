<?php // lint >= 8.0

namespace ScopeFunctionCallStack;

function (): void
{
	var_dump(print_r(sleep(throw new \Exception())));

	var_dump(print_r(function () {
		sleep(throw new \Exception());
	}));

	var_dump(print_r(fn () => sleep(throw new \Exception())));
};
