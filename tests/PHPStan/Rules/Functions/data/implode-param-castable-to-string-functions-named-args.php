<?php declare(strict_types = 1); // lint >= 8.0

namespace ImplodeParamCastableToStringFunctionsNamedArgs;

function invalidUsages()
{
	// implode weirdness
	implode(array: [['a']], separator: ',');
	implode(separator: [['a']]);
	implode(',', array: [['a']]);
	implode(separator: ',', array: [['']]);
}

function wrongNumberOfArguments(): void
{
	implode(array: ',');
	join(array: ',');
}
