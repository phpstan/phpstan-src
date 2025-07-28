<?php declare(strict_types = 1);

namespace Bug4993;

$inputHandle = fopen('php://stdin','r');
if ($inputHandle === false)
{
	exit(1);
}
$row = fgetcsv( $inputHandle );
if ( $row === false || $row === NULL )
{
	exit(1);
}
