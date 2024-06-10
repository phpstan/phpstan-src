<?php declare(strict_types = 1); // lint >= 8.1

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

/** @var Language[] $langs */
$langs = [
	Language::ENG,
	Language::GER,
	Language::DAN,
];

$array = array_fill_keys($langs, null);
unset($array[Language::GER]);

var_dump(array_fill_keys([Language::ITA, Language::DUT], null));
