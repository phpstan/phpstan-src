<?php

namespace Bug8356;

function foo(): void {
	parse_str('filter[x][y]=0', $output);
	print $output['filter']['x']['y'];
}

