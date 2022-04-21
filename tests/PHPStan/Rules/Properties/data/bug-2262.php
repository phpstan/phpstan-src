<?php declare(strict_types = 1);

namespace Bug2262;

Class Foo {

	/**
	 * @param \stdClass[] $items
	 * @return void
	 */
	public function bar($items)
	{
		foreach ($items as $item) {
			foreach ($item->subItems as $subItem) {
				echo $subItem->_works;
				echo $subItem->willFail;

				if (isset($subItem->someOtherAttribute)) {
					echo $subItem->willFail;
				}
			}
		}
	}
}
