<?php

namespace Bug8503;

class HelloWorld
{
	public function test(): void
	{
		$matrix = [];
		$db = new \PDO('conn');
		$qry = $db->prepare('SELECT x FROM z');
		$rows = $qry->fetchAll() ?: [];

		foreach($rows as $row) {
			$matrix[$row['x']] = [];

			foreach($rows as $row2) {
				$matrix[$row['x']][$row2['x']] = [];

				foreach($rows as $row3) {
					$matrix[$row['x']][$row2['x']][$row3['x']] = [];

					foreach($rows as $row4) {
						$matrix[$row['x']][$row2['x']][$row3['x']][$row4['x']] = [];

						foreach($rows as $row5) {
							$matrix[$row['x']][$row2['x']][$row3['x']][$row4['x']][$row5['x']] = [];

							foreach($rows as $row6) {
								$matrix[$row['x']][$row2['x']][$row3['x']][$row4['x']][$row5['x']][$row6['x']] = [];

								foreach($rows as $row7) {
									$matrix[$row['x']][$row2['x']][$row3['x']][$row4['x']][$row5['x']][$row6['x']][$row7['x']] = [];

									foreach($rows as $row8) {
										$matrix[$row['x']][$row2['x']][$row3['x']][$row4['x']][$row5['x']][$row6['x']][$row7['x']][$row8['x']] = [];
									}
								}
							}
						}
					}
				}
			}
		}
	}
}
