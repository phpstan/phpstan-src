<?php

namespace Bug8629;

use XMLReader;

function (): void {
	$reader = new XMLReader();
	var_dump($reader->nodeType);
};
