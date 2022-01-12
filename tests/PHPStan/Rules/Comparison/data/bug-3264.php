<?php

namespace Bug3264;

function (): void {
	$totalShops = 40;
	$predefinedShops = 10;

	for ($i = 0; $i < $predefinedShops; $i++)
	{
		echo "Do something $i...\n";
	}
	for (;$i < $totalShops; $i++)
	{
		echo "Do something else $i...\n";
	}
};
