<?php

namespace Bug4346;

function (): void {
	while (true) {
		while (true) {
			break 2;
		}
	}
	echo 2;
};
