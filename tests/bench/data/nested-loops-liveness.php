<?php declare(strict_types = 1);

/*
 * instructions: 1.820B
 * cycles: 2.353B
 * memory: 10.57MiB
 */

namespace BenchNestedLoopsLiveness;

/**
 * Sixteen loops nested in each other, each writing the same two variables.
 * VariableLivenessResolver resolves a loop by iterating its body until the
 * variables live at its head stop changing, and each pass of an enclosing
 * loop resolved the nested one again from nothing, so the innermost body was
 * walked a number of times exponential in the nesting depth. A loop now
 * starts from the head it resolved to for smaller live sets.
 */
function run(): string
{
	$text = '';
	$count = 0;
	while (rand(0, 1)) {
		$text .= 'a';
		$count = $count + 1;
		while (rand(0, 1)) {
			$text .= 'b';
			$count = $count + 1;
			while (rand(0, 1)) {
				$text .= 'c';
				$count = $count + 1;
				while (rand(0, 1)) {
					$text .= 'd';
					$count = $count + 1;
					while (rand(0, 1)) {
						$text .= 'e';
						$count = $count + 1;
						while (rand(0, 1)) {
							$text .= 'f';
							$count = $count + 1;
							while (rand(0, 1)) {
								$text .= 'g';
								$count = $count + 1;
								while (rand(0, 1)) {
									$text .= 'h';
									$count = $count + 1;
									while (rand(0, 1)) {
										$text .= 'i';
										$count = $count + 1;
										while (rand(0, 1)) {
											$text .= 'j';
											$count = $count + 1;
											while (rand(0, 1)) {
												$text .= 'k';
												$count = $count + 1;
												while (rand(0, 1)) {
													$text .= 'l';
													$count = $count + 1;
													while (rand(0, 1)) {
														$text .= 'm';
														$count = $count + 1;
														while (rand(0, 1)) {
															$text .= 'n';
															$count = $count + 1;
															while (rand(0, 1)) {
																$text .= 'o';
																$count = $count + 1;
																while (rand(0, 1)) {
																	$text .= 'p';
																	$count = $count + 1;
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
						}
					}
				}
			}
		}
	}

	return $text . $count;
}
