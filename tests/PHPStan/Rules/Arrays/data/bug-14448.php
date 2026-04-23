<?php declare(strict_types = 1);

namespace Bug14448;

/**
 * @return \Generator<int,array{'id':int,'type':int}>
 */
function DataGenerator() : \Generator
{
	for ( $i = 0; $i++; $i<100 )
	{
		yield [ 'id' => $i, 'type' => rand(0,100) ] ;
	}
}

$results = [];

$generator = DataGenerator();
foreach ( $generator as $data )
{
	$id = $data['id'];
    if ( !array_key_exists($id, $results ) )
    {
	    $results[$id] = [];
    }
	$type = $data['type'];
	if ( !array_key_exists('data',$results[$id]) )
	{
	  $results[$id]['data'] = [ 'types' => [], 'info' => [] ];
	}
	// Expected: No error for this line.
	if ( ! in_array($type, $results[$id]['data']['types'], true) )
	{
	  $results[$id]['data']['types'][] = $type;
	}
	// Expected: No error for this line.
	// If this if block is commented out, the error on the line above goes away
	// which is suspicious, because it should have no impact on the presence of a
	// 'types' key in $results[$id]['data']
	if ( ! in_array($type, $results[$id]['data']['info'], true) )
	{
	  $results[$id]['data']['info'][$type] = [];
	}
}
