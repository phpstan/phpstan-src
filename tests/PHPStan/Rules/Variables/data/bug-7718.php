<?php

namespace Bug7718;

if ($_GET['something'] == 'banana'){
	$bananas = 1;
}

if ($_GET['something'] == 'banana'){
	echo $bananas;
}
