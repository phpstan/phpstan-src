<?php

namespace Bug1388;

final class Foo
{
	public $s;

	public function bar(): void
	{
		$data = [];

		$s = $this->s;
		$sId = $s->id;

		$data[$sId]['1'] = '1';
		$data[$sId]['2'] = '2';
		$data[$sId]['3']['31'] = false;
		$data[$sId]['4']['41']['411'] = false;
		foreach ($s->c as $c) {
			$cId = $c->id;

			$data[$sId]['nodes'][$cId]['1'] = $c->name;
			$data[$sId]['nodes'][$cId]['2'] = '2';
			$data[$sId]['nodes'][$cId]['3']['31'] = false;
			$data[$sId]['nodes'][$cId]['4']['41']['411'] = false;
			foreach ($c->d as $d) {
				$dId = $d->id;

				$data[$sId]['nodes'][$cId]['nodes'][$dId]['1'] = $d->name;
				$data[$sId]['nodes'][$cId]['nodes'][$dId]['2'] = '2';
				$data[$sId]['nodes'][$cId]['nodes'][$dId]['3']['31'] = false;
				$data[$sId]['nodes'][$cId]['nodes'][$dId]['4']['41']['411'] = false;
			}
		}
	}
}
