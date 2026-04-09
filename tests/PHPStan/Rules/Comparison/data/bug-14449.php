<?php declare(strict_types = 1);

namespace Bug14449;

/**
 * @return \Generator<int,array{'id':int}>
 */
function DataGenerator() : \Generator
{
	yield [ 'id' => 1 ] ;
	yield [ 'id' => 1 ] ;
}

function () : void {
	$results = [];

	$generator = DataGenerator();
	foreach ( $generator as $data )
	{
		$id = $data['id'];
		if ( !array_key_exists($id, $results ) )
		{
			$results[$id] = [];
		}
		if ( !array_key_exists('data',$results[$id]) )
		{
			$results[$id]['data'] = [];
		}

		$resultData = &$results[$id]['data'];
		if ( !array_key_exists('id', $results[$id]['data']) )
		{
			$resultData['id'] = $id;
		}
	}
};
