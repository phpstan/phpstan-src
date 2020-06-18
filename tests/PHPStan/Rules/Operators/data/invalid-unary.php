<?php

function (
	int $i,
	string $str
) {
	+$i;
	-$i;
	~$i;

	+$str;
	-$str;
	~$str;

	+'123';
	-'123';
	~'123';

	+'bla';
	-'bla';
	~'123';

	$array = [];
	~$array;
	~1.1;
};
