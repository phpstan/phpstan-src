<?php

namespace Bug11111;

enum Language: string
{
	case ENG = 'eng';
	case FRE = 'fre';
	case GER = 'ger';
	case ITA = 'ita';
	case SPA = 'spa';
	case DUT = 'dut';
	case DAN = 'dan';
}

function doFoo() {
	$langs = [
		Language::ENG,
		Language::GER,
		Language::DAN,
	];

	$array = array_fill_keys(
		$langs,
		null
	);
	$combined = array_combine(
		$langs,
		$langs
	);
}

