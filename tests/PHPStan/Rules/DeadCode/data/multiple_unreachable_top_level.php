<?php

namespace MultipleUnreachableTopLevel;

if (true) {
	return 1;
}

echo 'statement 1';
echo 'statement 2';

function func()
{
	echo 'statement 3';
}

echo func();
