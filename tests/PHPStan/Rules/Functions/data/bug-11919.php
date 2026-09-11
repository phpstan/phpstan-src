<?php declare(strict_types = 1);

namespace Bug11919;

$handle = \fopen('some-file.csv', 'r');
if ($handle === false)
{
	exit(1);
}
$row = \fgetcsv( $handle );
while ( $row !== false )
{
	foreach ( ['a', 'b'] as $value )
	{
		// $row can never be false here
		if ( ! \in_array('some-key', $row) )
		{
			exit(1);
		}
		$row = \fgetcsv( $handle );
		// This shortcuts to the while loop - which eliminates false values
	    continue 2;
	}	
}
