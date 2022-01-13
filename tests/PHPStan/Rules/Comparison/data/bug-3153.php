<?php

namespace Bug3153;

class Foo
{

	public function doFoo()
	{
		$x = random_int(1, 1000);

		if($x < 7){
			while(true){
				$x++;

				if( $x > 10 ){
					break;
				}
			}
		}
	}

	public function doBar()
	{
		$rows = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15];
		$added_rows = 0;
		$limit = random_int(1, 20);

		foreach($rows as $row){

			if( $added_rows >= $limit ){
				break;
			}
			$added_rows++;
		}

		if( $added_rows < 3 ){
			foreach($rows as $row){

				$added_rows++;

				if( $added_rows > 10 ){
					break;
				}
			}
		}
	}

}
