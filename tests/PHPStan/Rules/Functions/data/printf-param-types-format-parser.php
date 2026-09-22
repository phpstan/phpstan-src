<?php

namespace PrintfParamTypesFormatParser;

function doFoo(string $s, int $i): void
{
	sprintf('%*2$d', $i, '5');
	sprintf('%1$*2$d', $i, 5);
	sprintf('%5%', $s);
}
