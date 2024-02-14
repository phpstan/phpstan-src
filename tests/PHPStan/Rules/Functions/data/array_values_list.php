<?php

/** @var list<int> $list */
$list = [1, 2, 3];
/** @var list<int> $list */
$array = ['a' => 1, 'b' => 2, 'c' => 3];

array_values([0,1,3]);
array_values([1,3]);
array_values(['test']);
array_values(['a' => 'test']);
array_values(['', 'test']);
array_values(['a' => '', 'b' => 'test']);
array_values($list);
array_values($array);

array_values([0]);
array_values(['a' => null, 'b' => null]);
array_values([null, null]);
array_values([null, 0]);
array_values([]);

array_values(array: $array);
array_values(array: $list);
