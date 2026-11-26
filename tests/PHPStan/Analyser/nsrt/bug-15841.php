<?php declare(strict_types = 1);

namespace Bug15841;

use function PHPStan\Testing\assertType;

/**
 * @template TKey of string
 * @template TValue
 * @param array<TKey, TValue> $array
 * @return array<TKey, TValue>
 */
function identity(array $array) : array{
	return $array;
}

/**
 * @param array<string, array<string, list<int>>> $a
 */
function build(array $a) : void{
	$mapping = [];
	$table = [];

	foreach($a as $blockName => $remaps){
		foreach($remaps as $propertyName => $values){
			if(isset($table[$propertyName])){
				foreach($table[$propertyName] as $k => $existing){
					foreach($values as $v){
						if(!isset($existing[$v])){
							continue 2;
						}
					}

					$mapping[$blockName][$propertyName] = $k;
					continue 2;
				}
			}

			$table[$propertyName][] = $values;
			$mapping[$blockName][$propertyName] = array_key_last($table[$propertyName]);
		}
	}

	assertType('array<string, non-empty-array<string, int<0, max>>>', $mapping);
	foreach(identity($mapping) as $properties){
		assertType('non-empty-array<string, int<0, max>>', $properties);
	}
}

/**
 * @param list<array{string, array<string, int>, array<string, int>}> $table
 */
function process(array $table) : void{
	$unchanged = [];

	foreach($table as [$newName, $oldStates, $newStates]){
		if(count($oldStates) === 0){
			$unchanged[$newName] = [];
			continue;
		}

		if(!isset($unchanged[$newName])){
			$unchanged[$newName] = [];
			foreach($oldStates as $propertyName => $propertyValue){
				if(isset($newStates[$propertyName]) && $newStates[$propertyName] === $propertyValue){
					$unchanged[$newName][] = $propertyName;
				}
			}
		}else{
			foreach($unchanged[$newName] as $k => $propertyName){
				if(!isset($oldStates[$propertyName])){
					unset($unchanged[$newName][$k]);
				}
			}
		}
	}

	assertType('array<string, array<int<0, max>, string>>', $unchanged);
}
