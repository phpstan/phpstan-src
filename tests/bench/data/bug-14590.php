<?php

namespace Bug14590;

function provider(): array {
	$cases = [];
	foreach ([1, 2] as $v0) {
		foreach ([1, 2] as $v1) {
			foreach ([1, 2] as $v2) {
				foreach ([1, 2] as $v3) {
					foreach ([1, 2] as $v4) {
						foreach ([1, 2] as $v5) {
							foreach ([1, 2] as $v6) {
								foreach ([1, 2] as $v7) {
									foreach ([1, 2] as $v8) {
										$cases[] = $v0;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	return $cases;
}
