<?php // lint >= 8.3

declare(strict_types = 1);

namespace FunctionCallMbstring;

function doFoo(string $s): void
{
	// reads the internal encoding without an $encoding argument
	mb_strlen($s);
	mb_str_pad($s, 3);
	mb_str_pad($s, 3, encoding: 'UTF-8');

	// writes the settings or the position of mb_ereg_search()
	mb_internal_encoding('UTF-8');
	mb_ereg_search_init($s, 'a');
	mb_ereg_search_regs();
}
