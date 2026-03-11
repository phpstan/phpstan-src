<?php

namespace Bug13352;

use Exception;
use PDO;

class Email {
	/** @return mixed[] */
	public function getEmailGroupOther(string $s): array
	{
		return ['1'];
	}
}
class moteur_string {
	/** @param mixed $arg */
	public function coalesce($arg): string
	{
		return '1';
	}
}

class backend {
	/** @return mixed[] */
	public function getStructuredNarrative(string $s): array
	{
		return ['1'];
	}
}

/** @return mixed[] */
function function_1(mixed $var1, mixed $var2, string $var3 = 'key1'): array
{
	if ($var3 != 'key1' && $var3 != 'key2' && $var3 != 'key3') {
		throw new Exception('$var3 must be \'key1\' or \'key2\' or \'key3\'');
	}
	$var4 = new Email();
	$var5 = $var6 = $var7 = $var8 = [];
	$var9 = $var1;
	if (!empty($var1)) {
		$var10 = new moteur_string();

		if ($var3 == 'key2') {
			$var9['key4'] = $var9['key5'];
			unset(
				$var9['key6'], $var9['key7'], $var9['key8'], $var9['key9'], $var9['key10'], $var9['key11'], $var9['key12'], $var9['key13'], $var9['key14'], $var9['key15'], $var9['key16'], $var9['key17'], $var9['key18'], $var9['key19'], $var9['key20'], $var9['key21'], $var9['key22'], $var9['key23'], $var9['key24'], $var9['key25'], $var9['key26'], $var9['key27'], $var9['key28'], $var9['key29'], $var9['key30'], $var9['key31'], $var9['key32'], $var9['key33'], $var9['key34'], $var9['key35'], $var9['key36'], $var9['key35'], $var9['key36'], $var9['key37'], $var9['key38'], $var9['key39'], $var9['key40'], $var9['key41'], $var9['key42'], $var9['key43'], $var9['key44'], $var9['key5'], $var9['key45'], $var9['key46'], $var9['key47'], $var9['key48'], $var9['key49'], $var9['key50'],
				$var9['key51'], $var9['key52'], $var9['key53'], $var9['key54'], $var9['key55'], $var9['key56'], $var9['key57'], $var9['key58'], $var9['key59'], $var9['key60'], $var9['key61'], $var9['key62'], $var9['key63'], $var9['key64'], $var9['key65'], $var9['key66'], $var9['key67'], $var9['key68'], $var9['key69'], $var9['key70'], $var9['key71'], $var9['key72'], $var9['key62'],
			);
		}
//MERGE AND RENAME FIELDS
		if (!is_null($var1['key73'])) {
			$var1['key74'] = $var1['key73'];
		} elseif (!is_null($var1['key75'])) {
			$var1['key74'] = $var1['key75'];
		} else {
			$var1['key74'] = $var1['key76'];
		}
		if (!is_null($var1['key77'])) {
			$var1['key78'] = $var1['key77'];
		} elseif (!is_null($var1['key79'])) {
			$var1['key78'] = $var1['key79'];
		} else {
			$var1['key78'] = $var1['key80'];
		}
		if (!is_null($var1['key81'])) {
			$var1['key82'] = $var1['key81'];
		} elseif (!is_null($var1['key83'])) {
			$var1['key82'] = $var1['key83'];
		} else {
			$var1['key82'] = $var1['key84'];
		}
		if (!is_null($var1['key85'])) {
			$var1['key86'] = $var1['key85'];
		} elseif (!is_null($var1['key87'])) {
			$var1['key86'] = $var1['key87'];
		} else {
			$var1['key86'] = $var1['key88'];
		}
		if (!is_null($var1['key89'])) {
			$var1['key90'] = $var1['key89'];
		} elseif (!is_null($var1['key91'])) {
			$var1['key90'] = $var1['key91'];
		} else {
			$var1['key90'] = $var1['key92'];
		}
		if (!is_null($var1['key93'])) {
			$var1['key94'] = $var1['key93'];
		} elseif (!is_null($var1['key95'])) {
			$var1['key94'] = $var1['key95'];
		} else {
			$var1['key94'] = $var1['key96'];
		}
		if (!is_null($var1['key97'])) {
			$var1['key98'] = $var1['key97'];
		} elseif (!is_null($var1['key99'])) {
			$var1['key98'] = $var1['key99'];
		} else {
			$var1['key98'] = $var1['key100'];
		}
		if (!is_null($var1['key101'])) {
			$var1['key102'] = $var1['key101'];
		} elseif (!is_null($var1['key103'])) {
			$var1['key102'] = $var1['key103'];
		} else {
			$var1['key102'] = $var1['key104'];
		}

		$var1['key105']
			= $var1['key106'];
		$var1['key107']
			= $var1['key108'];
		$var1['key109']
			= $var1['key110'];
		$var1['key111']
			= $var1['key112'];
		$var1['key113']
			= $var1['key114'];
		if (!is_null($var1['key115'])) {
			$var1['key116']
				= $var1['key115'];
		} elseif (!is_null($var1['key117'])) {
			$var1['key116']
				= $var1['key117'];
		} else {
			$var1['key116']
				= $var1['key118'];
		}
		if (!is_null($var1['key119'])) {
			$var1['key120']
				= $var1['key119'];
		} elseif (!is_null($var1['key121'])) {
			$var1['key120']
				= $var1['key121'];
		} else {
			$var1['key120']
				= $var1['key122'];
		}
		$var1['key123']
			= $var1['key124'];
		$var1['key125'] = !is_null(
			$var1['key126'],
		) ? $var1['key126']
			: $var1['key127'];
		$var1['key128']
			= $var1['key129'];
		$var1['key130']
			= $var1['key131'];
		if (!is_null($var1['key132'])) {
			$var1['key133']
				= $var1['key132'];
		} else {
			$var1['key133']
				= $var1['key134'];
		}
		$var1['key135']
			= $var1['key136'];
		$var1['key137'] = !is_null(
			$var1['key138'],
		) ? $var1['key138']
			: $var1['key139'];
		$var1['key140'] = !is_null(
			$var1['key141'],
		) ? $var1['key141']
			: $var1['key142'];
		$var1['key143'] = !is_null(
			$var1['key144'],
		) ? $var1['key144']
			: $var1['key145'];
		if (!is_null($var1['key146'])) {
			$var1['key147']
				= $var1['key146'];
		} elseif (!is_null($var1['key148'])) {
			$var1['key147']
				= $var1['key148'];
		} else {
			$var1['key147']
				= $var1['key149'];
		}
		if (!is_null($var1['key150'])) {
			$var1['key151']
				= $var1['key150'];
		} elseif (!is_null($var1['key152'])) {
			$var1['key151']
				= $var1['key152'];
		} else {
			$var1['key151']
				= $var1['key153'];
		}
		if (!is_null($var1['key154'])) {
			$var1['key155']
				= $var1['key154'];
		} elseif (!is_null($var1['key156'])) {
			$var1['key155']
				= $var1['key156'];
		} else {
			$var1['key155']
				= $var1['key157'];
		}
		if (!is_null($var1['key158'])) {
			$var1['key159']
				= $var1['key158'];
		} elseif (!is_null($var1['key160'])) {
			$var1['key159']
				= $var1['key160'];
		} else {
			$var1['key159']
				= $var1['key161'];
		}
		if (!is_null($var1['key162'])) {
			$var1['key163']
				= $var1['key162'];
		} elseif (!is_null($var1['key164'])) {
			$var1['key163']
				= $var1['key164'];
		} else {
			$var1['key163']
				= $var1['key165'];
		}
		if (!is_null($var1['key166'])) {
			$var1['key167']
				= $var1['key166'];
		} elseif (!is_null($var1['key168'])) {
			$var1['key167']
				= $var1['key168'];
		} else {
			$var1['key167']
				= $var1['key169'];
		}
		if (!is_null($var1['key170'])) {
			$var1['key171']
				= $var1['key170'];
		} elseif (!is_null($var1['key172'])) {
			$var1['key171']
				= $var1['key172'];
		} else {
			$var1['key171']
				= $var1['key173'];
		}
		if (!is_null($var1['key174'])) {
			$var1['key175']
				= $var1['key174'];
		} elseif (!is_null($var1['key176'])) {
			$var1['key175']
				= $var1['key176'];
		} else {
			$var1['key175']
				= $var1['key177'];
		}
		if (!is_null($var1['key178'])) {
			$var1['key179']
				= $var1['key178'];
		} elseif (!is_null($var1['key180'])) {
			$var1['key179']
				= $var1['key180'];
		} else {
			$var1['key179']
				= $var1['key181'];
		}
		if (!is_null($var1['key182'])) {
			$var1['key183']
				= $var1['key182'];
		} elseif (!is_null($var1['key184'])) {
			$var1['key183']
				= $var1['key184'];
		} else {
			$var1['key183']
				= $var1['key185'];
		}
		$var1['key186'] = $var1['key187'];
		$var1['key188'] = $var1['key189'];
		$var1['key190'] = $var1['key191'];
		$var1['key192'] = $var1['key193'];
		$var1['key194'] = $var1['key195'];
		$var1['key196'] = $var1['key197'];
		$var1['key198'] = $var1['key199'];
		$var1['key200'] = !is_null(
			$var1['key201'],
		) ? $var1['key201']
			: $var1['key202'];
		$var1['key203'] = !is_null(
			$var1['key204'],
		) ? $var1['key204']
			: $var1['key205'];
		$var1['key206'] = !is_null(
			$var1['key207'],
		) ? $var1['key207']
			: $var1['key208'];
		$var1['key209'] = !is_null(
			$var1['key210'],
		) ? $var1['key210']
			: $var1['key211'];
		$var1['key212'] = !is_null(
			$var1['key213'],
		) ? $var1['key213']
			: $var1['key214'];
		$var1['key215'] = !is_null(
			$var1['key216'],
		) ? $var1['key216']
			: $var1['key217'];
		$var1['key218'] = !is_null(
			$var1['key219'],
		) ? $var1['key219']
			: $var1['key220'];
		$var1['key221'] = !is_null(
			$var1['key222'],
		) ? $var1['key222']
			: $var1['key223'];
		$var1['key224'] = !is_null(
			$var1['key225'],
		) ? $var1['key225']
			: $var1['key226'];
		$var1['key227'] =
			!is_null($var1['key228'])
				? $var1['key228']
				: $var1['key229'];
		$var1['key230'] =
			!is_null($var1['key231'])
				? $var1['key231']
				: $var1['key232'];
		$var1['key233'] =
			!is_null($var1['key234'])
				? $var1['key234']
				: $var1['key235'];
		if (!is_null($var1['key236'])) {
			$var1['key237'] = $var1['key236'];
		} elseif (!is_null($var1['key238'])) {
			$var1['key237'] = $var1['key238'];
		} else {
			$var1['key237'] = $var1['key239'];
		}
		if (!is_null($var1['key240'])) {
			$var1['key241'] = $var1['key240'];
		} elseif (!is_null($var1['key242'])) {
			$var1['key241'] = $var1['key242'];
		} else {
			$var1['key241'] = $var1['key243'];
		}
		if (!is_null($var1['key244'])) {
			$var1['key245'] = $var1['key244'];
		} elseif (!is_null($var1['key246'])) {
			$var1['key245'] = $var1['key246'];
		} else {
			$var1['key245'] = $var1['key247'];
		}
		if (!is_null($var1['key248'])) {
			$var1['key249'] = $var1['key248'];
		} elseif (!is_null($var1['key250'])) {
			$var1['key249'] = $var1['key250'];
		} else {
			$var1['key249'] = $var1['key251'];
		}
		if (!is_null($var1['key252'])) {
			$var1['key253'] = $var1['key252'];
		} elseif (!is_null($var1['key254'])) {
			$var1['key253'] = $var1['key254'];
		} else {
			$var1['key253'] = $var1['key255'];
		}
		if (!is_null($var1['key256'])) {
			$var1['key257'] = $var1['key256'];
		} elseif (!is_null($var1['key258'])) {
			$var1['key257'] = $var1['key258'];
		} else {
			$var1['key257'] = $var1['key259'];
		}
		if (!is_null($var1['key260'])) {
			$var1['key261'] = $var1['key260'];
		} elseif (!is_null($var1['key262'])) {
			$var1['key261'] = $var1['key262'];
		} else {
			$var1['key261'] = $var1['key263'];
		}

		if (!is_null($var1['key264'])) {
			$var1['key265']
				= $var1['key264'];
		} elseif (!is_null($var1['key266'])) {
			$var1['key265']
				= $var1['key266'];
		} else {
			$var1['key265']
				= $var1['key267'];
		}
		$var1['key268']
			= $var1['key269'];
		$var1['key270']
			= $var1['key271'];
		$var1['key272']
			= $var1['key273'];
		$var1['key274'] = !is_null(
			$var1['key275'],
		) ? $var1['key275']
			: $var1['key276'];
		$var1['key277']
			= $var1['key278'];
		$var1['key279']
			= $var1['key280'];
		$var1['key281']
			= $var1['key282'];
		$var1['key283']
			= $var1['key284'];
		$var1['key285']
			= $var1['key286'];
		$var1['key287']
			= $var1['key288'];
		$var1['key289']
			= $var1['key290'];
		$var1['key291']
			= $var1['key292'];
		$var1['key293'] = !is_null(
			$var1['key294'],
		) ? $var1['key294']
			: $var1['key295'];
		if (!is_null($var1['key296'])) {
			$var1['key297']
				= $var1['key296'];
		} elseif (!is_null($var1['key298'])) {
			$var1['key297']
				= $var1['key298'];
		} else {
			$var1['key297']
				= $var1['key299'];
		}
		$var1['key300'] = $var1['key301'];
		$var1['key302'] = $var1['key303'];
		$var1['key304']
			= $var1['key305'];
		$var1['key306']
			= $var1['key307'];
		$var1['key308']
			= $var1['key309'];
		$var1['key310'] = !is_null(
			$var1['key311'],
		) ? $var1['key311']
			: $var1['key312'];
		$var1['key313'] = !is_null(
			$var1['key314'],
		) ? $var1['key314']
			: $var1['key315'];
		$var1['key316'] = !is_null(
			$var1['key317'],
		) ? $var1['key317']
			: $var1['key318'];
		$var1['key319'] = !is_null(
			$var1['key320'],
		) ? $var1['key320']
			: $var1['key321'];
		$var1['key322'] = !is_null(
			$var1['key323'],
		) ? $var1['key323']
			: $var1['key324'];
		$var1['key325'] = !is_null(
			$var1['key326'],
		) ? $var1['key326']
			: $var1['key327'];
		$var1['key328'] = !is_null(
			$var1['key329'],
		) ? $var1['key329']
			: $var1['key330'];
		$var1['key331'] = !is_null(
			$var1['key332'],
		) ? $var1['key332']
			: $var1['key333'];
		$var1['key334'] = !is_null(
			$var1['key335'],
		) ? $var1['key335']
			: $var1['key336'];
		$var1['key337'] = !is_null(
			$var1['key338'],
		) ? $var1['key338']
			: $var1['key339'];
		$var1['key340'] = !is_null(
			$var1['key341'],
		) ? $var1['key341']
			: $var1['key342'];
		$var1['key343'] = !is_null(
			$var1['key344'],
		) ? $var1['key344']
			: $var1['key345'];
		$var1['key346'] = !is_null(
			$var1['key347'],
		) ? $var1['key347']
			: $var1['key348'];
		$var1['key349'] = !is_null(
			$var1['key350'],
		) ? $var1['key350']
			: $var1['key351'];
		$var1['key352'] = !is_null(
			$var1['key353'],
		) ? $var1['key353']
			: $var1['key354'];
		$var1['key355'] = !is_null(
			$var1['key356'],
		) ? $var1['key356']
			: $var1['key357'];
		$var1['key358'] = !is_null(
			$var1['key359'],
		) ? $var1['key359']
			: $var1['key360'];
		$var1['key361'] = !is_null(
			$var1['key362'],
		) ? $var1['key362']
			: $var1['key363'];
		$var1['key364'] = $var1['key365'];
		$var1['key366'] = $var1['key367'];
		$var1['key368'] = $var1['key369'];
		$var1['key370'] = $var1['key371'];
		if (!is_null($var1['key372'])) {
			$var1['key373'] = $var1['key372'];
		} elseif (!is_null($var1['key374'])) {
			$var1['key373'] = $var1['key374'];
		} else {
			$var1['key373'] = $var1['key375'];
		}
		if (!is_null($var1['key376'])) {
			$var1['key377'] = $var1['key376'];
		} elseif (!is_null($var1['key378'])) {
			$var1['key377'] = $var1['key378'];
		} else {
			$var1['key377'] = $var1['key379'];
		}
		if (!is_null($var1['key380'])) {
			$var1['key381'] = $var1['key380'];
		} elseif (!is_null($var1['key382'])) {
			$var1['key381'] = $var1['key382'];
		} else {
			$var1['key381'] = $var1['key383'];
		}
		if (!is_null($var1['key384'])) {
			$var1['key385'] = $var1['key384'];
		} elseif (!is_null($var1['key386'])) {
			$var1['key385'] = $var1['key386'];
		} else {
			$var1['key385'] = $var1['key387'];
		}
		if (!is_null($var1['key388'])) {
			$var1['key389'] = $var1['key388'];
		} elseif (!is_null($var1['key390'])) {
			$var1['key389'] = $var1['key390'];
		} else {
			$var1['key389'] = $var1['key391'];
		}
		if (!is_null($var1['key392'])) {
			$var1['key393'] = $var1['key392'];
		} elseif (!is_null($var1['key394'])) {
			$var1['key393'] = $var1['key394'];
		} else {
			$var1['key393'] = $var1['key395'];
		}
		if (!is_null($var1['key396'])) {
			$var1['key397'] = $var1['key396'];
		} elseif (!is_null($var1['key398'])) {
			$var1['key397'] = $var1['key398'];
		} else {
			$var1['key397'] = $var1['key399'];
		}

		if (!is_null($var1['key400'])) {
			$var1['key401']
				= $var1['key400'];
		} else {
			$var1['key401'] = $var1['key402'];
		}
		if (!is_null($var1['key403'])) {
			$var1['key404']
				= $var1['key403'];
		} elseif (!is_null($var1['key405'])) {
			$var1['key404']
				= $var1['key405'];
		} else {
			$var1['key404']
				= $var1['key406'];
		}
		$var1['key407']
			= $var1['key408'];
		$var1['key409']
			= $var1['key410'];
		if (!is_null($var1['key411'])) {
			$var1['key412']
				= $var1['key411'];
		} elseif (!is_null($var1['key413'])) {
			$var1['key412']
				= $var1['key413'];
		} else {
			$var1['key412']
				= $var1['key414'];
		}
		if (!is_null($var1['key415'])) {
			$var1['key416']
				= $var1['key415'];
		} elseif (!is_null($var1['key417'])) {
			$var1['key416']
				= $var1['key417'];
		} else {
			$var1['key416']
				= $var1['key418'];
		}
		if (!is_null($var1['key419'])) {
			$var1['key420']
				= $var1['key419'];
		} elseif (!is_null($var1['key421'])) {
			$var1['key420']
				= $var1['key421'];
		} else {
			$var1['key420']
				= $var1['key422'];
		}

		if (!is_null($var1['key423'])) {
			$var1['key424']
				= $var1['key423'];
		} elseif (!is_null($var1['key425'])) {
			$var1['key424']
				= $var1['key425'];
		} else {
			$var1['key424']
				= $var1['key426'];
		}
		if (!is_null($var1['key427'])) {
			$var1['key428']
				= $var1['key427'];
		} elseif (!is_null($var1['key429'])) {
			$var1['key428']
				= $var1['key429'];
		} else {
			$var1['key428']
				= $var1['key430'];
		}
		$var1['key431'] = $var1['key21'];
		if (!is_null($var1['key432'])) {
			$var1['key433']
				= $var1['key432'];
		} elseif (!is_null($var1['key434'])) {
			$var1['key433']
				= $var1['key434'];
		} else {
			$var1['key433']
				= $var1['key435'];
		}
		$var1['key436']
			= $var1['key437'];
		$var1['key438'] = !is_null(
			$var1['key439'],
		) ? $var1['key439']
			: $var1['key440'];

		if (isset($var1['key9']) && $var1['key9'] == 'key441') {
			if (isset($var1['key442'])) {
				$var1['key443']
					= $var1['key442'];
			} elseif (isset($var1['key444'])) {
				$var1['key443']
					= $var1['key444'];
			} else {
				$var1['key443'] = null;
			}
		}
		if (empty($var1['key443'])) {
			if (isset($var1['key445'])) {
				$var1['key443']
					= $var1['key445'];
			} elseif (isset($var1['key446'])) {
				$var1['key443']
					= $var1['key446'];
			} else {
				$var1['key443'] = null;
			}
		}

		if (!is_null($var1['key447'])) {
			$var1['key448'] = $var1['key447'];
		} elseif (!is_null($var1['key449'])) {
			$var1['key448'] = $var1['key449'];
		} else {
			$var1['key448'] = $var1['key450'];
		}
		$var1['key451']
			= $var1['key452'];
		$var1['key453'] = $var1['key454'];
		$var1['key455'] = $var1['key456'];
		if (!is_null($var1['key457'])) {
			$var1['key458']
				= $var1['key457'];
		} elseif (!is_null($var1['key459'])) {
			$var1['key458']
				= $var1['key459'];
		} else {
			$var1['key458']
				= $var1['key460'];
		}
		if (!is_null($var1['key461'])) {
			$var1['key462']
				= $var1['key461'];
		} elseif (!is_null($var1['key463'])) {
			$var1['key462']
				= $var1['key463'];
		} else {
			$var1['key462'] = $var1['key464'];
		}
		if (!is_null($var1['key465'])) {
			$var1['key466']
				= $var1['key465'];
		} elseif (!is_null($var1['key467'])) {
			$var1['key466']
				= $var1['key467'];
		} else {
			$var1['key466']
				= $var1['key468'];
		}
		if (!is_null($var1['key469'])) {
			$var1['key470'] = $var1['key469'];
		} elseif (!is_null($var1['key471'])) {
			$var1['key470'] = $var1['key471'];
		} else {
			$var1['key470'] = $var1['key472'];
		}
		if (!is_null($var1['key473'])) {
			$var1['key474'] = $var1['key473'];
		} elseif (!is_null($var1['key475'])) {
			$var1['key474'] = $var1['key475'];
		} else {
			$var1['key474'] = $var1['key476'];
		}
		if (!is_null($var1['key477'])) {
			$var1['key478'] = $var1['key477'];
		} elseif (!is_null($var1['key479'])) {
			$var1['key478'] = $var1['key479'];
		} else {
			$var1['key478'] = $var1['key480'];
		}
		if (!is_null($var1['key481'])) {
			$var1['key482'] = $var1['key481'];
		} elseif (!is_null($var1['key483'])) {
			$var1['key482'] = $var1['key483'];
		} else {
			$var1['key482'] = $var1['key484'];
		}
		if (!is_null($var1['key485'])) {
			$var1['key486'] = $var1['key485'];
		} elseif (!is_null($var1['key487'])) {
			$var1['key486'] = $var1['key487'];
		} else {
			$var1['key486'] = $var1['key488'];
		}
		if (!is_null($var1['key489'])) {
			$var1['key490'] = $var1['key489'];
		} elseif (!is_null($var1['key491'])) {
			$var1['key490'] = $var1['key491'];
		} else {
			$var1['key490'] = $var1['key492'];
		}
		if (!is_null($var1['key493'])) {
			$var1['key494']
				= $var1['key493'];
		} elseif (!is_null($var1['key495'])) {
			$var1['key494']
				= $var1['key495'];
		} else {
			$var1['key494']
				= $var1['key496'];
		}

		if (!is_null($var1['key497'])) {
			$var1['key498'] = $var1['key497'];
		} elseif (!is_null($var1['key499'])) {
			$var1['key498'] = $var1['key499'];
		} else {
			$var1['key498'] = $var1['key500'];
		}
		if (!is_null($var1['key501'])) {
			$var1['key502']
				= $var1['key501'];
		} elseif (!is_null($var1['key503'])) {
			$var1['key502']
				= $var1['key503'];
		} else {
			$var1['key502']
				= $var1['key504'];
		}
		if (!is_null($var1['key505'])) {
			$var1['key506']
				= $var1['key505'];
		} elseif (!is_null($var1['key507'])) {
			$var1['key506']
				= $var1['key507'];
		} else {
			$var1['key506']
				= $var1['key508'];
		}
		if (!is_null($var1['key509'])) {
			$var1['key510']
				= $var1['key509'];
		} elseif (!is_null($var1['key511'])) {
			$var1['key510']
				= $var1['key511'];
		} else {
			$var1['key510']
				= $var1['key512'];
		}
		$var1['key61'] = $var1['key61'] == 'key513' ? 1 : 0;
		if (!is_null($var1['key514'])) {
			$var1['key515']
				= $var1['key514'];
		} elseif (!is_null($var1['key516'])) {
			$var1['key515']
				= $var1['key516'];
		} else {
			$var1['key515']
				= $var1['key517'];
		}

		/**
		 * Quartile Ranking coalesce
		 */
		$var1['key518'] = $var1['key62'] == 'key513' ? 1 : 0;
		$var1['key519'] = $var10->coalesce(
			[
				$var1['key520'],
				$var1['key521'],
				$var1['key522'],
			],
		);

		/**
		 * Parse document multi benchmark
		 * Parse evethings related to multi benchmark data
		 * */

		$var11 = 11;
		if (
			$var1['key72'] == true
			&& $var1['key61'] == 1
		) {
			for ($var12 = 2; $var12 <= $var11; $var12++) {
				$var1['key515' . $var12] = $var10->coalesce(
					[
						$var1['key514' . $var12],
						$var1['key516' . $var12],
						$var1['key517' . $var12],
					],
				);
				$var1['key523' . $var12] = $var1['key524' . $var12];
			}

//Data for template without breaking the checkSum
			$var2['key525'] = true;
		} else {
//Data for template without breaking the checkSum
			$var2['key525'] = false;
			for ($var12 = 2; $var12 <= $var11; $var12++) {
				$var1['key515' . $var12] = null;
				$var1['key523' . $var12] = null;
			}
		}

		if (!is_null($var1['key526'])) {
			$var1['key527']
				= $var1['key526'];
		} elseif (!is_null($var1['key528'])) {
			$var1['key527']
				= $var1['key528'];
		} else {
			$var1['key527']
				= $var1['key529'];
		}
		$var1['key530'] = $var10->coalesce(
			[
				$var1['key531'],
				$var1['key532'],
			],
		);
		$var1['key533'] = $var10->coalesce(
			[
				$var1['key534'],
				$var1['key535'],
			],
		);
		if (!is_null($var1['key536'])) {
			$var1['key537']
				= $var1['key536'];
		} elseif (!is_null($var1['key538'])) {
			$var1['key537']
				= $var1['key538'];
		} else {
			$var1['key537']
				= $var1['key539'];
		}
		if (!is_null($var1['key540'])) {
			$var1['key541'] = $var1['key540'];
		} elseif (!is_null($var1['key542'])) {
			$var1['key541'] = $var1['key542'];
		} else {
			$var1['key541'] = $var1['key543'];
		}

		$var1['key544']
			= $var1['key545'];
		if (!is_null($var1['key546'])) {
			$var1['key547']
				= $var1['key546'];
		} elseif (!is_null($var1['key548'])) {
			$var1['key547']
				= $var1['key548'];
		} else {
			$var1['key547']
				= $var1['key549'];
		}
		if (!is_null($var1['key550'])) {
			$var1['key551']
				= $var1['key550'];
		} elseif (!is_null($var1['key552'])) {
			$var1['key551']
				= $var1['key552'];
		} else {
			$var1['key551']
				= $var1['key553'];
		}
		if (!is_null($var1['key554'])) {
			$var1['key555']
				= $var1['key554'];
		} elseif (!is_null($var1['key556'])) {
			$var1['key555']
				= $var1['key556'];
		} else {
			$var1['key555']
				= $var1['key557'];
		}
		if (!is_null($var1['key558'])) {
			$var1['key559']
				= $var1['key558'];
		} elseif (!is_null($var1['key560'])) {
			$var1['key559']
				= $var1['key560'];
		} else {
			$var1['key559']
				= $var1['key561'];
		}
		if (!is_null($var1['key562'])) {
			$var1['key563']
				= $var1['key562'];
		} elseif (!is_null($var1['key564'])) {
			$var1['key563']
				= $var1['key564'];
		} else {
			$var1['key563']
				= $var1['key565'];
		}
		if (!is_null($var1['key566'])) {
			$var1['key567']
				= $var1['key566'];
		} elseif (!is_null($var1['key568'])) {
			$var1['key567']
				= $var1['key568'];
		} else {
			$var1['key567']
				= $var1['key569'];
		}
		if (!is_null($var1['key570'])) {
			$var1['key571']
				= $var1['key570'];
		} elseif (!is_null($var1['key572'])) {
			$var1['key571']
				= $var1['key572'];
		} else {
			$var1['key571']
				= $var1['key573'];
		}
		if (!is_null($var1['key574'])) {
			$var1['key575']
				= $var1['key574'];
		} elseif (!is_null($var1['key576'])) {
			$var1['key575']
				= $var1['key576'];
		} else {
			$var1['key575']
				= $var1['key577'];
		}
		$var1['key578'] = $var10->coalesce(
			[
				$var1['key579'],
				$var1['key580'],
			],
		);
		$var1['key581'] = $var10->coalesce(
			[
				$var1['key582'],
				$var1['key583'],
			],
		);
		$var1['key584'] = $var10->coalesce(
			[
				$var1['key585'],
				$var1['key586'],
			],
		);
		$var1['key587'] = $var10->coalesce(
			[
				$var1['key588'],
				$var1['key589'],
			],
		);
		$var1['key590'] = $var10->coalesce(
			[
				$var1['key591'],
				$var1['key592'],
			],
		);
		$var1['key593'] = $var10->coalesce(
			[
				$var1['key594'],
				$var1['key595'],
			],
		);
		$var1['key596'] = $var10->coalesce(
			[
				$var1['key597'],
				$var1['key598'],
			],
		);
		$var1['key599'] = $var10->coalesce(
			[
				$var1['key600'],
				$var1['key601'],
			],
		);
		if (!is_null($var1['key602'])) {
			$var1['key603'] = $var1['key602'];
		} elseif (!is_null($var1['key604'])) {
			$var1['key603'] = $var1['key604'];
		} else {
			$var1['key603'] = $var1['key605'];
		}
		if (!is_null($var1['key606'])) {
			$var1['key607'] = $var1['key606'];
		} elseif (!is_null($var1['key608'])) {
			$var1['key607'] = $var1['key608'];
		} else {
			$var1['key607'] = $var1['key609'];
		}
		if (!is_null($var1['key610'])) {
			$var1['key611'] = $var1['key610'];
		} elseif (!is_null($var1['key612'])) {
			$var1['key611'] = $var1['key612'];
		} else {
			$var1['key611'] = $var1['key613'];
		}
		if (!is_null($var1['key614'])) {
			$var1['key615'] = $var1['key614'];
		} elseif (!is_null($var1['key616'])) {
			$var1['key615'] = $var1['key616'];
		} else {
			$var1['key615'] = $var1['key617'];
		}
		if (!is_null($var1['key618'])) {
			$var1['key619'] = $var1['key618'];
		} elseif (!is_null($var1['key620'])) {
			$var1['key619'] = $var1['key620'];
		} else {
			$var1['key619'] = $var1['key621'];
		}
		if (!is_null($var1['key622'])) {
			$var1['key623'] = $var1['key622'];
		} elseif (!is_null($var1['key624'])) {
			$var1['key623'] = $var1['key624'];
		} else {
			$var1['key623'] = $var1['key625'];
		}
		if (!is_null($var1['key626'])) {
			$var1['key627']
				= $var1['key626'];
		} elseif (!is_null($var1['key628'])) {
			$var1['key627']
				= $var1['key628'];
		} else {
			$var1['key627']
				= $var1['key629'];
		}
		if (!is_null($var1['key630'])) {
			$var1['key631']
				= $var1['key630'];
		} elseif (!is_null($var1['key632'])) {
			$var1['key631']
				= $var1['key632'];
		} else {
			$var1['key631']
				= $var1['key633'];
		}
		if (!is_null($var1['key634'])) {
			$var1['key635']
				= $var1['key634'];
		} elseif (!is_null($var1['key636'])) {
			$var1['key635']
				= $var1['key636'];
		} else {
			$var1['key635']
				= $var1['key637'];
		}
		if (!is_null($var1['key638'])) {
			$var1['key639']
				= $var1['key638'];
		} elseif (!is_null($var1['key640'])) {
			$var1['key639']
				= $var1['key640'];
		} else {
			$var1['key639']
				= $var1['key641'];
		}
		if (!is_null($var1['key642'])) {
			$var1['key643']
				= $var1['key642'];
		} elseif (!is_null($var1['key644'])) {
			$var1['key643']
				= $var1['key644'];
		} else {
			$var1['key643']
				= $var1['key645'];
		}
		if (!is_null($var1['key646'])) {
			$var1['key647']
				= $var1['key646'];
		} elseif (!is_null($var1['key648'])) {
			$var1['key647']
				= $var1['key648'];
		} else {
			$var1['key647']
				= $var1['key649'];
		}

		$var1['key650']
			= $var1['key651'];
		if (isset($var1['key652'])) {
			$var1['key653']
				= $var1['key652'];
		} elseif (isset($var1['key654'])) {
			$var1['key653']
				= $var1['key654'];
		} elseif (isset($var1['key655'])) {
			$var1['key653']
				= $var1['key655'];
		} elseif (isset($var1['key656'])) {
			$var1['key653']
				= $var1['key656'];
		} elseif (isset($var1['key657'])) {
			$var1['key653']
				= $var1['key657'];
		} elseif (isset($var1['key658'])) {
			$var1['key653']
				= $var1['key658'];
		} else {
			$var1['key653'] = null;
		}
		$var1['key659'] = !is_null(
			$var1['key660'],
		) ? $var1['key660']
			: $var1['key661'];
		$var1['key662']
			= $var1['key663'];

		if (isset($var1['key9']) && $var1['key9'] == 'key441') {
			if (isset($var1['key664'])) {
				$var1['key665']
					= $var1['key664'];
			} else {
				$var1['key665'] = null;
			}
		}
		if (empty($var1['key665'])) {
			if (isset($var1['key666'])) {
				$var1['key665']
					= $var1['key666'];
			} else {
				$var1['key665'] = null;
			}
		}

		if (!is_null($var1['key667'])) {
			$var1['key668']
				= $var1['key667'];
		} elseif (!is_null($var1['key669'])) {
			$var1['key668']
				= $var1['key669'];
		} else {
			$var1['key668']
				= $var1['key670'];
		}
		$var1['key671']
			= $var1['key672'];

		if (isset($var1['key9']) && $var1['key9'] == 'key441') {
			if (!is_null($var1['key673'])) {
				$var1['key674']
					= $var1['key673'];
			} elseif (!is_null($var1['key675'])) {
				$var1['key674']
					= $var1['key675'];
			} else {
				$var1['key674']
					= $var1['key676'];
			}
		}
		if (empty($var1['key674'])) {
			if (!is_null($var1['key677'])) {
				$var1['key674'] = $var1['key677'];
			} elseif (!is_null($var1['key678'])) {
				$var1['key674'] = $var1['key678'];
			} else {
				$var1['key674'] = $var1['key679'];
			}
		}

		if (!is_null($var1['key680'])) {
			$var1['key681'] = $var1['key680'];
		} elseif (!is_null($var1['key682'])) {
			$var1['key681'] = $var1['key682'];
		} else {
			$var1['key681'] = $var1['key683'];
		}
		$var1['key684']
			= $var1['key685'];
		if (isset($var1['key686'])) {
			$var1['key687']
				= $var1['key686'];
		} elseif (isset($var1['key688'])) {
			$var1['key687']
				= $var1['key688'];
		}
		$var1['key689'] = !is_null($var1['key690'])
			? $var1['key690'] : $var1['key691'];
		$var1['key692'] = !is_null($var1['key693'])
			? $var1['key693'] : $var1['key694'];
		$var1['key695'] = !is_null($var1['key696'])
			? $var1['key696'] : $var1['key697'];
		$var1['key698'] = !is_null($var1['key699'])
			? $var1['key699'] : $var1['key700'];
		$var1['key701'] = !is_null($var1['key702'])
			? $var1['key702'] : $var1['key703'];
		$var1['key704'] = !is_null($var1['key705'])
			? $var1['key705'] : $var1['key706'];
		$var1['key707'] = !is_null($var1['key708'])
			? $var1['key708'] : $var1['key709'];
		$var1['key710'] = !is_null($var1['key711'])
			? $var1['key711'] : $var1['key712'];
		if (!is_null($var1['key713'])) {
			$var1['key714']
				= $var1['key713'];
		} elseif (!is_null($var1['key715'])) {
			$var1['key714']
				= $var1['key715'];
		} else {
			$var1['key714']
				= $var1['key716'];
		}
		if (!is_null($var1['key717'])) {
			$var1['key718']
				= $var1['key717'];
		} elseif (!is_null($var1['key719'])) {
			$var1['key718']
				= $var1['key719'];
		} else {
			$var1['key718']
				= $var1['key720'];
		}
		if (!is_null($var1['key721'])) {
			$var1['key722']
				= $var1['key721'];
		} elseif (!is_null($var1['key723'])) {
			$var1['key722']
				= $var1['key723'];
		} else {
			$var1['key722']
				= $var1['key724'];
		}
		if (!is_null($var1['key725'])) {
			$var1['key726'] = $var1['key725'];
		} elseif (!is_null($var1['key727'])) {
			$var1['key726'] = $var1['key727'];
		} else {
			$var1['key726'] = $var1['key728'];
		}
		if (!is_null($var1['key729'])) {
			$var1['key730'] = $var1['key729'];
		} elseif (!is_null($var1['key731'])) {
			$var1['key730'] = $var1['key731'];
		} else {
			$var1['key730'] = $var1['key732'];
		}
		if (!is_null($var1['key733'])) {
			$var1['key734'] = $var1['key733'];
		} elseif (!is_null($var1['key735'])) {
			$var1['key734'] = $var1['key735'];
		} else {
			$var1['key734'] = $var1['key736'];
		}
		if (!is_null($var1['key737'])) {
			$var1['key738'] = $var1['key737'];
		} elseif (!is_null($var1['key739'])) {
			$var1['key738'] = $var1['key739'];
		} else {
			$var1['key738'] = $var1['key740'];
		}
		if (!is_null($var1['key741'])) {
			$var1['key742'] = $var1['key741'];
		} elseif (!is_null($var1['key743'])) {
			$var1['key742'] = $var1['key743'];
		} else {
			$var1['key742'] = $var1['key744'];
		}

//OTHERS FIELDS
		$var1['key745'] = $var1['key17'];
		$var1['key746'] = $var1['key18'];
		$var1['key747'] = $var1['key19'];
		$var1['key748'] = $var1['key20'];
		$var1['key749'] = !is_null($var1['key22'])
			? $var1['key22'] : $var1['key23'];

		$var1['key750'] = !is_null($var1['key35'])
			? $var1['key35'] : $var1['key36'];
		$var1['key751'] = $var1['key37'];

//HACK comment:74:ticket:1246
		if ($var1['key67'] == 305) {
			if (empty($var1['key11'])) {
				$var1['key474'] = null;
				$var1['key478'] = null;
				$var1['key448'] = null;
			} else {
				$var1['key478'] = $var1['key11'] . '%';
			}
			$var1['key752'] = $var1['key38'];
			$var1['key753'] = $var1['key39'];
			$var1['key754'] = $var1['key40'];
		} else {
			$var1['key752'] = !is_null($var1['key38']) ? $var1['key38']
				+ $var1['key13'] : null;
			$var1['key753'] = !is_null($var1['key39']) ? $var1['key39']
				+ $var1['key11'] : null;
			$var1['key754'] = !is_null($var1['key40']) ? $var1['key40']
				+ $var1['key12'] : null;
		}

		if (!is_null($var1['key41'])) {
			$var13 = date(
				'key755',
				strtotime(
					$var1['key41'],
				),
			);

			$var14 = explode('-', $var1['key41']);
			$var1['key756'] = $var14[0];
			$var1['key757'] = $var14[1];
			$var1['key758'] =
				$var2["MonthName{$var13}"] ?? null;
			$var1['key759'] = $var14[2];
		} else {
			$var1['key756']
				= $var1['key757']
				= $var1['key758'] = $var1['key759'] = null;
		}
		if (!is_null($var1['key42'])) {
			$var15 = date(
				'key755',
				strtotime(
					$var1['key42'],
				),
			);

			$var14 = explode('-', $var1['key42']);
			$var1['key760'] = $var14[0];
			$var1['key761'] = $var14[1];
			$var1['key762'] =
				$var2["MonthName{$var15}"] ?? null;
			$var1['key763'] = $var14[2];
		} else {
			$var1['key760']
				= $var1['key761']
				= $var1['key762'] = $var1['key763'] = null;
		}

		$var16 =
			$var1['key764'] === 'key513' ? $var1['key765']
				: $var1['key42'];
		if (isset($var16)) {
			$var17 = explode('-', $var16);
			$var18 = $var17[0];
			$var19 = $var17[1];
			$var20 = date('key755', strtotime($var16));
			$var21 = !empty($var2["MonthName{$var20}"])
				? $var2["MonthName{$var20}"] : '';
			$var22 = $var17[2];
		}

		$var1['key766'] = $var1['key43'];
		$var1['key767'] = $var1['key44'];
		$var1['key4'] = $var1['key5'];
		if (!is_null($var1['key768'])) {
			$var1['key769']
				= $var1['key768'];
		} elseif (!is_null($var1['key770'])) {
			$var1['key769']
				= $var1['key770'];
		} else {
			$var1['key769']
				= $var1['key771'];
		}
		$var24 = $var1;
//Fetch the right DocumentUniqueID regarding the template expectation
		$var23 = $var24->db()->prepare(
			'',
		);
		$var23->execute([$var1['key56']]);
		$var25 = $var23->fetch(PDO::FETCH_COLUMN);
		$var26 = $var24->getTemplateDesign($var25);
		if ($var2['key525'] && $var26['key772'] == 'key773') {
			$var1['key774'] = $var1['key47'];
			$var27 = $var1['key775'];
		} elseif ($var2['key525']) {
			$var1['key774'] = $var1['key49'];
			$var27 = $var1['key776'];
		} elseif ($var26['key772'] == 'key773') {
			$var1['key774'] = $var1['key46'];
			$var27 = $var1['key68'];
		} else {
			$var1['key774'] = $var1['key48'];
			$var27 = $var1['key777'];
		}

		$var1['key778'] = $var1['key779'];
		$var1['key780'] = $var1['key781'];
		$var1['key782'] = $var1['key50'];
		$var1['key523'] = $var10->coalesce(
			[
				$var1['key51'],
				$var1['key52'],
			],
		);
		$var1['key783'] = $var1['key34'];
		$var1['key53'] = $var1['key53'];
		$var1['key784'] = $var1['key54'];
		$var1['key785'] = $var1['key55'];
		$var1['key786'] = $var1['key56'];
		$var1['key787'] = $var1['key57'];
		$var1['key788'] = $var1['key59'];
		$var1['key789'] = $var1['key60'];
		$var1['key790'] = $var1['key70'];
		$var1['key791'] = $var1['key64'];
		$var1['key792'] = $var1['key65'];
		$var1['key793'] = $var1['key66'];
		$var1['key794'] = $var1['key71'];
		$var1['key795']
			= $var1['key796'];
		$var1['key797']
			= $var1['key798'];
		$var1['key799'] = $var1['key800'];
		$var1['key801']
			= $var1['key802'];
		$var1['key803']
			= $var1['key804'];
		$var1['key805']
			= $var1['key806'];
		$var1['key807']
			= $var1['key808'];
		$var1['key809']
			= $var1['key810'];
		$var1['key811']
			= $var1['key812'];
		$var1['key813']
			= $var1['key814'];
		$var1['key815']
			= $var1['key816'];
		$var1['key817']
			= $var1['key818'];
		$var1['key819']
			= $var1['key820'];
		$var1['key821']
			= $var1['key822'];
//UNSET useless fields
		unset(
			$var1['key73'], $var1['key75'], $var1['key76'], $var1['key77'], $var1['key79'], $var1['key80'], $var1['key81'], $var1['key83'], $var1['key84'], $var1['key85'], $var1['key87'], $var1['key88'], $var1['key89'], $var1['key91'], $var1['key92'], $var1['key93'], $var1['key95'], $var1['key96'], $var1['key97'], $var1['key99'], $var1['key100'], $var1['key101'], $var1['key103'], $var1['key104'], $var1['key106'], $var1['key108'], $var1['key110'], $var1['key112'], $var1['key114'], $var1['key115'], $var1['key117'], $var1['key118'], $var1['key119'], $var1['key121'], $var1['key122'], $var1['key124'], $var1['key126'], $var1['key127'], $var1['key129'], $var1['key131'], $var1['key132'], $var1['key134'], $var1['key136'], $var1['key138'], $var1['key139'], $var1['key141'], $var1['key142'], $var1['key144'], $var1['key145'], $var1['key146'], $var1['key148'], $var1['key149'], $var1['key150'], $var1['key152'], $var1['key153'], $var1['key154'], $var1['key156'], $var1['key157'], $var1['key158'], $var1['key160'], $var1['key161'], $var1['key162'], $var1['key164'], $var1['key165'], $var1['key166'], $var1['key168'], $var1['key169'], $var1['key170'], $var1['key172'], $var1['key173'], $var1['key174'], $var1['key176'], $var1['key177'], $var1['key178'], $var1['key180'], $var1['key181'], $var1['key182'], $var1['key184'], $var1['key185'], $var1['key187'], $var1['key189'], $var1['key191'], $var1['key193'], $var1['key195'], $var1['key197'], $var1['key199'], $var1['key201'], $var1['key202'], $var1['key204'], $var1['key205'], $var1['key207'], $var1['key208'], $var1['key210'], $var1['key211'], $var1['key213'], $var1['key214'], $var1['key216'], $var1['key217'], $var1['key219'], $var1['key220'], $var1['key222'], $var1['key223'], $var1['key225'], $var1['key226'], $var1['key228'], $var1['key229'], $var1['key231'], $var1['key232'], $var1['key234'], $var1['key235'], $var1['key236'], $var1['key238'], $var1['key239'], $var1['key240'], $var1['key242'], $var1['key243'], $var1['key244'], $var1['key246'], $var1['key247'], $var1['key248'], $var1['key250'], $var1['key251'], $var1['key252'], $var1['key254'], $var1['key255'], $var1['key256'], $var1['key258'], $var1['key259'], $var1['key260'], $var1['key262'], $var1['key263'], $var1['key264'], $var1['key266'], $var1['key267'], $var1['key269'], $var1['key271'], $var1['key273'], $var1['key275'], $var1['key276'], $var1['key278'], $var1['key280'], $var1['key282'], $var1['key284'], $var1['key286'], $var1['key288'], $var1['key290'], $var1['key292'], $var1['key294'], $var1['key295'], $var1['key296'], $var1['key298'], $var1['key299'], $var1['key301'], $var1['key303'], $var1['key305'], $var1['key307'], $var1['key309'], $var1['key311'], $var1['key312'], $var1['key314'], $var1['key315'], $var1['key317'], $var1['key318'], $var1['key320'], $var1['key321'], $var1['key323'], $var1['key324'], $var1['key326'], $var1['key327'], $var1['key329'], $var1['key330'], $var1['key332'], $var1['key333'], $var1['key335'], $var1['key336'], $var1['key338'], $var1['key339'], $var1['key341'], $var1['key342'], $var1['key344'], $var1['key345'], $var1['key347'], $var1['key348'], $var1['key350'], $var1['key351'], $var1['key353'], $var1['key354'], $var1['key356'], $var1['key357'], $var1['key359'], $var1['key360'], $var1['key362'], $var1['key363'], $var1['key365'], $var1['key367'], $var1['key369'], $var1['key371'], $var1['key372'], $var1['key374'], $var1['key375'], $var1['key376'], $var1['key378'], $var1['key379'], $var1['key380'], $var1['key382'], $var1['key383'], $var1['key384'], $var1['key386'], $var1['key387'], $var1['key388'], $var1['key390'], $var1['key391'], $var1['key392'], $var1['key394'], $var1['key395'], $var1['key396'], $var1['key398'], $var1['key399'], $var1['key400'], $var1['key402'], $var1['key21'], $var1['key403'], $var1['key405'], $var1['key406'], $var1['key408'], $var1['key410'], $var1['key414'], $var1['key413'], $var1['key411'], $var1['key418'], $var1['key417'], $var1['key415'], $var1['key422'], $var1['key421'], $var1['key419'], $var1['key426'], $var1['key425'], $var1['key423'], $var1['key427'], $var1['key429'], $var1['key430'], $var1['key432'], $var1['key434'], $var1['key435'], $var1['key437'], $var1['key439'], $var1['key440'], $var1['key445'], $var1['key446'], $var1['key447'], $var1['key449'], $var1['key450'], $var1['key452'], $var1['key454'], $var1['key456'], $var1['key460'], $var1['key459'], $var1['key457'], $var1['key464'], $var1['key463'], $var1['key461'], $var1['key468'], $var1['key467'], $var1['key465'], $var1['key469'], $var1['key471'], $var1['key472'], $var1['key473'], $var1['key475'], $var1['key476'], $var1['key477'], $var1['key479'], $var1['key480'], $var1['key481'], $var1['key483'], $var1['key484'], $var1['key485'], $var1['key487'], $var1['key488'], $var1['key489'], $var1['key491'], $var1['key492'], $var1['key442'], $var1['key444'], $var1['key493'], $var1['key495'], $var1['key496'], $var1['key630'], $var1['key632'], $var1['key633'], $var1['key634'], $var1['key636'], $var1['key637'], $var1['key638'], $var1['key640'], $var1['key641'], $var1['key642'], $var1['key644'], $var1['key645'], $var1['key646'], $var1['key648'], $var1['key649'], $var1['key500'], $var1['key499'], $var1['key497'], $var1['key501'], $var1['key503'], $var1['key504'], $var1['key505'], $var1['key507'], $var1['key508'], $var1['key509'], $var1['key511'], $var1['key512'], $var1['key514'], $var1['key516'], $var1['key517'], $var1['key823'], $var1['key824'], $var1['key825'], $var1['key826'], $var1['key827'], $var1['key828'], $var1['key829'], $var1['key830'], $var1['key831'], $var1['key832'], $var1['key833'], $var1['key834'], $var1['key835'], $var1['key836'], $var1['key837'], $var1['key838'], $var1['key839'], $var1['key840'], $var1['key841'], $var1['key842'], $var1['key843'], $var1['key844'], $var1['key845'], $var1['key846'], $var1['key847'], $var1['key848'], $var1['key849'], $var1['key850'], $var1['key851'], $var1['key852'], $var1['key526'], $var1['key528'], $var1['key529'], $var1['key532'], $var1['key531'], $var1['key535'], $var1['key534'], $var1['key536'], $var1['key538'], $var1['key539'], $var1['key540'], $var1['key542'], $var1['key543'], $var1['key545'], $var1['key546'], $var1['key548'], $var1['key549'], $var1['key550'], $var1['key552'], $var1['key553'], $var1['key554'], $var1['key556'], $var1['key557'], $var1['key558'], $var1['key560'], $var1['key561'], $var1['key562'], $var1['key564'], $var1['key565'], $var1['key566'], $var1['key568'], $var1['key569'], $var1['key570'], $var1['key572'], $var1['key573'], $var1['key574'], $var1['key576'], $var1['key577'], $var1['key580'], $var1['key583'], $var1['key586'], $var1['key589'], $var1['key579'], $var1['key582'], $var1['key585'], $var1['key588'], $var1['key592'], $var1['key591'], $var1['key595'], $var1['key594'], $var1['key598'], $var1['key597'], $var1['key601'], $var1['key600'], $var1['key602'], $var1['key604'], $var1['key605'], $var1['key606'], $var1['key608'], $var1['key609'], $var1['key610'], $var1['key612'], $var1['key613'], $var1['key614'], $var1['key616'], $var1['key617'], $var1['key618'], $var1['key620'], $var1['key621'], $var1['key622'], $var1['key624'], $var1['key625'], $var1['key626'], $var1['key628'], $var1['key629'], $var1['key651'], $var1['key652'], $var1['key654'], $var1['key655'], $var1['key660'], $var1['key661'], $var1['key663'], $var1['key666'], $var1['key664'], $var1['key667'], $var1['key669'], $var1['key670'], $var1['key672'], $var1['key677'], $var1['key678'], $var1['key679'], $var1['key673'], $var1['key675'], $var1['key676'], $var1['key680'], $var1['key682'], $var1['key683'], $var1['key685'], $var1['key686'], $var1['key688'], $var1['key656'], $var1['key657'], $var1['key658'], $var1['key690'], $var1['key691'], $var1['key693'], $var1['key694'], $var1['key696'], $var1['key697'], $var1['key699'], $var1['key700'], $var1['key702'], $var1['key703'], $var1['key705'], $var1['key706'], $var1['key708'], $var1['key709'], $var1['key711'], $var1['key712'], $var1['key713'], $var1['key715'], $var1['key716'], $var1['key717'], $var1['key719'], $var1['key720'], $var1['key721'], $var1['key723'], $var1['key724'], $var1['key725'], $var1['key727'], $var1['key728'], $var1['key729'], $var1['key731'], $var1['key732'], $var1['key733'], $var1['key735'], $var1['key736'], $var1['key737'], $var1['key739'], $var1['key740'], $var1['key741'], $var1['key743'], $var1['key744'], $var1['key62'], $var1['key17'], $var1['key18'], $var1['key19'], $var1['key20'], $var1['key22'], $var1['key23'], $var1['key24'], $var1['key25'], $var1['key26'], $var1['key27'], $var1['key28'], $var1['key29'], $var1['key30'], $var1['key31'], $var1['key32'], $var1['key33'], $var1['key34'], $var1['key36'], $var1['key35'], $var1['key37'], $var1['key38'], $var1['key39'], $var1['key40'], $var1['key11'], $var1['key12'], $var1['key13'], $var1['key41'], $var1['key43'], $var1['key44'], $var1['key5'], $var1['key768'], $var1['key770'], $var1['key771'], $var1['key779'], $var1['key781'], $var1['key46'], $var1['key47'], $var1['key50'], $var1['key52'], $var1['key49'], $var1['key48'], $var1['key54'], $var1['key55'], $var1['key56'], $var1['key57'], $var1['key59'], $var1['key60'], $var1['key70'], $var1['key64'], $var1['key65'], $var1['key66'], $var1['key71'], $var1['key796'], $var1['key798'], $var1['key800'], $var1['key802'], $var1['key804'], $var1['key806'], $var1['key808'], $var1['key810'], $var1['key812'], $var1['key814'], $var1['key816'], $var1['key818'], $var1['key820'], $var1['key822'], $var1['key62'], $var1['key520'], $var1['key521'], $var1['key522'], $var1['key51'],
		);
//Control Data for bot
		if ($var3 == 'key2') {
			$var5['key63'] = $var1['key63'];
			$var5['key753'] = $var1['key753'];
			$var5['key754'] = $var1['key754'];
			$var5['key4'] = $var1['key4'];
			$var5['key748'] = $var1['key748'];
			$var5['key431'] = $var1['key431'];
			$var5['key853'] = $var1['key774'];
		}
//End control
		switch ($var1['key791']) {
			case 'key854':
				$var1['key855'] = $var1['key200'];
				$var28 = !is_null($var1['key799'])
					? $var1['key799'] : '';
				break;
			case 'key856':
				$var1['key855']
					= $var1['key203'];
				$var28 = !is_null($var1['key801'])
					? $var1['key801']
					: $var1['key791'];
				break;
			case 'key857':
				$var1['key855']
					= $var1['key206'];
				$var28 = !is_null($var1['key803'])
					? $var1['key803'] : $var1['key791'];
				break;
			case 'key858':
				$var1['key855'] = $var1['key209'];
				$var28 = !is_null($var1['key805'])
					? $var1['key805'] : $var1['key791'];
				break;
			case 'key859':
				$var1['key855']
					= $var1['key212'];
				$var28 = !is_null($var1['key807'])
					? $var1['key807'] : $var1['key791'];
				break;
			case 'key860':
				$var1['key855'] = $var1['key215'];
				$var28 = !is_null($var1['key809'])
					? $var1['key809'] : $var1['key791'];
				break;
			case 'key861':
				$var1['key855']
					= $var1['key218'];
				$var28 = !is_null($var1['key811'])
					? $var1['key811']
					: $var1['key791'];
				break;
			case 'key862':
				$var1['key855'] = $var1['key221'];
				$var28 = !is_null($var1['key813'])
					? $var1['key813'] : $var1['key791'];
				break;
			case 'key863':
				$var1['key855'] = $var1['key224'];
				$var28 = !is_null($var1['key815'])
					? $var1['key815'] : $var1['key791'];
				break;
			case 'key864':
				$var1['key855'] = $var1['key227'];
				$var28 = !is_null($var1['key817'])
					? $var1['key817'] : $var1['key791'];
				break;
			case 'key865':
				$var1['key855'] = $var1['key230'];
				$var28 = !is_null($var1['key819'])
					? $var1['key819'] : $var1['key791'];
				break;
			case 'multi-currency_hedge':
				$var1['key855'] =
					$var1['key233'];
				$var28 = !is_null($var1['key821'])
					? $var1['key821'] : $var1['key791'];
				break;
			case null:
				$var1['key855'] = '';
				$var28 = $var1['key791'];
				break;
			default:
				$var28 = $var1['key791'];
				break;
		}
		switch ($var1['key745']) {
			case 'key866':
			case 'Cap&Dist':
				$var1['key867'] = $var1['key116'];
				$var29 = !is_null($var1['key797'])
					? $var1['key797'] : $var1['key745'];
				break;
			case 'key868';
				$var1['key867'] = $var1['key120'];
				$var29 = !is_null($var1['key795'])
					? $var1['key795'] : $var1['key745'];
				break;
			case null:
				$var1['key867'] = '';
				$var29 = $var1['key745'];
				break;
			default:
				$var29 = $var1['key745'];
				break;
		}
		if (!empty($var1['key4'])) {
			switch ($var1['key4']) {
				case 1:
					if (!is_null($var1['key279'])) {
						$var1['key277']
							= $var1['key279'];
					}
					$var1['key143'] = !is_null(
						$var1['key147'],
					) ? $var1['key147']
						: $var1['key143'];
					break;
				case 2:
					if (!is_null($var1['key281'])) {
						$var1['key277']
							= $var1['key281'];
					}
					$var1['key143'] = !is_null(
						$var1['key151'],
					) ? $var1['key151']
						: $var1['key143'];
					break;
				case 3:
					if (!is_null($var1['key283'])) {
						$var1['key277']
							= $var1['key283'];
					}
					$var1['key143'] = !is_null(
						$var1['key155'],
					) ? $var1['key155']
						: $var1['key143'];
					break;
				case 4:
					if (!is_null($var1['key285'])) {
						$var1['key277']
							= $var1['key285'];
					}
					$var1['key143'] = !is_null(
						$var1['key159'],
					) ? $var1['key159']
						: $var1['key143'];
					break;
				case 5:
					if (!is_null($var1['key287'])) {
						$var1['key277']
							= $var1['key287'];
					}
					$var1['key143'] = !is_null(
						$var1['key163'],
					) ? $var1['key163']
						: $var1['key143'];
					break;
				case 6:
					if (!is_null($var1['key289'])) {
						$var1['key277']
							= $var1['key289'];
					}
					$var1['key143'] = !is_null(
						$var1['key167'],
					) ? $var1['key167']
						: $var1['key143'];
					break;
				case 7:
					if (!is_null($var1['key291'])) {
						$var1['key277']
							= $var1['key291'];
					}
					$var1['key143'] = !is_null(
						$var1['key171'],
					) ? $var1['key171']
						: $var1['key143'];
					break;
			}
		}
//Tag replacement
		$var1['key74'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key74'] ?? '',
		);
		$var1['key78'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key78'] ?? '',
		);
		$var1['key82'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key82'] ?? '',
		);
		$var1['key86'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key86'] ?? '',
		);
		$var1['key90'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key90'] ?? '',
		);
		$var1['key778'] = str_replace(
			[
				'{scLaunchYear}',
				'{scLaunchMonthNumeric}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthNumeric}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key760'],
				$var1['key761'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key757'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key778'] ?? '',
		);
		$var1['key94'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key94'] ?? '',
		);
		$var1['key98'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key98'] ?? '',
		);
		$var1['key102'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{wkn}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{distributionType}',
				'{valoren}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key788'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var29,
				$var1['key789'],
				$var1['key790'],
			],
			$var1['key102'] ?? '',
		);
		$var1['key506'] = str_replace(
			[
				'{subfundName}',
				'{ISIN}',
				'{class}',
				'{scCurrency}',
				'{distributionType}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key786'],
				$var1['key787'],
				$var1['key766'],
				$var29,
				$var1['key790'],
			],
			$var1['key506'] ?? '',
		);
		$var30 = is_null($var1['key751'])
			? ''
			: number_format(
				round((float) $var1['key751'], 2),
				2,
			);
		$var1['key482'] = str_replace(
			[
				'{performanceFee}',
				'{performanceBenchmark}',
			],
			[
				$var30,
				$var1['key749'],
			],
			$var1['key482'] ?? '',
		);
		$var1['key404'] = str_replace(
			[
				'{performanceFee}',
				'{performanceBenchmark}',
				'{scCurrency}',
				'{hedgeType}',
			],
			[
				$var30,
				$var1['key749'],
				$var1['key766'],
				$var28,
			],
			$var1['key404'] ?? '',
		);
		if ($var1['key782'] === null) {
			$var31 = null;
			$var1['key407'] = null;
		} else {
			$var31 = number_format(
				round((float) $var1['key782'], 2),
				2,
			);
			$var1['key407'] = str_replace(
				'{pastPerformanceFee}',
				$var31,
				$var1['key407'] ?? '',
			);
		}
		$var32 = is_null($var1['key752'])
			? ''
			: number_format(
				round((float) $var1['key752'], 2),
				2,
			);
		$var1['key451'] = str_replace(
			'{conversionFeeValue}',
			$var32,
			$var1['key451'] ?? '',
		);

		$var33 = is_null($var1['key753'])
			? ''
			: number_format(
				round((float) $var1['key753'], 2),
				2,
			);

		$var34 = is_null($var1['key869'])
			? ''
			: number_format(
				round((float) $var1['key869'], 2),
				2,
			);

		$var1['key458'] = str_replace(
			[
				'{entryChargeValue}',
				'{SubscriptionFeeInFavourOfTheFund}',
			],
			[
				$var33,
				$var34,
			],
			$var1['key458'] ?? '',
		);

		$var35 = is_null($var1['key754'])
			? ''
			: number_format(
				round((float) $var1['key754'], 2),
				2,
			);

		$var36 = is_null($var1['key870'])
			? ''
			: number_format(
				round((float) $var1['key870'], 2),
				2,
			);

		$var37 = is_null($var1['key871'])
			? ''
			: number_format(
				round((float) $var1['key871'], 2),
				2,
			);

		$var38 = is_null($var1['key872'])
			? ''
			: number_format(
				round((float) $var1['key872'], 2),
				2,
			);

		$var1['key462'] = str_replace(
			[
				'{exitChargeValue}',
				'{RedemptionFeeInFavourOfTheFund}',
				'{EffectiveRedemptionFee}',
				'{EffectiveSubscriptionFee}',
			],
			[
				$var35,
				$var36,
				$var37,
				$var38,
			],
			$var1['key462'] ?? '',
		);

		$var1['key466'] = str_replace(
			'{conversionFeeValue}',
			$var32,
			$var1['key466'] ?? '',
		);

		if ($var1['key53'] === null) {
			$var1['key671'] = null;
		} else {
			$var1['key671'] = str_replace(
				'{otherShareclasses}',
				$var1['key53'],
				$var1['key671'] ?? '',
			);
		}
		if ($var1['key766'] === null || $var1['key767'] === null) {
			$var1['key684'] = null;
		} else {
			$var1['key684'] = str_replace(
				[
					'{scCurrency}',
					'{sfCurrency}',
				],
				[
					$var1['key766'],
					$var1['key767'],
				],
				$var1['key684'] ?? '',
			);
		}
		$var39 = [
			'{benchmark}',
			'{scCurrency}',
			'{hedgeType}',
			'{quartileRanking}',
		];
		$var40 = [
			$var1['key523'],
			$var1['key766'],
			$var28 ?? '',
			$var1['key783'],
		];
		for ($var12 = 2; $var12 <= $var11; $var12++) {
			$var39[] = "{benchmark$var12}";
			$var40[] = $var1['key523' . $var12];
		}
		for ($var12 = 2; $var12 <= $var11; $var12++) {
			$var39[] = "{benchmark$var12}";
			$var40[] = $var1['key523' . $var12];
		}


		$var1['key515'] = str_replace(
			$var39,
			$var40,
			$var1['key515'] ?? '',
		);


		for ($var12 = 2; $var12 <= $var11; $var12++) {
			$var1['key515' . $var12] = str_replace(
				$var39,
				$var40,
				$var1['key515' . $var12] ?? '',
			);
		}

		$var1['key519'] = str_replace(
			$var39,
			$var40,
			$var1['key519'] ?? '',
		);

		if ($var1['key760'] === null || $var1['key756'] === null) {
			$var1['key551'] = null;
			$var1['key555'] = null;
		}
		$var1['key563'] = str_replace(
			[
				'{scCurrency}',
				'{class}',
			],
			[
				$var1['key766'],
				$var1['key787'],
			],
			$var1['key563'] ?? '',
		);

		$var1['key541'] = str_replace(
			$var39,
			$var40,
			$var1['key541'] ?? '',
		);

		$var1['key603'] = str_replace(
			$var39,
			$var40,
			$var1['key603'] ?? '',
		);

		$var1['key607'] = str_replace(
			$var39,
			$var40,
			$var1['key607'] ?? '',
		);

		$var1['key611'] = str_replace(
			$var39,
			$var40,
			$var1['key611'] ?? '',
		);

		$var1['key615'] = str_replace(
			$var39,
			$var40,
			$var1['key615'] ?? '',
		);

		$var1['key619'] = str_replace(
			$var39,
			$var40,
			$var1['key619'] ?? '',
		);

		$var1['key623'] = str_replace(
			$var39,
			$var40,
			$var1['key623'] ?? '',
		);

		$var1['key559'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
				'{class}',
			],
			[
				$var1['key766'],
				$var1['key767'],
				$var1['key787'],
			],
			$var1['key559'] ?? '',
		);
		$var1['key319'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
			],
			[
				$var1['key766'],
				$var1['key767'],
			],
			$var1['key319'] ?? '',
		);
		$var1['key137'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
			],
			[
				$var1['key766'],
				$var1['key767'],
			],
			$var1['key137'] ?? '',
		);
		if (
			$var1['key4'] === null
			&& (str_contains($var1['key277'] ?? '', '{srri}'))
		) {
			$var1['key277'] = null;
		} else {
			$var1['key277'] = str_replace(
				'{srri}',
				$var1['key4'] ?? '',
				$var1['key277'] ?? '',
			);
		}
		if (
			$var1['key4'] === null
			&& (str_contains($var1['key274'] ?? '', '{srri}'))
		) {
			$var1['key274'] = null;
		} else {
			$var1['key274'] = str_replace(
				'{srri}',
				$var1['key4'] ?? '',
				$var1['key274'] ?? '',
			);
		}
		if (
			$var1['key4'] === null
			&& (str_contains($var1['key355'] ?? '', '{srri}'))
		) {
			$var1['key355'] = null;
		} else {
			$var1['key355'] = str_replace(
				'{srri}',
				$var1['key4'] ?? '',
				$var1['key355'] ?? '',
			);
		}
		if (
			$var1['key4'] === null
			&& (str_contains($var1['key358'] ?? '', '{srri}'))
		) {
			$var1['key358'] = null;
		} else {
			$var1['key358'] = str_replace(
				'{srri}',
				$var1['key4'] ?? '',
				$var1['key358'] ?? '',
			);
		}
		if (
			$var1['key4'] === null
			&& (str_contains($var1['key361'] ?? '', '{srri}'))
		) {
			$var1['key361'] = null;
		} else {
			$var1['key361'] = str_replace(
				'{srri}',
				$var1['key4'] ?? '',
				$var1['key361'] ?? '',
			);
		}
		$var1['key373'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key373'] ?? '',
		);
		$var1['key377'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key377'] ?? '',
		);
		$var1['key381'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key381'] ?? '',
		);
		$var1['key385'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key385'] ?? '',
		);
		$var1['key389'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key389'] ?? '',
		);
		$var1['key393'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key393'] ?? '',
		);
		$var1['key397'] = str_replace(
			'{srri}',
			$var1['key4'] ?? '',
			$var1['key397'] ?? '',
		);

		unset($var39);

		$var39['key107'] = [
			'{benchmark}',
			'{subfundName}',
			'{scCurrency}',
			'{sfCurrency}',
		];

		$var39['key147']
			= $var39['key151']
			= $var39['key155']
			= $var39['key159']
			= $var39['key163']
			= $var39['key167']
			= $var39['key171'] = ['{benchmark}'];

		$var39['key113']
			= $var39['key105']
			= $var39['key109']
			= $var39['key111']
			= $var39['key128']
			= $var39['key130']
			= $var39['key133']
			= $var39['key135']
			= $var39['key137']
			= $var39['key140']
			= $var39['key143']
			= $var39['key175']
			= $var39['key179']
			= $var39['key183']
			= $var39['key237']
			= $var39['key241']
			= $var39['key245']
			= $var39['key249']
			= $var39['key253']
			= $var39['key257']
			= $var39['key261']
			= $var39['key186']
			= $var39['key188']
			= $var39['key190']
			= $var39['key192']
			= $var39['key194']
			= $var39['key196']
			= $var39['key198']
			= [
			'{benchmark}',
			'{scCurrency}',
			'{sfCurrency}',
		];

		$var39['key123'] = [
			'{benchmark}',
			'{scCurrency}',
			'{sfCurrency}',
			'{subfundName}',
		];

		$var39['key867'] = [
			'{benchmark}',
			'{scCurrency}',
			'{sfCurrency}',
			'{class}',
		];
		$var39['key125']
			= $var39['key855'] = [
			'{benchmark}',
			'{scCurrency}',
			'{sfCurrency}',
			'{hedgeType}',
		];

		unset($var40);
		$var40['key107'] = [
			$var1['key523'],
			$var1['key784'],
			$var1['key766'],
			$var1['key767'],
		];
		$var40['key113']
			= $var40['key105']
			= $var40['key109']
			= $var40['key111']
			= $var40['key128']
			= $var40['key130']
			= $var40['key133']
			= $var40['key135']
			= $var40['key137']
			= $var40['key140']
			= $var40['key143']
			= $var40['key175']
			= $var40['key179']
			= $var40['key183']
			= $var40['key237']
			= $var40['key241']
			= $var40['key245']
			= $var40['key249']
			= $var40['key253']
			= $var40['key257']
			= $var40['key261']
			= $var40['key186']
			= $var40['key188']
			= $var40['key190']
			= $var40['key192']
			= $var40['key194']
			= $var40['key196']
			= $var40['key198']
			= [
			$var1['key523'],
			$var1['key766'],
			$var1['key767'],
		];

		$var40['key123'] = [
			$var1['key523'],
			$var1['key766'],
			$var1['key767'],
			$var1['key784'],
		];

		$var40['key867'] = [
			$var1['key523'],
			$var1['key766'],
			$var1['key767'],
			$var1['key787'],
		];

		$var40['key147']
			= $var40['key151']
			= $var40['key155']
			= $var40['key159']
			= $var40['key163']
			= $var40['key167']
			= $var40['key171'] = [
			$var1['key523'],
		];

		$var40['key125']
			= $var40['key855'] = [
			$var1['key523'],
			$var1['key766'],
			$var1['key767'],
			$var28,
		];
		foreach ($var39 as $var41 => $var42) {
			for ($var12 = 2; $var12 <= $var11; $var12++) {
				$var39[$var41][] = "{benchmark$var12}";
				$var40[$var41][] = $var1['key523' . $var12];
			}
		}
		$var1['key107'] = str_replace(
			$var39['key107'],
			$var40['key107'],
			$var1['key107'] ?? '',
		);
		$var1['key113'] = str_replace(
			$var39['key113'],
			$var40['key113'],
			$var1['key113'] ?? '',
		);
		$var1['key867'] = str_replace(
			$var39['key867'],
			$var40['key867'],
			$var1['key867'] ?? '',
		);
		$var1['key123'] = str_replace(
			$var39['key123'],
			$var40['key123'],
			$var1['key123'] ?? '',
		);
		$var1['key125'] = str_replace(
			$var39['key125'],
			$var40['key125'],
			$var1['key125'] ?? '',
		);
		$var1['key855'] = str_replace(
			$var39['key855'],
			$var40['key855'],
			$var1['key855'] ?? '',
		);

		$var1['key105'] = str_replace(
			$var39['key105'],
			$var40['key105'],
			$var1['key105'] ?? '',
		);

		$var1['key147'] = str_replace(
			$var39['key147'],
			$var40['key147'],
			$var1['key147'] ?? '',
		);

		$var1['key151'] = str_replace(
			$var39['key151'],
			$var40['key151'],
			$var1['key151'] ?? '',
		);

		$var1['key155'] = str_replace(
			$var39['key155'],
			$var40['key155'],
			$var1['key155'] ?? '',
		);

		$var1['key159'] = str_replace(
			$var39['key159'],
			$var40['key159'],
			$var1['key159'] ?? '',
		);

		$var1['key163'] = str_replace(
			$var39['key163'],
			$var40['key163'],
			$var1['key163'] ?? '',
		);

		$var1['key167'] = str_replace(
			$var39['key167'],
			$var40['key167'],
			$var1['key167'] ?? '',
		);

		$var1['key171'] = str_replace(
			$var39['key171'],
			$var40['key171'],
			$var1['key171'] ?? '',
		);

		$var1['key111'] = str_replace(
			$var39['key111'],
			$var40['key111'],
			$var1['key111'] ?? '',
		);
		$var1['key135'] = str_replace(
			$var39['key135'],
			$var40['key135'],
			$var1['key135'] ?? '',
		);
		$var1['key130'] = str_replace(
			$var39['key130'],
			$var40['key130'],
			$var1['key130'] ?? '',
		);
		$var1['key128'] = str_replace(
			$var39['key128'],
			$var40['key128'],
			$var1['key128'] ?? '',
		);
		$var1['key133'] = str_replace(
			$var39['key133'],
			$var40['key133'],
			$var1['key133'] ?? '',
		);
		$var1['key137'] = str_replace(
			$var39['key137'],
			$var40['key137'],
			$var1['key137'] ?? '',
		);
		$var1['key140'] = str_replace(
			$var39['key140'],
			$var40['key140'],
			$var1['key140'] ?? '',
		);
		$var1['key143'] = str_replace(
			$var39['key143'],
			$var40['key143'],
			$var1['key143'] ?? '',
		);
		$var1['key109'] = str_replace(
			$var39['key109'],
			$var40['key109'],
			$var1['key109'] ?? '',
		);
		$var1['key186'] = str_replace(
			$var39['key186'],
			$var40['key186'],
			$var1['key186'] ?? '',
		);
		$var1['key188'] = str_replace(
			$var39['key188'],
			$var40['key188'],
			$var1['key188'] ?? '',
		);
		$var1['key190'] = str_replace(
			$var39['key190'],
			$var40['key190'],
			$var1['key190'] ?? '',
		);
		$var1['key192'] = str_replace(
			$var39['key192'],
			$var40['key192'],
			$var1['key192'] ?? '',
		);
		$var1['key194'] = str_replace(
			$var39['key194'],
			$var40['key194'],
			$var1['key194'] ?? '',
		);
		$var1['key196'] = str_replace(
			$var39['key196'],
			$var40['key196'],
			$var1['key196'] ?? '',
		);
		$var1['key198'] = str_replace(
			$var39['key198'],
			$var40['key198'],
			$var1['key198'] ?? '',
		);
		$var1['key237'] = str_replace(
			$var39['key237'],
			$var40['key237'],
			$var1['key237'] ?? '',
		);
		$var1['key241'] = str_replace(
			$var39['key241'],
			$var40['key241'],
			$var1['key241'] ?? '',
		);
		$var1['key245'] = str_replace(
			$var39['key245'],
			$var40['key245'],
			$var1['key245'] ?? '',
		);
		$var1['key249'] = str_replace(
			$var39['key249'],
			$var40['key249'],
			$var1['key249'] ?? '',
		);
		$var1['key253'] = str_replace(
			$var39['key253'],
			$var40['key253'],
			$var1['key253'] ?? '',
		);
		$var1['key257'] = str_replace(
			$var39['key257'],
			$var40['key257'],
			$var1['key257'] ?? '',
		);
		$var1['key261'] = str_replace(
			$var39['key261'],
			$var40['key261'],
			$var1['key261'] ?? '',
		);
		$var1['key175'] = str_replace(
			$var39['key175'],
			$var40['key175'],
			$var1['key175'] ?? '',
		);
		$var1['key179'] = str_replace(
			$var39['key179'],
			$var40['key179'],
			$var1['key179'] ?? '',
		);
		$var1['key183'] = str_replace(
			$var39['key183'],
			$var40['key183'],
			$var1['key183'] ?? '',
		);

		$var1['key547'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
			],
			[
				$var1['key766'],
				$var1['key767'],
			],
			$var1['key547'] ?? '',
		);
		$var1['key537'] = str_replace(
			[
				'{scLaunchYear}',
				'{scLaunchMonthNumeric}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthNumeric}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key760'],
				$var1['key761'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key757'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key537'] ?? '',
		);
		$var1['key631'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
				'{scLaunchYear}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key766'],
				$var1['key767'],
				$var1['key760'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key631'] ?? '',
		);

		$var1['key635'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
				'{scLaunchYear}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key766'],
				$var1['key767'],
				$var1['key760'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key635'] ?? '',
		);
		$var1['key639'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
				'{scLaunchYear}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key766'],
				$var1['key767'],
				$var1['key760'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key639'] ?? '',
		);
		$var1['key643'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
				'{scLaunchYear}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key766'],
				$var1['key767'],
				$var1['key760'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key643'] ?? '',
		);
		$var1['key647'] = str_replace(
			[
				'{scCurrency}',
				'{sfCurrency}',
				'{scLaunchYear}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key766'],
				$var1['key767'],
				$var1['key760'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key758'],
				$var1['key759'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key647'] ?? '',
		);
		$var1['key653'] = str_replace(
			[
				'{fundName}',
				'{subfundName}',
			],
			[
				$var1['key785'],
				$var1['key784'],
			],
			$var1['key653'] ?? '',
		);

		$var1['key674'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key674'] ?? '',
		);
		$var1['key681'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key681'] ?? '',
		);
		$var1['key726'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key726'] ?? '',
		);
		$var1['key730'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key730'] ?? '',
		);
		$var1['key734'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key734'] ?? '',
		);
		$var1['key738'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key738'] ?? '',
		);
		$var1['key742'] = str_replace(
			'{distributionType}',
			$var29 ?? '',
			$var1['key742'] ?? '',
		);

		$var1['key714'] = str_replace(
			[
				'{class}',
				'{distributionType}',
				'{fundName}',
				'{subfundName}',
			],
			[
				$var1['key787'],
				$var29,
				$var1['key785'],
				$var1['key784'],
			],
			$var1['key714'] ?? '',
		);
		$var1['key718'] = str_replace(
			[
				'{class}',
				'{distributionType}',
			],
			[
				$var1['key787'],
				$var29,
			],
			$var1['key718'] ?? '',
		);
		$var1['key722'] = str_replace(
			[
				'{class}',
				'{distributionType}',
			],
			[
				$var1['key787'],
				$var29,
			],
			$var1['key722'] ?? '',
		);

		$var1['key567'] = str_replace(
			[
				'{subfundName}',
				'{fundName}',
				'{ISIN}',
				'{class}',
				'{scCurrency}',
				'{hedgeType}',
				'{sedol}',
			],
			[
				$var1['key784'],
				$var1['key785'],
				$var1['key786'],
				$var1['key787'],
				$var1['key766'],
				$var28,
				$var1['key790'],
			],
			$var1['key567'] ?? '',
		);

//HACK FOR AIF
		$var1['key873'] = is_null($var1['key873'])
			? null
			: number_format(
				round((float) $var1['key873'], 2),
				2,
			);
		$var1['key874'] = is_null($var1['key874'])
			? null
			: number_format(
				round((float) $var1['key874'], 2),
				2,
			);
		$var1['key448'] = str_replace(
			[
				'{ManagementFee}',
				'{EffectiveManagementFee}',
			],
			[
				(string) $var1['key873'],
				(string) $var1['key874'],
			],
			$var1['key448'] ?? '',
		);
		unset($var1['key873'], $var1['key874']);
//END HACK FOR AIF

//End Tag replacement
//Conditional fields
//HEADER
		$var1['key875'] = $var1['key769'];
//RRP
		$var1['key876'] = trim(
			$var1['key274'] . ' '
			. $var1['key277'],
		);
//CF
		if ($var1['key753'] === null) {
			$var1['key877'] = null;
			$var6['key878'] = null;
		} elseif ($var1['key753'] == 0 && !is_null($var1['key453'])) {
			$var1['key877'] = $var1['key453'];
			$var6['key878'] = '0.00';
		} else {
			if (
				!is_null($var1['key458'])
				&& !empty($var1['key458'])
			) {
				$var1['key877'] = $var1['key458'];
			} else {
				$var1['key877'] = number_format(
						round((float) $var1['key753'], 2),
						2,
					) . '%';
			}
			$var6['key878'] = number_format(round((float) $var1['key753'], 2), 2);
		}
		if ($var1['key754'] === null) {
			$var1['key879'] = null;
			$var6['key880'] = null;
		} elseif ($var1['key754'] == 0 && !is_null($var1['key453'])) {
			$var1['key879'] = $var1['key453'];
			$var6['key880'] = '0.00';
		} else {
			if (
				!is_null($var1['key462'])
				&& !empty($var1['key462'])
			) {
				$var1['key879'] = $var1['key462'];
			} else {
				$var1['key879'] = number_format(
						round((float) $var1['key754'], 2),
						2,
					) . '%';
			}
			$var6['key880'] = number_format(round((float) $var1['key754'], 2), 2);
		}
		if ($var1['key752'] === null) {
			$var1['key881'] = null;
			$var6['key882'] = null;
		} elseif ($var1['key752'] == 0 && !is_null($var1['key453'])) {
			$var1['key881'] = $var1['key453'];
			$var6['key882'] = '0.00';
		} else {
			if (
				!is_null($var1['key466'])
				&& !empty($var1['key466'])
			) {
				$var1['key881'] = $var1['key466'];
			} else {
				$var1['key881'] = number_format(round($var1['key752'], 2), 2) . '%';
			}
			$var6['key882'] = number_format(round($var1['key752'], 2), 2);
		}
		if (!is_null($var1['key749']) && floatval($var1['key751']) != 0) {
			$var1['key883'] = $var30;
		} else {
			$var1['key883'] = null;
		}
		$var1['key407'] = is_null(
			$var1['key782'],
		) ? null : $var1['key407'];
		if (!is_null($var1['key749']) && floatval($var1['key751']) != 0) {
			$var1['key884'] = trim(
				$var1['key404'] . ' '
				. $var1['key407'],
			);
		} else {
			$var1['key884']
				= $var1['key409'];
		}
		if (
			!is_null($var1['key753']) && $var1['key753'] <> 0
			&& !is_null(
				$var1['key754'],
			)
			&& $var1['key754'] <> 0
		) {
			$var1['key412']
				= $var1['key412'];
		} elseif (
			!is_null($var1['key753']) && $var1['key753'] <> 0
			&& (is_null(
					$var1['key754'],
				)
				|| $var1['key754'] == 0)
		) {
			$var1['key412']
				= $var1['key416'];
		} elseif (
			(is_null($var1['key753']) || $var1['key753'] == 0)
			&& !is_null(
				$var1['key754'],
			)
			&& $var1['key754'] <> 0
		) {
			$var1['key412']
				= $var1['key420'];
		} else {
			$var1['key412']
				= $var1['key424'];
		}
		$var1['key436'] = (is_null($var1['key751'])
			|| floatval(
				$var1['key751'],
			) == 0) ? null : $var1['key436'];

		$var8['key431'] = $var1['key431'];
		$var8['key748'] = $var1['key748'];

		if ($var1['key431'] !== null && $var1['key748'] === null) {
			$var1['key428']
				= $var1['key433'] === null
				? $var1['key428']
				: $var1['key433'];
			$var1['key748'] = number_format(
					round((float) $var1['key431'], 2),
					2,
				) . '%';
			$var6['key885'] = number_format(
				round((float) $var1['key431'], 2),
				2,
			);
		} else {
			$var1['key428']
				= $var1['key428'];
			$var1['key748'] = ($var1['key748'] === null) ? null
				: number_format(round((float) $var1['key748'], 2), 2) . '%';
			$var6['key885'] = ($var1['key748'] === null) ? null
				: number_format(round((float) $var1['key748'], 2), 2);
		}
		if (
			$var1['key752'] !== null
			&& ($var1['key752'] != $var1['key753']
				|| $var1['key752'] != $var1['key754'])
		) {
			$var1['key451']
				= $var1['key451'];
			$var1['key455'] = $var1['key455'];
		} else {
			$var1['key451'] = null;
			$var1['key455'] = null;
			$var1['key881'] = null;
			$var6['key882'] = null;
		}
		if ($var1['key455'] !== null) {
			$var1['key451'] = null;
		}
//PP
		$var1['key515'] = ($var1['key61'] == 1)
			? $var1['key515'] : null;
		$var1['key519'] = ($var1['key518'] == 1)
			? $var1['key519'] : null;
		$var1['key502'] = (!is_null(
				$var1['key45'],
			)
			&& $var1['key45'] > 0)
			? $var1['key502'] : null;

		if (
			$var1['key58'] != 'key886'
			&& !empty($var1['key627'])
		) {
			$var1['key551']
				= $var1['key627'];
		} else {
			if ($var1['key760'] == $var1['key756']) {
				$var1['key551']
					= $var1['key551'];
			} else {
				$var1['key551']
					= $var1['key555'];
			}
		}

		$var1['key412'] = str_replace(
			[
				'{entryChargeValue}',
				'{exitChargeValue}',
				'{RedemptionFeeInFavourOfTheFund}',
				'{SubscriptionFeeInFavourOfTheFund}',
				'{EffectiveRedemptionFee}',
				'{EffectiveSubscriptionFee}',
			],
			[
				$var33,
				$var35,
				$var36,
				$var34,
				$var37,
				$var38,
			],
			$var1['key412'] ?? '',
		);

		$var1['key416'] = str_replace(
			[
				'{entryChargeValue}',
				'{exitChargeValue}',
				'{RedemptionFeeInFavourOfTheFund}',
				'{SubscriptionFeeInFavourOfTheFund}',
				'{EffectiveRedemptionFee}',
				'{EffectiveSubscriptionFee}',
			],
			[
				$var33,
				$var35,
				$var36,
				$var34,
				$var37,
				$var38,
			],
			$var1['key416'] ?? '',
		);

		$var1['key420'] = str_replace(
			[
				'{entryChargeValue}',
				'{exitChargeValue}',
				'{RedemptionFeeInFavourOfTheFund}',
				'{SubscriptionFeeInFavourOfTheFund}',
				'{EffectiveRedemptionFee}',
				'{EffectiveSubscriptionFee}',
			],
			[
				$var33,
				$var35,
				$var36,
				$var34,
				$var37,
				$var38,
			],
			$var1['key420'] ?? '',
		);

		$var1['key424'] = str_replace(
			[
				'{entryChargeValue}',
				'{exitChargeValue}',
				'{RedemptionFeeInFavourOfTheFund}',
				'{SubscriptionFeeInFavourOfTheFund}',
				'{EffectiveRedemptionFee}',
				'{EffectiveSubscriptionFee}',
			],
			[
				$var33,
				$var35,
				$var36,
				$var34,
				$var37,
				$var38,
			],
			$var1['key424'] ?? '',
		);

		$var1['key551'] = str_replace(
			[
				'{scLaunchYear}',
				'{scLaunchMonthNumeric}',
				'{scLaunchMonthTextual}',
				'{scLaunchDay}',
				'{sfLaunchYear}',
				'{sfLaunchMonthNumeric}',
				'{sfLaunchMonthTextual}',
				'{sfLaunchDay}',
				'{class}',
				'{scReLaunchYear}',
				'{scReLaunchDay}',
				'{scReLaunchMonthNumeric}',
				'{scReLaunchMonthTextual}',
			],
			[
				$var1['key760'],
				$var1['key761'],
				$var1['key762'],
				$var1['key763'],
				$var1['key756'],
				$var1['key757'],
				$var1['key758'],
				$var1['key759'],
				$var1['key787'],
				$var18 ?? '',
				$var22 ?? '',
				$var19 ?? '',
				$var21 ?? '',
			],
			$var1['key551'] ?? '',
		);

		if (
			$var1['key766'] <> $var1['key767']
			&& !empty($var1['key559'])
		) {
			$var1['key563']
				= $var1['key559'];
		}


		unset($var1['key72'], $var1['key518']);

		$var43 = true;
		if (!empty($var27)) {
			$var1['key68'] = unserialize($var27);
			$var44 = [];
			for ($var12 = 2; $var12 <= $var11; $var12++) {
				$var44 = array_merge(
					$var44,
					array_column(
						$var1['key68'],
						'key887' . $var12,
					),
				);
			}
			$var45 = array_merge(
				array_column($var1['key68'], 'key888'),
				array_column($var1['key68'], 'key887'),
				$var44,
			);
			foreach ($var45 as $var46) {
				if (is_numeric($var46)) {
					$var43 = false;
					break;
				}
			}
		}

		if ($var43 === false) {
			$var1['key527'] = null;

			if (!empty($var1['key69'])) {
				$var1['key69'] = unserialize(
					$var1['key69'],
				);

				if (
					!isset($var1['key69']['key889'])
					|| !isset($var1['key69']['key890'])
					|| !isset($var1['key69']['key590'])
				) {
					$var1['key578'] = null;
					$var1['key590'] = null;
				}

				if (
					!isset($var1['key69']['key891'])
					|| !isset($var1['key69']['key892'])
					|| !isset($var1['key69']['key593'])
				) {
					$var1['key581'] = null;
					$var1['key593'] = null;
				}

				if (
					!isset($var1['key69']['key893'])
					|| !isset($var1['key69']['key894'])
					|| !isset($var1['key69']['key596'])
				) {
					$var1['key584'] = null;
					$var1['key596'] = null;
				}

				if (
					!isset($var1['key69']['key895'])
					|| !isset($var1['key69']['key896'])
					|| !isset($var1['key69']['key599'])
				) {
					$var1['key587'] = null;
					$var1['key599'] = null;
				}
			}
		} else {
			$var1['key498'] = null;
			$var1['key544'] = null;
			$var1['key547'] = null;
			$var1['key563'] = null;
			$var1['key530'] = null;
			$var1['key533'] = null;
			$var1['key537'] = null;
			$var1['key541'] = null;
			$var1['key578'] = null;
			$var1['key581'] = null;
			$var1['key584'] = null;
			$var1['key587'] = null;
			$var1['key590'] = null;
			$var1['key593'] = null;
			$var1['key596'] = null;
			$var1['key599'] = null;
		}
		unset(
			$var1['key68'], $var1['key897'], $var1['key898'], $var1['key777'], $var1['key776'], $var1['key69'], $var1['key775'], $var1['key899'],
		);

//PI
		$var1['key671'] = is_null(
			$var1['key53'],
		) ? null : $var1['key671'];
//End Conditional fields
		$var1['key792'] = $var24->getDate($var1, true);
		$var47 = $var24->getTagReferenceDate($var1['key792']);

		if (!empty($var1['key750'])) {
			switch ($var1['key750']) {
				case 'key900':
					$var48 = '{year}-{monthNumeric}-{day}';
					break;
				case 'key901':
					$var48 = $var2['key902'] ?? null;
					break;
				case 'key903':
					$var48 = $var2['key904'] ?? null;
					break;
				case 'key905':
					$var48 = $var2['key906'] ?? null;
					break;
				default:
					throw new Exception('dateFormat "' . $var1['key750'] . '" doesn\'t exist.');
			}
			[
				$var49,
				$var50,
				$var51,
			] = explode(
				'-',
				$var1['key792'],
			);
			$var52 = date(
				'key755',
				strtotime(
					$var1['key792'],
				),
			);
			$var53 = $var2["MonthName{$var52}"] ?? null;
			$var47 = str_replace(
				[
					'{year}',
					'{monthNumeric}',
					'{monthTextual}',
					'{day}',
				],
				[
					$var49,
					$var50,
					$var53,
					$var51,
				],
				$var48,
			);
		}

		if (!is_null($var1['key428'])) {
			if (is_null($var1['key794'])) {
				$var1['key794'] = 0;
			}
			$var55 = time();
			[
				$var56,
				$var57,
			] = explode('-', date('Y-m-d', $var55));
			$var58 = date('key755', $var55);
			$var59 = $var2["MonthName{$var58}"] ?? null;

			$var1['key428'] = str_replace(
				[
					'{ongoingChargesYear}',
					'{ongoingChargesMonth}',
					'{ongoingChargesMonthTextual}',
				],
				[
					$var56,
					$var57,
					$var59,
				],
				$var1['key428'],
			);
			unset($var55, $var56, $var57, $var59);
		}

//This is necessary because str_replace convert null in ''
		$var60 = array_keys($var1);
		for ($var12 = 0, $var61 = \count($var60); $var12 < $var61; $var12++) {
			if ($var1[$var60[$var12]] === '') {
				$var1[$var60[$var12]] = null;
			}
		}
//META
		$var62 = implode(',', $var4->getEmailGroupOther('key908'));
		$var7['key909'] = 'Version=2.0; GeneratorContact=' . $var62 . '; DocumentType=KID; ';
		if (isset($var1['key9'])) {
			$var7['key909'] .= 'PublicationCountry=' . $var1['key9']
				. (($var1['key910'] == 'key513'
					&& $var1['key9'] == 'key441'
					&& $var1['key6'] != 'key441') ? '.QFI'
					: '') . '; ';
		}
		unset($var1['key910'], $var1['key6']);
		if (isset($var1['key10'])) {
			$var7['key909'] .= 'Language=' . $var1['key10'] . '; ';
		}
		$var7['key909'] .= 'RepShareClass=' . $var1['key786'] . '; RepShareClassCurrency='
			. $var1['key766'] . '; ShareClass=' . $var1['key786'];
		if (isset($var1['key789'])) {
			$var7['key909'] .= ',VALOR:' . $var1['key789'];
		}
		if (isset($var1['key788'])) {
			$var7['key909'] .= ',WKNDE:' . $var1['key788'];
		}
		$var7['key909'] .= '; ';
		if (isset($var1['key15']) && $var1['key15'] == 'key911') {
			$var7['key909'] .= 'DateOfPublication='
				. $var1['key792']
				. '; RecordDate='
				. $var1['key792']
				. '; ModificationDate='
				. $var1['key792']
				. '; ';
			if (
				(array_key_exists('key912', $var1)
					&& $var1['key912'] != 1)
				|| !array_key_exists('key912', $var1)
			) {
				$var7['key909'] .= 'DocumentUrl='
					. $var1['key786']
					. '/'
					. $var1['key10']
					. 'key913'
					. $var1['key9']
					. '; ';
			}
		}
		unset($var1['key912']);
		if (isset($var1['key4'])) {
			$var7['key909'] .= 'SRRI=' . $var1['key4'] . '; ';
		}
		$var7['key909'] .= 'PerformanceFee=' . $var30 . '%; ';
		if (isset($var1['key877'])) {
			$var7['key909'] .= 'EntryCharge=' . $var1['key877'] . '; ';
		}
		if (isset($var1['key879'])) {
			$var7['key909'] .= 'ExitCharge=' . $var1['key879'] . '; ';
		}
		if (isset($var1['key748'])) {
			$var7['key909'] .= 'OngoingCharges=' . $var1['key748'] . '; ';
		}
		$var7['key909'] .= 'ProductionDateTime=' . $var24->moteurData['key914'] . ';';
		if (isset($var1['key9'])) {
			$var7['key9'] = $var1['key9'];
			$var8['key9'] = $var1['key9'];
		}
		if (isset($var1['key10'])) {
			$var7['key10'] = $var1['key10'];
			$var8['key10'] = $var1['key10'];
		}
		if (isset($var1['key14'])) {
			$var7['key14'] = $var1['key14'];
			$var8['key14'] = $var1['key14'];
		}
		$var8['key746'] = $var1['key746'];
		$var8['key747'] = $var1['key747'];
//Data for bot
		if ($var3 == 'key2') {
			$var6['key67'] = $var1['key67'];
			$var6['key746'] = $var1['key746'];
			$var6['key747'] = $var1['key747'];
			$var6['key915'] = (!isset($var1['key7'])) ? null
				: $var1['key7'];
			$var6['key792'] = $var1['key792'];
			$var6['key16'] = $var1['key16'];
			$var6['key916'] = $var1['key786'];
			$var6['key917'] = $var1['key785'];
			$var6['key918'] = $var1['key784'];
			$var6['key919'] = $var1['key787'];
			$var6['key920'] = $var1['key766'];
			$var6['key921'] = $var1['key4'];
			if (!is_null($var1['key749']) && floatval($var1['key751']) != 0) {
				$var6['key922'] = $var31;
				$var6['key923'] = $var30;
			} else {
				$var6['key922'] = null;
				$var6['key923'] = null;
			}
			$var6['key924'] = $var7['key909'];
			$var6['key925'] = $var1['key745'];
			$var6['key926'] = $var1['key791'];
			$var6['key8']
				= isset($var1['key8'])
				? $var1['key8'] : null;

			$var63 = $var2;
//This assignment must be done before the replacement tag
			$var63['key927'] = $var2['key927'] ?? null;
		}

//Labels
		if (!is_null($var1['key928'])) {
			$var2['key929'] = $var1['key928'];
		} elseif (!is_null($var1['key930'])) {
			$var2['key929'] = $var1['key930'];
		}
		if (!is_null($var1['key931'])) {
			$var2['key932'] = $var1['key931'];
		} elseif (!is_null($var1['key933'])) {
			$var2['key932'] = $var1['key933'];
		}
		if (!is_null($var1['key934'])) {
			$var2['key935'] = $var1['key934'];
		} elseif (!is_null($var1['key936'])) {
			$var2['key935'] = $var1['key936'];
		}
		if (!is_null($var1['key937'])) {
			$var2['key938'] = $var1['key937'];
		} elseif (!is_null($var1['key939'])) {
			$var2['key938'] = $var1['key939'];
		}
		if (!is_null($var1['key940'])) {
			$var2['key941'] = $var1['key940'];
		} elseif (!is_null($var1['key942'])) {
			$var2['key941'] = $var1['key942'];
		}
		if (!is_null($var1['key943'])) {
			$var2['key944'] = $var1['key943'];
		} elseif (!is_null($var1['key945'])) {
			$var2['key944'] = $var1['key945'];
		}
		if (!is_null($var1['key946'])) {
			$var2['key947'] = $var1['key946'];
		} elseif (!is_null($var1['key948'])) {
			$var2['key947'] = $var1['key948'];
		}
		if (!is_null($var1['key949'])) {
			$var2['key950'] = $var1['key949'];
		} elseif (!is_null($var1['key951'])) {
			$var2['key950'] = $var1['key951'];
		}
		if (!is_null($var1['key952'])) {
			$var2['key953'] = $var1['key952'];
		} elseif (!is_null($var1['key954'])) {
			$var2['key953'] = $var1['key954'];
		}
		if (!is_null($var1['key955'])) {
			$var2['key956'] = $var1['key955'];
		} elseif (!is_null($var1['key957'])) {
			$var2['key956'] = $var1['key957'];
		}
		if (!is_null($var1['key958'])) {
			$var2['key959']
				= $var1['key958'];
		} elseif (!is_null($var1['key960'])) {
			$var2['key959']
				= $var1['key960'];
		}
		if (!is_null($var1['key961'])) {
			$var2['key962'] = $var1['key961'];
		} elseif (!is_null($var1['key963'])) {
			$var2['key962'] = $var1['key963'];
		}
		if (!is_null($var1['key964'])) {
			$var2['key748'] = $var1['key964'];
		} elseif (!is_null($var1['key965'])) {
			$var2['key748'] = $var1['key965'];
		}
		if (!is_null($var1['key966'])) {
			$var2['key967']
				= $var1['key966'];
		} elseif (!is_null($var1['key968'])) {
			$var2['key967']
				= $var1['key968'];
		}
		if (!is_null($var1['key969'])) {
			$var2['key883'] = $var1['key969'];
		} elseif (!is_null($var1['key970'])) {
			$var2['key883'] = $var1['key970'];
		}
		if (!is_null($var1['key971'])) {
			$var2['key927']
				= $var1['key971'];
		} elseif (!is_null($var1['key972'])) {
			$var2['key927']
				= $var1['key972'];
		}
		if (!empty($var2['key927'])) {
			$var2['key927'] = str_replace(
				'{publicationDate}',
				$var47,
				$var2['key927'],
			);
		}

		if (!empty($var1['key494'])) {
			if ($var3 == 'key2')//HACK for add ongoincharges in checksum
			{
				$var63['key748'] = str_replace(
					'{ongoingCharges}',
					$var1['key748'],
					$var1['key494'],
				);
			}
			$var1['key748'] = $var1['key494'];
		} elseif (!empty($var1['key748'])) {
			if ($var3 == 'key2')//HACK for add ongoincharges in checksum
			{
				$var63['key748'] = $var1['key748'];
			}
			$var1['key748'] = '{ongoingCharges}';
		}

//convert string to numeric for template
		$var1['key751'] += 0;

//delete unused data
		unset(
			$var1['key67'], $var1['key746'], $var1['key9'], $var1['key10'], $var1['key14'], $var1['key15'], $var1['key7'], $var1['key749'], $var1['key973'], $var1['key974'], $var1['key975'], $var1['key976'], $var1['key977'], $var1['key978'], $var1['key979'], $var1['key980'], $var1['key981'], $var1['key982'], $var1['key783'], $var1['key431'], $var1['key782'], $var1['key753'], $var1['key754'], $var1['key870'], $var1['key869'], $var1['key871'], $var1['key872'], $var1['key752'], $var1['key791'], $var1['key745'], $var1['key45'], $var1['key756'], $var1['key757'], $var1['key758'], $var1['key759'], $var1['key760'], $var1['key764'], $var1['key765'], $var1['key761'], $var1['key762'], $var1['key763'], $var1['key766'], $var1['key767'], $var1['key785'], $var1['key784'], $var1['key523'], $var1['key53'], $var1['key787'], $var1['key788'], $var1['key789'], $var1['key790'], $var1['key8'], $var1['key792'], $var1['key793'], $var1['key794'], $var1['key16'], $var1['key769'], $var1['key200'], $var1['key203'], $var1['key206'], $var1['key209'], $var1['key212'], $var1['key215'], $var1['key218'], $var1['key221'], $var1['key224'], $var1['key227'], $var1['key230'], $var1['key233'], $var1['key494'], $var1['key750'], $var1['key116'], $var1['key120'], $var1['key147'], $var1['key151'], $var1['key155'], $var1['key159'], $var1['key163'], $var1['key167'], $var1['key171'], $var1['key274'], $var1['key277'], $var1['key279'], $var1['key281'], $var1['key283'], $var1['key285'], $var1['key287'], $var1['key289'], $var1['key291'], $var1['key453'], $var1['key404'], $var1['key407'], $var1['key409'], $var1['key416'], $var1['key420'], $var1['key424'], $var1['key433'], $var1['key555'], $var1['key559'], $var1['key627'], $var1['key458'], $var1['key462'], $var1['key466'], $var1['key795'], $var1['key797'], $var1['key799'], $var1['key801'], $var1['key803'], $var1['key805'], $var1['key807'], $var1['key809'], $var1['key811'], $var1['key813'], $var1['key815'], $var1['key817'], $var1['key819'], $var1['key821'], $var1['key930'], $var1['key928'], $var1['key933'], $var1['key931'], $var1['key936'], $var1['key934'], $var1['key939'], $var1['key937'], $var1['key942'], $var1['key940'], $var1['key945'], $var1['key943'], $var1['key948'], $var1['key946'], $var1['key951'], $var1['key949'], $var1['key954'], $var1['key952'], $var1['key957'], $var1['key955'], $var1['key960'], $var1['key958'], $var1['key963'], $var1['key961'], $var1['key965'], $var1['key964'], $var1['key968'], $var1['key966'], $var1['key970'], $var1['key969'], $var1['key972'], $var1['key971'], $var1['key42'], $var1['key58'], $var1['key983'], $var2['key902'], $var2['key904'], $var2['key906'], $var2['key984'], $var2['key985'], $var2['key986'], $var2['key987'], $var2['key988'], $var2['key989'], $var2['key990'], $var2['key991'], $var2['key992'], $var2['key993'], $var2['key994'], $var2['key995'],
		);
//End Final array construction

		foreach ($var1 as &$var64) {
			if ($var64 !== null && trim($var64) === '{empty}') {
				$var64 = null;
			}
		}

		$var65 = [
			'key996' => [
				'key74'  => $var1['key74'],
				'key78'  => $var1['key78'],
				'key82'  => $var1['key82'],
				'key86'  => $var1['key86'],
				'key90'  => $var1['key90'],
				'key875' => $var1['key875'],
			],
			'key997' => [
				'key94'  => $var1['key94'],
				'key98'  => $var1['key98'],
				'key102' => $var1['key102'],
				'key778' => $var1['key778'],
				'key780' => $var1['key780'],
			],
			'key929' => [
				'key105' => $var1['key105'],
				'key107' => $var1['key107'],
				'key109' => $var1['key109'],
				'key111' => $var1['key111'],
				'key113' => $var1['key113'],
				'key867' => $var1['key867'],
				'key123' => $var1['key123'],
				'key125' => $var1['key125'],
				'key128' => $var1['key128'],
				'key130' => $var1['key130'],
				'key133' => $var1['key133'],
				'key135' => $var1['key135'],
				'key137' => $var1['key137'],
				'key140' => $var1['key140'],
				'key143' => $var1['key143'],
				'key175' => $var1['key175'],
				'key179' => $var1['key179'],
				'key183' => $var1['key183'],
				'key855' => $var1['key855'],
				'key237' => $var1['key237'],
				'key241' => $var1['key241'],
				'key245' => $var1['key245'],
				'key249' => $var1['key249'],
				'key253' => $var1['key253'],
				'key257' => $var1['key257'],
				'key261' => $var1['key261'],
				'key186' => $var1['key186'],
				'key188' => $var1['key188'],
				'key190' => $var1['key190'],
				'key192' => $var1['key192'],
				'key194' => $var1['key194'],
				'key196' => $var1['key196'],
				'key198' => $var1['key198'],
			],
			'key932' => [
				'key4'   => $var1['key4'],
				'key265' => $var1['key265'],
				'key268' => $var1['key268'],
				'key270' => $var1['key270'],
				'key272' => $var1['key272'],
				'key876' => $var1['key876'],
				'key293' => $var1['key293'],
				'key297' => $var1['key297'],
				'key322' => $var1['key322'],
				'key325' => $var1['key325'],
				'key328' => $var1['key328'],
				'key331' => $var1['key331'],
				'key334' => $var1['key334'],
				'key337' => $var1['key337'],
				'key340' => $var1['key340'],
				'key343' => $var1['key343'],
				'key346' => $var1['key346'],
				'key349' => $var1['key349'],
				'key300' => $var1['key300'],
				'key352' => $var1['key352'],
				'key355' => $var1['key355'],
				'key358' => $var1['key358'],
				'key361' => $var1['key361'],
				'key302' => $var1['key302'],
				'key304' => $var1['key304'],
				'key306' => $var1['key306'],
				'key308' => $var1['key308'],
				'key310' => $var1['key310'],
				'key313' => $var1['key313'],
				'key316' => $var1['key316'],
				'key319' => $var1['key319'],
				'key370' => $var1['key370'],
				'key366' => $var1['key366'],
				'key368' => $var1['key368'],
				'key364' => $var1['key364'],
				'key373' => $var1['key373'],
				'key377' => $var1['key377'],
				'key381' => $var1['key381'],
				'key385' => $var1['key385'],
				'key389' => $var1['key389'],
				'key393' => $var1['key393'],
				'key397' => $var1['key397'],
			],
			'key935' => [
				'key881' => $var1['key881'],
				'key748' => $var1['key748'],
				'key879' => $var1['key879'],
				'key877' => $var1['key877'],
				'key883' => $var1['key883'],
				'key401' => $var1['key401'],
				'key884' => $var1['key884'],
				'key412' => $var1['key412'],
				'key428' => $var1['key428'],
				'key436' => $var1['key436'],
				'key438' => $var1['key438'],
				'key443' => $var1['key443'],
				'key448' => $var1['key448'],
				'key451' => $var1['key451'],
				'key455' => $var1['key455'],
				'key470' => $var1['key470'],
				'key474' => $var1['key474'],
				'key478' => $var1['key478'],
				'key482' => $var1['key482'],
				'key486' => $var1['key486'],
				'key490' => $var1['key490'],
			],
			'key938' => [
				'key774' => $var1['key774'],
				'key590' => $var1['key590'],
				'key593' => $var1['key593'],
				'key596' => $var1['key596'],
				'key599' => $var1['key599'],
				'key498' => $var1['key498'],
				'key506' => $var1['key506'],
				'key515' => $var1['key515'],
				'key510' => $var1['key510'],
				'key502' => $var1['key502'],
				'key527' => $var1['key527'],
				'key530' => $var1['key530'],
				'key533' => $var1['key533'],
				'key537' => $var1['key537'],
				'key541' => $var1['key541'],
				'key544' => $var1['key544'],
				'key547' => $var1['key547'],
				'key551' => $var1['key551'],
				'key563' => $var1['key563'],
				'key578' => $var1['key578'],
				'key581' => $var1['key581'],
				'key584' => $var1['key584'],
				'key587' => $var1['key587'],
				'key603' => $var1['key603'],
				'key607' => $var1['key607'],
				'key611' => $var1['key611'],
				'key615' => $var1['key615'],
				'key619' => $var1['key619'],
				'key623' => $var1['key623'],
				'key631' => $var1['key631'],
				'key635' => $var1['key635'],
				'key639' => $var1['key639'],
				'key643' => $var1['key643'],
				'key647' => $var1['key647'],
				'key567' => $var1['key567'],
				'key571' => $var1['key571'],
				'key575' => $var1['key575'],
			],
			'key941' => [
				'key684' => $var1['key684'],
				'key650' => $var1['key650'],
				'key659' => $var1['key659'],
				'key662' => $var1['key662'],
				'key665' => $var1['key665'],
				'key668' => $var1['key668'],
				'key671' => $var1['key671'],
				'key674' => $var1['key674'],
				'key681' => $var1['key681'],
				'key726' => $var1['key726'],
				'key730' => $var1['key730'],
				'key734' => $var1['key734'],
				'key738' => $var1['key738'],
				'key742' => $var1['key742'],
				'key714' => $var1['key714'],
				'key718' => $var1['key718'],
				'key722' => $var1['key722'],
				'key689' => $var1['key689'],
				'key692' => $var1['key692'],
				'key695' => $var1['key695'],
				'key698' => $var1['key698'],
				'key701' => $var1['key701'],
				'key704' => $var1['key704'],
				'key707' => $var1['key707'],
				'key710' => $var1['key710'],
			],
			'key998' => [
				'key751' => $var1['key751'],
				'key786' => $var1['key786'],
				'key61'  => $var1['key61'],
			],
		];

		for ($var12 = 2; $var12 <= $var11; $var12++) {
			if (isset($var1['key515' . $var12])) {
				$var66['key515' . $var12]
					= $var1['key515' . $var12];
			} elseif (is_null($var1['key515' . $var12])) {
				unset($var1['key515' . $var12]);
			}
		}
		if (isset($var66)) {
			$var65['key938'] = array_merge(
				$var65['key938'],
				$var66,
			);
		}

		if (isset($var1['key999'])) {
			$var65['key938']['key999'] = $var1['key999'];
		}
		if (isset($var1['key519'])) {
			$var65['key938']['key519']
				= $var1['key519'];
		} else {
			unset($var1['key519']);
		}
		if (isset($var1['key1000'])) {
			$var65['key997']['key1000'] = $var1['key1000'];
		} else {
			unset($var1['key1000']);
		}
		if (isset($var1['key687'])) {
			$var65['key941']['key687']
				= $var1['key687'];
		} else {
			unset($var1['key687']);
		}
		if (isset($var1['key653'])) {
			$var65['key941']['key653']
				= $var1['key653'];
		} else {
			unset($var1['key653']);
		}
//HACK for structured template
		if ($var1['key63'] == 'Structured Products') {
			$var67 = new backend();
			$var68 = $var67->getStructuredNarrative($var1['key747']);

			if (!empty(array_intersect_key($var68['key1001'], $var1))) {
				throw new Exception('some keys are present in the 2 documentData arrays.');
			} elseif (
				!empty(
				array_intersect_key(
					$var68['key1002'],
					$var2,
				)
				)
			) {
				throw new Exception('some keys are present in the 2 templateLabels arrays.');
			} else {
				$var1 = array_merge($var1, $var68['key1001']);
				$var2 = array_merge($var2, $var68['key1002']);
				$var8['key1003'] = $var68['key1003'];
				$var65['key1004'] = $var68['key1001'];
				unset($var68);
			}
		}
		unset($var1['key63'], $var1['key747']);
//END HACK for structured template
		$var69 = [];
		foreach ($var65 as $var70 => $var71) {
			$var69 = array_merge($var69, $var71);
		}

		if ($var69 != $var1) {
			$var72 = array_diff_assoc($var69, $var1);
			$var73 = array_diff_assoc($var1, $var69);
			$var74 = '';
			if (!empty($var72)) {
				ob_start();
				print_r(filter_var_array($var72, FILTER_UNSAFE_RAW));
				$var74 .= "<br /><br />Difference between structured array and data:<br />"
					. ob_get_contents();
				ob_end_clean();
			}
			if (!empty($var73)) {
				ob_start();
				print_r(filter_var_array($var73, FILTER_UNSAFE_RAW));
				$var74 .= "<br /><br />Difference between data and structured array:<br />"
					. ob_get_contents();
				ob_end_clean();
			}
			throw new Exception(
				$var1['key786']
				. $var74,
			);
		}
	}

	if ($var3 == 'key2') {
		return [
			'key1005' => $var5,
			'key1006' => $var6,
			'key1001' => $var65 ?? null,
			'key1002' => $var2,
			'key1007' => $var63 ?? null,
			'key1008' => $var7,
			'key1009' => $var8,
			'key1010' => $var9,
		];
	} elseif ($var3 == 'key3') {
		return $var1;
	} else {
		return [
			'key1001' => $var65 ?? null,
			'key1002' => $var2,
			'key1008' => $var7,
			'key1009' => $var8,
		];
	}
}
