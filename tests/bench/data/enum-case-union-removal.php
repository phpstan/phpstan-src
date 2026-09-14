<?php // lint >= 8.1

declare(strict_types = 1);

namespace BenchEnumCaseUnionRemoval;

/**
 * Regression test for removing a union of enum cases from an enum type that already carries a subtraction.
 *
 * Narrowing `$country !== $onlyCountry` inside a loop over Country::cases() makes
 * TypeCombinator::remove() take a union of cases away from an enum type with some cases
 * already subtracted. The whole-union shortcut only applied when the type still held every
 * case of the union, so the cases were peeled off one at a time and every peel rebuilt and
 * re-described the growing subtracted union - quadratic in the number of cases.
 * ObjectType::tryRemove() now subtracts the still-held cases in one go.
 *
 * `bin/phpstan analyse -l 8 --debug` on this file: 3.3 s on 2.3.x, 1.6 s with the fix.
 */
enum Country: string
{

	case C1 = 'c1';
	case C2 = 'c2';
	case C3 = 'c3';
	case C4 = 'c4';
	case C5 = 'c5';
	case C6 = 'c6';
	case C7 = 'c7';
	case C8 = 'c8';
	case C9 = 'c9';
	case C10 = 'c10';
	case C11 = 'c11';
	case C12 = 'c12';
	case C13 = 'c13';
	case C14 = 'c14';
	case C15 = 'c15';
	case C16 = 'c16';
	case C17 = 'c17';
	case C18 = 'c18';
	case C19 = 'c19';
	case C20 = 'c20';
	case C21 = 'c21';
	case C22 = 'c22';
	case C23 = 'c23';
	case C24 = 'c24';
	case C25 = 'c25';
	case C26 = 'c26';
	case C27 = 'c27';
	case C28 = 'c28';
	case C29 = 'c29';
	case C30 = 'c30';
	case C31 = 'c31';
	case C32 = 'c32';
	case C33 = 'c33';
	case C34 = 'c34';
	case C35 = 'c35';
	case C36 = 'c36';
	case C37 = 'c37';
	case C38 = 'c38';
	case C39 = 'c39';
	case C40 = 'c40';
	case C41 = 'c41';
	case C42 = 'c42';
	case C43 = 'c43';
	case C44 = 'c44';
	case C45 = 'c45';
	case C46 = 'c46';
	case C47 = 'c47';
	case C48 = 'c48';
	case C49 = 'c49';
	case C50 = 'c50';
	case C51 = 'c51';
	case C52 = 'c52';
	case C53 = 'c53';
	case C54 = 'c54';
	case C55 = 'c55';
	case C56 = 'c56';
	case C57 = 'c57';
	case C58 = 'c58';
	case C59 = 'c59';
	case C60 = 'c60';
	case C61 = 'c61';
	case C62 = 'c62';
	case C63 = 'c63';
	case C64 = 'c64';
	case C65 = 'c65';
	case C66 = 'c66';
	case C67 = 'c67';
	case C68 = 'c68';
	case C69 = 'c69';
	case C70 = 'c70';
	case C71 = 'c71';
	case C72 = 'c72';
	case C73 = 'c73';
	case C74 = 'c74';
	case C75 = 'c75';
	case C76 = 'c76';
	case C77 = 'c77';
	case C78 = 'c78';
	case C79 = 'c79';
	case C80 = 'c80';
	case C81 = 'c81';
	case C82 = 'c82';
	case C83 = 'c83';
	case C84 = 'c84';
	case C85 = 'c85';
	case C86 = 'c86';
	case C87 = 'c87';
	case C88 = 'c88';
	case C89 = 'c89';
	case C90 = 'c90';
	case C91 = 'c91';
	case C92 = 'c92';
	case C93 = 'c93';
	case C94 = 'c94';
	case C95 = 'c95';
	case C96 = 'c96';
	case C97 = 'c97';
	case C98 = 'c98';
	case C99 = 'c99';
	case C100 = 'c100';
	case C101 = 'c101';
	case C102 = 'c102';
	case C103 = 'c103';
	case C104 = 'c104';
	case C105 = 'c105';
	case C106 = 'c106';
	case C107 = 'c107';
	case C108 = 'c108';
	case C109 = 'c109';
	case C110 = 'c110';
	case C111 = 'c111';
	case C112 = 'c112';
	case C113 = 'c113';
	case C114 = 'c114';
	case C115 = 'c115';
	case C116 = 'c116';
	case C117 = 'c117';
	case C118 = 'c118';
	case C119 = 'c119';
	case C120 = 'c120';
	case C121 = 'c121';
	case C122 = 'c122';
	case C123 = 'c123';
	case C124 = 'c124';
	case C125 = 'c125';
	case C126 = 'c126';
	case C127 = 'c127';
	case C128 = 'c128';
	case C129 = 'c129';
	case C130 = 'c130';
	case C131 = 'c131';
	case C132 = 'c132';
	case C133 = 'c133';
	case C134 = 'c134';
	case C135 = 'c135';
	case C136 = 'c136';
	case C137 = 'c137';
	case C138 = 'c138';
	case C139 = 'c139';
	case C140 = 'c140';
	case C141 = 'c141';
	case C142 = 'c142';
	case C143 = 'c143';
	case C144 = 'c144';
	case C145 = 'c145';
	case C146 = 'c146';
	case C147 = 'c147';
	case C148 = 'c148';
	case C149 = 'c149';
	case C150 = 'c150';
	case C151 = 'c151';
	case C152 = 'c152';
	case C153 = 'c153';
	case C154 = 'c154';
	case C155 = 'c155';
	case C156 = 'c156';
	case C157 = 'c157';
	case C158 = 'c158';
	case C159 = 'c159';
	case C160 = 'c160';
	case C161 = 'c161';
	case C162 = 'c162';
	case C163 = 'c163';
	case C164 = 'c164';
	case C165 = 'c165';
	case C166 = 'c166';
	case C167 = 'c167';
	case C168 = 'c168';
	case C169 = 'c169';
	case C170 = 'c170';
	case C171 = 'c171';
	case C172 = 'c172';
	case C173 = 'c173';
	case C174 = 'c174';
	case C175 = 'c175';
	case C176 = 'c176';
	case C177 = 'c177';
	case C178 = 'c178';
	case C179 = 'c179';
	case C180 = 'c180';
	case C181 = 'c181';
	case C182 = 'c182';
	case C183 = 'c183';
	case C184 = 'c184';
	case C185 = 'c185';
	case C186 = 'c186';
	case C187 = 'c187';
	case C188 = 'c188';
	case C189 = 'c189';
	case C190 = 'c190';
	case C191 = 'c191';
	case C192 = 'c192';
	case C193 = 'c193';
	case C194 = 'c194';
	case C195 = 'c195';
	case C196 = 'c196';
	case C197 = 'c197';
	case C198 = 'c198';
	case C199 = 'c199';
	case C200 = 'c200';
	case C201 = 'c201';
	case C202 = 'c202';
	case C203 = 'c203';
	case C204 = 'c204';
	case C205 = 'c205';
	case C206 = 'c206';
	case C207 = 'c207';
	case C208 = 'c208';
	case C209 = 'c209';
	case C210 = 'c210';
	case C211 = 'c211';
	case C212 = 'c212';
	case C213 = 'c213';
	case C214 = 'c214';
	case C215 = 'c215';
	case C216 = 'c216';
	case C217 = 'c217';
	case C218 = 'c218';
	case C219 = 'c219';
	case C220 = 'c220';
	case C221 = 'c221';
	case C222 = 'c222';
	case C223 = 'c223';
	case C224 = 'c224';
	case C225 = 'c225';
	case C226 = 'c226';
	case C227 = 'c227';
	case C228 = 'c228';
	case C229 = 'c229';
	case C230 = 'c230';
	case C231 = 'c231';
	case C232 = 'c232';
	case C233 = 'c233';
	case C234 = 'c234';
	case C235 = 'c235';
	case C236 = 'c236';
	case C237 = 'c237';
	case C238 = 'c238';
	case C239 = 'c239';
	case C240 = 'c240';
	case C241 = 'c241';
	case C242 = 'c242';
	case C243 = 'c243';
	case C244 = 'c244';
	case C245 = 'c245';
	case C246 = 'c246';
	case C247 = 'c247';
	case C248 = 'c248';
	case C249 = 'c249';
	case C250 = 'c250';
	case C251 = 'c251';
	case C252 = 'c252';
	case C253 = 'c253';
	case C254 = 'c254';
	case C255 = 'c255';
	case C256 = 'c256';
	case C257 = 'c257';
	case C258 = 'c258';
	case C259 = 'c259';
	case C260 = 'c260';
	case C261 = 'c261';
	case C262 = 'c262';
	case C263 = 'c263';
	case C264 = 'c264';
	case C265 = 'c265';
	case C266 = 'c266';
	case C267 = 'c267';
	case C268 = 'c268';
	case C269 = 'c269';
	case C270 = 'c270';
	case C271 = 'c271';
	case C272 = 'c272';
	case C273 = 'c273';
	case C274 = 'c274';
	case C275 = 'c275';
	case C276 = 'c276';
	case C277 = 'c277';
	case C278 = 'c278';
	case C279 = 'c279';
	case C280 = 'c280';
	case C281 = 'c281';
	case C282 = 'c282';
	case C283 = 'c283';
	case C284 = 'c284';
	case C285 = 'c285';
	case C286 = 'c286';
	case C287 = 'c287';
	case C288 = 'c288';
	case C289 = 'c289';
	case C290 = 'c290';
	case C291 = 'c291';
	case C292 = 'c292';
	case C293 = 'c293';
	case C294 = 'c294';
	case C295 = 'c295';
	case C296 = 'c296';
	case C297 = 'c297';
	case C298 = 'c298';
	case C299 = 'c299';
	case C300 = 'c300';
	case C301 = 'c301';
	case C302 = 'c302';
	case C303 = 'c303';
	case C304 = 'c304';
	case C305 = 'c305';
	case C306 = 'c306';
	case C307 = 'c307';
	case C308 = 'c308';
	case C309 = 'c309';
	case C310 = 'c310';
	case C311 = 'c311';
	case C312 = 'c312';
	case C313 = 'c313';
	case C314 = 'c314';
	case C315 = 'c315';
	case C316 = 'c316';
	case C317 = 'c317';
	case C318 = 'c318';
	case C319 = 'c319';
	case C320 = 'c320';
	case C321 = 'c321';
	case C322 = 'c322';
	case C323 = 'c323';
	case C324 = 'c324';
	case C325 = 'c325';
	case C326 = 'c326';
	case C327 = 'c327';
	case C328 = 'c328';
	case C329 = 'c329';
	case C330 = 'c330';
	case C331 = 'c331';
	case C332 = 'c332';
	case C333 = 'c333';
	case C334 = 'c334';
	case C335 = 'c335';
	case C336 = 'c336';
	case C337 = 'c337';
	case C338 = 'c338';
	case C339 = 'c339';
	case C340 = 'c340';
	case C341 = 'c341';
	case C342 = 'c342';
	case C343 = 'c343';
	case C344 = 'c344';
	case C345 = 'c345';
	case C346 = 'c346';
	case C347 = 'c347';
	case C348 = 'c348';
	case C349 = 'c349';
	case C350 = 'c350';
	case C351 = 'c351';
	case C352 = 'c352';
	case C353 = 'c353';
	case C354 = 'c354';
	case C355 = 'c355';
	case C356 = 'c356';
	case C357 = 'c357';
	case C358 = 'c358';
	case C359 = 'c359';
	case C360 = 'c360';
	case C361 = 'c361';
	case C362 = 'c362';
	case C363 = 'c363';
	case C364 = 'c364';
	case C365 = 'c365';
	case C366 = 'c366';
	case C367 = 'c367';
	case C368 = 'c368';
	case C369 = 'c369';
	case C370 = 'c370';
	case C371 = 'c371';
	case C372 = 'c372';
	case C373 = 'c373';
	case C374 = 'c374';
	case C375 = 'c375';
	case C376 = 'c376';
	case C377 = 'c377';
	case C378 = 'c378';
	case C379 = 'c379';
	case C380 = 'c380';
	case C381 = 'c381';
	case C382 = 'c382';
	case C383 = 'c383';
	case C384 = 'c384';
	case C385 = 'c385';
	case C386 = 'c386';
	case C387 = 'c387';
	case C388 = 'c388';
	case C389 = 'c389';
	case C390 = 'c390';
	case C391 = 'c391';
	case C392 = 'c392';
	case C393 = 'c393';
	case C394 = 'c394';
	case C395 = 'c395';
	case C396 = 'c396';
	case C397 = 'c397';
	case C398 = 'c398';
	case C399 = 'c399';
	case C400 = 'c400';
	case C401 = 'c401';
	case C402 = 'c402';
	case C403 = 'c403';
	case C404 = 'c404';
	case C405 = 'c405';
	case C406 = 'c406';
	case C407 = 'c407';
	case C408 = 'c408';
	case C409 = 'c409';
	case C410 = 'c410';
	case C411 = 'c411';
	case C412 = 'c412';
	case C413 = 'c413';
	case C414 = 'c414';
	case C415 = 'c415';
	case C416 = 'c416';
	case C417 = 'c417';
	case C418 = 'c418';
	case C419 = 'c419';
	case C420 = 'c420';
	case C421 = 'c421';
	case C422 = 'c422';
	case C423 = 'c423';
	case C424 = 'c424';
	case C425 = 'c425';
	case C426 = 'c426';
	case C427 = 'c427';
	case C428 = 'c428';
	case C429 = 'c429';
	case C430 = 'c430';
	case C431 = 'c431';
	case C432 = 'c432';
	case C433 = 'c433';
	case C434 = 'c434';
	case C435 = 'c435';
	case C436 = 'c436';
	case C437 = 'c437';
	case C438 = 'c438';
	case C439 = 'c439';
	case C440 = 'c440';
	case C441 = 'c441';
	case C442 = 'c442';
	case C443 = 'c443';
	case C444 = 'c444';
	case C445 = 'c445';
	case C446 = 'c446';
	case C447 = 'c447';
	case C448 = 'c448';
	case C449 = 'c449';
	case C450 = 'c450';
	case C451 = 'c451';
	case C452 = 'c452';
	case C453 = 'c453';
	case C454 = 'c454';
	case C455 = 'c455';
	case C456 = 'c456';
	case C457 = 'c457';
	case C458 = 'c458';
	case C459 = 'c459';
	case C460 = 'c460';
	case C461 = 'c461';
	case C462 = 'c462';
	case C463 = 'c463';
	case C464 = 'c464';
	case C465 = 'c465';
	case C466 = 'c466';
	case C467 = 'c467';
	case C468 = 'c468';
	case C469 = 'c469';
	case C470 = 'c470';
	case C471 = 'c471';
	case C472 = 'c472';
	case C473 = 'c473';
	case C474 = 'c474';
	case C475 = 'c475';
	case C476 = 'c476';
	case C477 = 'c477';
	case C478 = 'c478';
	case C479 = 'c479';
	case C480 = 'c480';
	case C481 = 'c481';
	case C482 = 'c482';
	case C483 = 'c483';
	case C484 = 'c484';
	case C485 = 'c485';
	case C486 = 'c486';
	case C487 = 'c487';
	case C488 = 'c488';
	case C489 = 'c489';
	case C490 = 'c490';
	case C491 = 'c491';
	case C492 = 'c492';
	case C493 = 'c493';
	case C494 = 'c494';
	case C495 = 'c495';
	case C496 = 'c496';
	case C497 = 'c497';
	case C498 = 'c498';
	case C499 = 'c499';
	case C500 = 'c500';

}

final class Importer
{

	public function run(?Country $onlyCountry, ?Country $startCountry, bool $onlyMissing): void
	{
		foreach (Country::cases() as $country) {
			if ($onlyCountry !== null && $country !== $onlyCountry) {
				continue;
			}

			if ($startCountry !== null && $country !== $startCountry) {
				continue;
			}

			// Start country has been found - clear it to stop skipping
			$startCountry = null;

			try {
				if ($onlyMissing && $this->count($country) > 0) {
					continue;
				}

				$this->import($country);
			} finally {
				$this->clear();
			}
		}
	}

	private function count(Country $country): int
	{
		return strlen($country->value);
	}

	private function import(Country $country): void
	{
	}

	private function clear(): void
	{
	}

}
