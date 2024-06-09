<?php declare(strict_types = 1); // lint >= 8.1

namespace Bug11114;

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

$langs = [
	Language::ENG,
	Language::GER,
	Language::DAN,
];

$result = array_diff($langs, [Language::DAN]);
