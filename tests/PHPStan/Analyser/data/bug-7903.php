<?php

declare(strict_types=1);

namespace Bug7903;

class Thing
{
	private const C         = 1;
	private const D   = 2;
	private const E   = 3;
	private const F   = 'a_';
	private const G   = 'b_';
	private const H = 'c_';
	private const I = 'd_';
	private const A = 2;
	private const B = 3;

	/** @return mixed[] */
	public function something(): array
	{
		$ggggxxxx = [
			'ccccc_rrrrr' => 0,
			'ccccc_yyyy'   => 0,
			'oooo_rrrrr' => 0,
			'oooo_yyyy'   => 0,
			'ssss'          => 0,
			'ssss_next'     => 0,
		];

		$jjjjjjxxxx = [
			'ccccc'     => 0,
			'ccccc_eur' => 0,
			'oooo'     => 0,
			'oooo_eur' => 0,
		];

		$resultsTemplate = [
			'ccccc' => 0,
			'oooo' => 0,
		];

		$vvvvTemplate = [
			0       => $jjjjjjxxxx,
			self::F => $jjjjjjxxxx,
			self::I => $jjjjjjxxxx,
			self::H => $jjjjjjxxxx,
			self::G => $jjjjjjxxxx,
		];

		$kkkkkkkTemplate = [
			0       => $ggggxxxx,
			self::C => $ggggxxxx,
			self::D => $ggggxxxx,
			self::E => $ggggxxxx,
		];

		$results = [
			'ddddd' => [
				'bbbbb'   => [0 => $kkkkkkkTemplate],
				'eeeee' => [0 => $kkkkkkkTemplate],
				'zzzz'    => [0 => $kkkkkkkTemplate],
			],
			'qqqqq'   => [
				'bbbbb'   => $kkkkkkkTemplate,
				'eeeee' => $kkkkkkkTemplate,
				'zzzz'    => $kkkkkkkTemplate,
			],
			'jjjjjj'   => [
				'bbbbb'   => [
					0       => $vvvvTemplate,
					self::A => $vvvvTemplate,
					self::B => $vvvvTemplate,
				],
				'eeeee' => $jjjjjjxxxx,
				'zzzz'    => $jjjjjjxxxx,
			],
			'aaaaaaa'  => [
				'bbbbb'     => $resultsTemplate,
				'eeeee'   => $resultsTemplate,
				'wwww' => $resultsTemplate,
				'nnnnn' => $resultsTemplate,
			],
			'iiiii'     => $resultsTemplate,
		];

		/** @var mixed[] $llllllgggg */
		$llllllgggg = [];

		foreach ($llllllgggg as $llllllmmmmmm) {
			if ((bool)$llllllmmmmmm['a']) {
				$results['aaaaaaa']['wwww']['oooo'] += (float)$llllllmmmmmm['b'];

				continue;
			}

			if ((bool)$llllllmmmmmm['c']) {
				$results['aaaaaaa']['nnnnn']['oooo'] += (float)$llllllmmmmmm['d'];

				continue;
			}

			$tttttId = $llllllmmmmmm['e'];

			$tttttuuuuu = (float)$llllllmmmmmm['b'];
			$tttttuuuuuNet = (float)$llllllmmmmmm['f'];
			$ssss = (float)$llllllmmmmmm['g'];
			$ssssNext = (float)$llllllmmmmmm['h'];
			$kkkkkkkId = (int)$llllllmmmmmm['i'];
			$isbbbbb = (bool)$llllllmmmmmm['j'];
			$key = $isbbbbb ? 'bbbbb' : 'eeeee';

			if ((bool)$llllllmmmmmm['k']) {
				$results['ddddd']['zzzz'][0][0]['oooo_rrrrr'] += $tttttuuuuu;
				$results['ddddd']['zzzz'][0][0]['oooo_yyyy'] += $tttttuuuuuNet;
				$results['ddddd']['zzzz'][0][0]['ssss'] += $ssss;
				$results['ddddd']['zzzz'][0][0]['ssss_next'] += $ssssNext;

				$results['ddddd']['zzzz'][0][$tttttId]['oooo_rrrrr'] += $tttttuuuuu;
				$results['ddddd']['zzzz'][0][$tttttId]['oooo_yyyy'] += $tttttuuuuuNet;
				$results['ddddd']['zzzz'][0][$tttttId]['ssss'] += $ssss;
				$results['ddddd']['zzzz'][0][$tttttId]['ssss_next'] += $ssssNext;

				$results['ddddd'][$key][0][0]['oooo_rrrrr'] += $tttttuuuuu;
				$results['ddddd'][$key][0][0]['oooo_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][0][0]['ssss'] += $ssss;
				$results['ddddd'][$key][0][0]['ssss_next'] += $ssssNext;

				$results['ddddd'][$key][0][$tttttId]['oooo_rrrrr'] += $tttttuuuuu;
				$results['ddddd'][$key][0][$tttttId]['oooo_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][0][$tttttId]['ssss'] += $ssss;
				$results['ddddd'][$key][0][$tttttId]['ssss_next'] += $ssssNext;

				$results['ddddd'][$key][$kkkkkkkId][0]['oooo_rrrrr'] += $tttttuuuuu;
				$results['ddddd'][$key][$kkkkkkkId][0]['oooo_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][$kkkkkkkId][0]['ssss'] += $ssss;
				$results['ddddd'][$key][$kkkkkkkId][0]['ssss_next'] += $ssssNext;

				$results['ddddd'][$key][$kkkkkkkId][$tttttId]['oooo_rrrrr'] = $tttttuuuuu;
				$results['ddddd'][$key][$kkkkkkkId][$tttttId]['oooo_yyyy'] = $tttttuuuuuNet;
				$results['ddddd'][$key][$kkkkkkkId][$tttttId]['ssss'] = $ssss;
				$results['ddddd'][$key][$kkkkkkkId][$tttttId]['ssss_next'] = $ssssNext;
			} elseif ((bool)$llllllmmmmmm['l']) {
				if (!$isbbbbb) {
					continue;
				}

				$results['qqqqq']['zzzz'][0]['oooo_yyyy'] += $tttttuuuuu;
				$results['qqqqq']['zzzz'][$tttttId]['oooo_yyyy'] += $tttttuuuuu;
				$results['qqqqq']['bbbbb'][0]['oooo_yyyy'] += $tttttuuuuu;
				$results['qqqqq']['bbbbb'][$tttttId]['oooo_yyyy'] = $tttttuuuuu;
			} else {
				throw new \LogicException('');
			}
		}

		/** @var mixed[] $aaa */
		$aaa = [];

		foreach ($aaa as $row) {
			$tttttId = $row['tttttId'];
			$kkkkkkkId = $row['kkkkkkkId'];
			$key = $row['key'];

			$tttttuuuuu = (float)$row['tttttuuuuuggggEurorrrrr'];
			$tttttuuuuuNet = (float)$row['tttttuuuuuggggEuroNet'];

			if ($row['isddddd']) {
				$results['ddddd']['zzzz'][0][0]['ccccc_rrrrr'] += $tttttuuuuu;
				$results['ddddd']['zzzz'][0][0]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['ddddd']['zzzz'][0][$tttttId]['ccccc_rrrrr'] += $tttttuuuuu;
				$results['ddddd']['zzzz'][0][$tttttId]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][0][0]['ccccc_rrrrr'] += $tttttuuuuu;
				$results['ddddd'][$key][0][0]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][0][$tttttId]['ccccc_rrrrr'] += $tttttuuuuu;
				$results['ddddd'][$key][0][$tttttId]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][$kkkkkkkId][0]['ccccc_rrrrr'] += $tttttuuuuu;
				$results['ddddd'][$key][$kkkkkkkId][0]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['ddddd'][$key][$kkkkkkkId][$tttttId]['ccccc_rrrrr'] = $tttttuuuuu;
				$results['ddddd'][$key][$kkkkkkkId][$tttttId]['ccccc_yyyy'] = $tttttuuuuuNet;
			} else {
				$results['qqqqq']['zzzz'][0]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['qqqqq']['zzzz'][$tttttId]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['qqqqq']['bbbbb'][0]['ccccc_yyyy'] += $tttttuuuuuNet;
				$results['qqqqq']['bbbbb'][$tttttId]['ccccc_yyyy'] += $tttttuuuuuNet;
			}
		}

		$results['jjjjjj']['zzzz']['ccccc_eur'] = $results['jjjjjj']['bbbbb'][0][0]['ccccc_eur'] +
			$results['jjjjjj']['eeeee']['ccccc_eur'];
		$results['jjjjjj']['zzzz']['oooo_eur'] = $results['jjjjjj']['bbbbb'][0][0]['oooo_eur'] +
			$results['jjjjjj']['eeeee']['oooo_eur'];

		$bbbbbggggccccc = $results['ddddd']['bbbbb'][0][0]['ccccc_yyyy'] +
			$results['qqqqq']['bbbbb'][0]['ccccc_yyyy'];
		$bbbbbjjjjjjccccc = $results['jjjjjj']['bbbbb'][0][0]['ccccc_eur'];

		$bbbbbggggoooo = $results['ddddd']['bbbbb'][0][0]['oooo_yyyy'] +
			$results['ddddd']['bbbbb'][0][0]['ssss_next'] +
			$results['qqqqq']['bbbbb'][0]['oooo_yyyy'];
		$bbbbbjjjjjjoooo = $results['jjjjjj']['bbbbb'][0][0]['oooo_eur'];

		$ffffffggggccccc = $results['ddddd']['eeeee'][0][0]['ccccc_yyyy'] +
			$results['qqqqq']['eeeee'][0]['ccccc_yyyy'];
		$ffffffjjjjjjccccc = $results['jjjjjj']['eeeee']['ccccc_eur'];

		$ffffffggggoooo = $results['ddddd']['eeeee'][0][0]['oooo_yyyy'] +
			$results['ddddd']['eeeee'][0][0]['ssss_next'] +
			$results['qqqqq']['eeeee'][0]['oooo_yyyy'];
		$ffffffjjjjjjoooo = $results['jjjjjj']['eeeee']['oooo_eur'];

		$results['aaaaaaa']['bbbbbbbbbbbb']['ccccc'] = $bbbbbjjjjjjccccc > 0 ? $bbbbbggggccccc - $bbbbbjjjjjjccccc : 0;
		$results['aaaaaaa']['bbbbb']['oooo'] = $bbbbbjjjjjjoooo > 0 ? $bbbbbggggoooo - $bbbbbjjjjjjoooo : 0;
		$results['aaaaaaa']['eeeee']['ccccc'] = $ffffffjjjjjjccccc > 0 ? $ffffffggggccccc - $ffffffjjjjjjccccc : 0;
		$results['aaaaaaa']['eeeee']['oooo'] = $ffffffjjjjjjoooo > 0 ? $ffffffggggoooo - $ffffffjjjjjjoooo : 0;

		$results['aaaaaaa']['zzzz']['ccccc'] =
			$results['aaaaaaa']['bbbbb']['ccccc'] +
			$results['aaaaaaa']['eeeee']['ccccc'] +
			$results['aaaaaaa']['wwww']['oooo'] +
			$results['aaaaaaa']['nnnnn']['oooo'];

		$results['aaaaaaa']['zzzz']['oooo'] =
			$results['aaaaaaa']['bbbbb']['oooo'] +
			$results['aaaaaaa']['eeeee']['oooo'] +
			$results['aaaaaaa']['wwww']['oooo'] +
			$results['aaaaaaa']['nnnnn']['oooo'];

		$results['iiiii']['ccccc'] = $bbbbbjjjjjjccccc > 0 ? ($bbbbbggggccccc / $bbbbbjjjjjjccccc * 100) - 100 : 0;
		$results['iiiii']['oooo'] = $bbbbbjjjjjjoooo > 0 ? ($bbbbbggggoooo / $bbbbbjjjjjjoooo * 100) - 100 : 0;

		return $results;
	}
}
