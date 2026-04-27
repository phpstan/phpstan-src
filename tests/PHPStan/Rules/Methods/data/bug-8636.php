<?php declare(strict_types = 1);

namespace Bug8636;

final class Config
{
    public const SIMPLE_CONST = [
        'at' => [
            'p1' => 'en',
            'p2' => 'Austria',
            'p3' => 'b',
            'p4' => 'de_AT',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],
    ];

	public const HUGE_CONST = [
        'at' => [
            'p1' => 'en',
            'p2' => 'Austria',
            'p3' => 'b',
            'p4' => 'de_AT',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'au' => [
            'p1' => 'en',
            'p2' => 'Australia',
            'p3' => 'b',
            'p4' => 'en_AU',
            'p5' => 'AUD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a'],
        ],

        'be' => [
            'p1' => 'fr',
            'p2' => 'Belgium',
            'p3' => 'b',
            'p4' => 'fr_BE',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'bx' => [
            'p1' => 'en',
            'p2' => 'Belgium',
            'p3' => 'b',
            'p4' => 'nl_BE',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'ca' => [
            'p1' => 'en',
            'p2' => 'Canada',
            'p3' => 'b',
            'p4' => 'en_CA',
            'p5' => 'CAD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'xf' => [
            'p1' => 'fr',
            'p2' => 'Canada',
            'p3' => 'b',
            'p4' => 'fr_CA',
            'p5' => 'CAD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'ch' => [
            'p1' => 'fr',
            'p2' => 'Switzerland',
            'p3' => 'b',
            'p4' => 'fr_CH',
            'p5' => 'CHF',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a'],
        ],

        'cx' => [
            'p1' => 'en',
            'p2' => 'Switzerland',
            'p3' => 'b',
            'p4' => 'de_CH',
            'p5' => 'CHF',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'cn' => [
            'p1' => 'en',
            'p2' => 'China',
            'p3' => 'b',
            'p4' => 'zh_CN',
            'p5' => 'CNY',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a'],
        ],

        'de' => [
            'p1' => 'en',
            'p2' => 'Germany',
            'p3' => 'b',
            'p4' => 'de_DE',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'es' => [
            'p1' => 'en',
            'p2' => 'Spain',
            'p3' => 'b',
            'p4' => 'es_ES',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'fr' => [
            'p1' => 'fr',
            'p2' => 'France',
            'p3' => 'b',
            'p4' => 'fr_FR',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a'],
        ],

        'hk' => [
            'p1' => 'en',
            'p2' => 'Hong-Kong',
            'p3' => 'b',
            'p4' => 'en_HK',
            'p5' => 'HKD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'hz' => [
            'p1' => 'en',
            'p2' => 'Hong-Kong',
            'p3' => 'b',
            'p4' => 'zh_HK',
            'p5' => 'HKD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'ie' => [
            'p1' => 'en',
            'p2' => 'Ireland',
            'p3' => 'b',
            'p4' => 'en_IE',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'it' => [
            'p1' => 'en',
            'p2' => 'Italy',
            'p3' => 'b',
            'p4' => 'it_IT',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'jp' => [
            'p1' => 'en',
            'p2' => 'Japan',
            'p3' => 'b',
            'p4' => 'ja_JP',
            'p5' => 'JPY',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'kr' => [
            'p1' => 'en',
            'p2' => 'South Korea',
            'p3' => 'b',
            'p4' => 'ko_KR',
            'p5' => 'KRW',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'nl' => [
            'p1' => 'en',
            'p2' => 'Netherlands',
            'p3' => 'b',
            'p4' => 'nl_NL',
            'p5' => 'EUR',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'nz' => [
            'p1' => 'en',
            'p2' => 'New Zealand',
            'p3' => 'b',
            'p4' => 'en_NZ',
            'p5' => 'NZD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'pl' => [
            'p1' => 'en',
            'p2' => 'Poland',
            'p3' => 'b',
            'p4' => 'pl_PL',
            'p5' => 'PLN',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'sg' => [
            'p1' => 'en',
            'p2' => 'Singapore',
            'p3' => 'b',
            'p4' => 'en_SG',
            'p5' => 'SGD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

        'tw' => [
            'p1' => 'en',
            'p2' => 'Taiwan',
            'p3' => 'b',
            'p4' => 'zh_TW',
            'p5' => 'TWD',
            'p6' => 'https://',
            'p7' => 'https://',
            'p8' => [],
            'p9' => ['a', 'a', 'a'],
        ],

    ];
	
	public function simple(string $c, string $r): string
    {
        return str_replace(
			'a',
			'b',
            self::SIMPLE_CONST[$c]['p7'],
        );
    }
	
	public function huge(string $c, string $r): string
    {
        return str_replace(
			'a',
			'b',
            self::HUGE_CONST[$c]['p7'],
        );
    }
}