<?php

namespace Bug9474;

class GlazedTerracotta{
	public function getColor() : int{ return 1; }
}

class HelloWorld
{
	public function sayHello(): void
	{
		var_dump((function(GlazedTerracotta $block) : int{
			$i = match($color = $block->getColor()){
				1 => 1,
				default => throw new \Exception("Unhandled dye colour " . $color)
			};
			echo $color;
			return $i;
		})(new GlazedTerracotta));
	}
}
