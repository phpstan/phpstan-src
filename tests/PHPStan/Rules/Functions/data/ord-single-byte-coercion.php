<?php

namespace OrdSingleByteCoercion;

function doFoo(): void
{
	echo ord(12);
	echo ord(1);
	echo ord(true);
	echo ord(null);
}
