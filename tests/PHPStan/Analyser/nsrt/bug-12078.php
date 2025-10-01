<?php declare(strict_types=1);

namespace Bug12078;

use function PHPStan\Testing\assertType;

/**
 * @return array <string,string>
 */
function returnsData6M(): array
{
	return ["A" => 'data A', "B" => 'Data B'];
}

/**
 * @return array <string,string>
 */
function returnsData3M(): array
{
	return ["A" => 'data A', "C" => 'Data C'];
}

function main()
{
	$arrDataByKey = [];

	$arrData6M = returnsData6M();
	if ([] === $arrData6M) {
		echo "No data for 6M\n";
	} else {
		foreach ($arrData6M as $key => $data) {
			$arrDataByKey[$key]['6M'][] = $data;
		}
	}

	$arrData3M = returnsData3M();
	if ([] === $arrData3M) {
		echo "No data for 3M\n";
	} else {
		foreach ($arrData3M as $key => $data) {
			$arrDataByKey[$key]['3M'][] = $data;
		}
	}
	/*
	So $arrDataByKey looks like
	[
		'A'=>[
			'6M'=>['data A'],
			'3M'=>['data A']
		],
		'B'=>[
			'6M'=>['data B']
		],
		'C'=>[
			'3M'=>['data C']
		]
	]
	*/

	assertType("array<string, non-empty-array{'6M'?: non-empty-list<string>, '3M'?: non-empty-list<string>}>", $arrDataByKey);
	foreach ($arrDataByKey as $key => $arrDataByKeyForKey) {
		assertType("non-empty-array{'6M'?: non-empty-list<string>, '3M'?: non-empty-list<string>}", $arrDataByKeyForKey);
		echo [] === ($arrDataByKeyForKey['6M'] ?? []) ? 'No 6M data for key ' . $key . "\n" : 'We got 6M data for key ' . $key . "\n";
		echo [] === ($arrDataByKeyForKey['3M'] ?? []) ? 'No 3M data for key ' . $key . "\n" : 'We got 3M data for key ' . $key . "\n";
		assertType("non-empty-array{'6M'?: non-empty-list<string>, '3M'?: non-empty-list<string>}", $arrDataByKeyForKey);
	}
}
