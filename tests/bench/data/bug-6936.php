<?php

namespace Bug6936;

class clxAuftragController
{
	public function save_auftrag_changes():void
	{
		foreach ($_POST['adansch'] as $avkid => $adansch) {
			$aktueller_endkunde = new x();
			$avk = new avk();
			$change = 0;
			$col = [];
			if ($adansch['telefon'] != $aktueller_endkunde->telefon && '' != $adansch['telefon']) {
				$col['telefon'] = $adansch['telefon'];
				$change = 1;
			}
			if ($adansch['email'] != $aktueller_endkunde->email && '' != $adansch['email']) {
				$col['email'] = $adansch['email'];
				$change = 1;
			}
			if ($adansch['fa_gruendungsjahr'] != $aktueller_endkunde->fa_gruendungsjahr) {
				$col['fa_gruendungsjahr'] = $adansch['fa_gruendungsjahr'];
				$change = 1;
			}
			if ($adansch['fa_geschaeftsfuehrer'] != $aktueller_endkunde->fa_geschaeftsfuehrer) {
				$col['fa_geschaeftsfuehrer'] = $adansch['fa_geschaeftsfuehrer'];
				$change = 1;
			}
			if ($adansch['handelregnr'] != $aktueller_endkunde->handelregnr) {
				$col['handelregnr'] = $adansch['handelregnr'];
				$change = 1;
			}
			if ($adansch['amtsgericht'] != $aktueller_endkunde->amtsgericht) {
				$col['amtsgericht'] = $adansch['amtsgericht'];
				$change = 1;
			}
			if ($adansch['ustid'] != $aktueller_endkunde->ustid) {
				$col['ustid'] = $adansch['ustid'];
				$change = 1;
			}
			if ($adansch['ustnr'] != $aktueller_endkunde->ustnr) {
				$col['ustnr'] = $adansch['ustnr'];
				$change = 1;
			}

			if ($adansch['firma'] != $aktueller_endkunde->firma) {
				$col['firma'] = $adansch['firma'];
				$change = 1;
			}

			if (1 == $change) {
				// MobisHelper::createXmlDataJob("ada",(int)$aktueller_endkunde->adaid, $col);
				if (!isset($_SENDJOB[$avk->avkid]['ada'][$aktueller_endkunde->adaid])) {
					$_SENDJOB[$avk->avkid]['ada'][$aktueller_endkunde->adaid] = [];
				}

				$_SENDJOB[$avk->avkid]['ada'][$aktueller_endkunde->adaid] = $_SENDJOB[$avk->avkid]['ada'][$aktueller_endkunde->adaid] + $col;
			}
		}
	}
}


class x {
	/**
	 * @var int
	 */
	public $adaid;
	/**
	 * @var string
	 */
	public $telefon;
	/**
	 * @var string
	 */
	public $email;
	/**
	 * @var string
	 */
	public $fa_gruendungsjahr;
	/**
	 * @var string
	 */
	public $fa_geschaeftsfuehrer;
	/**
	 * @var string
	 */
	public $handelregnr;
	/**
	 * @var string
	 */
	public $amtsgericht;
	/**
	 * @var string
	 */
	public $ustid;
	/**
	 * @var string
	 */
	public $ustnr;
	/**
	 * @var string
	 */
	public $firma;
}
class avk {
	/**
	 * @var int
	 */
	public $avkid;
}
