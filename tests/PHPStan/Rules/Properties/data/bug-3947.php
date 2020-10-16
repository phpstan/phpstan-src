<?php

namespace Bug3947;

class HelloWorld
{
	public function sayHello(\SimpleXMLElement $item): void
	{
		foreach ($item->items->children() as $groupItem) {
			switch ((string)$groupItem->orderType) {
			}
		}
	}
}
