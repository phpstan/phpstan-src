<?php

namespace Bug1746;

class Foo
{

	public function doFoo()
	{
		$existing = ['foo' => ['blah' => 'boohoo']];
		$assocModel = 'foo';
		$parents = ['Class' => ['foo' => 'bar', 'bar' => 'baz', 'foreignKey' => 'blah']];

		// initial value
		$isMatch = true;
		foreach ($parents as $parentModel) {
			$fk = $parentModel['foreignKey'];
			if (isset($data[$fk])) {
				// redetermine whether $isMatch is still true
				$isMatch = $isMatch && ($data[$fk] == $existing[$assocModel][$fk]);

				// bail
				if (!$isMatch) {
					break;
				}
			}
		}
	}

}
