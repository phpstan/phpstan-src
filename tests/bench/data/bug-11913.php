<?php

namespace Bug11913;

/**
 * Class CountryPhonePrefixDictionary
 *
 */
class CountryPhonePrefixDictionary
{
	public static function getForISOCode(string $countryISOCode): ?string
	{
		$dict = self::getDictionary();

		foreach($dict as $country)
		{
			if($country['code'] === $countryISOCode)
			{
				return $country['d_code'];
			}
		}

		return null;
	}

	/** @return string[] */
	public static function getAll(): array
	{
		return array_column(self::getDictionary(), 'd_code');
	}

	/**
	 * https://sammaye.wordpress.com/2010/11/05/php-countries-and-their-call-codes-with-two-letter-abbreviations/
	 *
	 * @return mixed[]
	 */
	protected static function getDictionary()
	{
		$countries = array();
		$countries[] = array("code" => "AF", "name" => "Afghanistan", "d_code" => "0093");
		$countries[] = array("code" => "AL", "name" => "Albania", "d_code" => "00355");
		$countries[] = array("code" => "DZ", "name" => "Algeria", "d_code" => "00213");
		$countries[] = array("code" => "AS", "name" => "American Samoa", "d_code" => "001");
		$countries[] = array("code" => "AD", "name" => "Andorra", "d_code" => "00376");
		$countries[] = array("code" => "AO", "name" => "Angola", "d_code" => "00244");
		$countries[] = array("code" => "AI", "name" => "Anguilla", "d_code" => "001");
		$countries[] = array("code" => "AG", "name" => "Antigua", "d_code" => "001");
		$countries[] = array("code" => "AR", "name" => "Argentina", "d_code" => "0054");
		$countries[] = array("code" => "AM", "name" => "Armenia", "d_code" => "00374");
		$countries[] = array("code" => "AW", "name" => "Aruba", "d_code" => "00297");
		$countries[] = array("code" => "AU", "name" => "Australia", "d_code" => "0061");
		$countries[] = array("code" => "AT", "name" => "Austria", "d_code" => "0043");
		$countries[] = array("code" => "AZ", "name" => "Azerbaijan", "d_code" => "00994");
		$countries[] = array("code" => "BH", "name" => "Bahrain", "d_code" => "00973");
		$countries[] = array("code" => "BD", "name" => "Bangladesh", "d_code" => "00880");
		$countries[] = array("code" => "BB", "name" => "Barbados", "d_code" => "001");
		$countries[] = array("code" => "BY", "name" => "Belarus", "d_code" => "00375");
		$countries[] = array("code" => "BE", "name" => "Belgium", "d_code" => "0032");
		$countries[] = array("code" => "BZ", "name" => "Belize", "d_code" => "00501");
		$countries[] = array("code" => "BJ", "name" => "Benin", "d_code" => "00229");
		$countries[] = array("code" => "BM", "name" => "Bermuda", "d_code" => "001");
		$countries[] = array("code" => "BT", "name" => "Bhutan", "d_code" => "00975");
		$countries[] = array("code" => "BO", "name" => "Bolivia", "d_code" => "00591");
		$countries[] = array("code" => "BA", "name" => "Bosnia and Herzegovina", "d_code" => "00387");
		$countries[] = array("code" => "BW", "name" => "Botswana", "d_code" => "00267");
		$countries[] = array("code" => "BR", "name" => "Brazil", "d_code" => "0055");
		$countries[] = array("code" => "IO", "name" => "British Indian Ocean Territory", "d_code" => "00246");
		$countries[] = array("code" => "VG", "name" => "British Virgin Islands", "d_code" => "001");
		$countries[] = array("code" => "BN", "name" => "Brunei", "d_code" => "00673");
		$countries[] = array("code" => "BG", "name" => "Bulgaria", "d_code" => "00359");
		$countries[] = array("code" => "BF", "name" => "Burkina Faso", "d_code" => "00226");
		$countries[] = array("code" => "MM", "name" => "Burma Myanmar", "d_code" => "0095");
		$countries[] = array("code" => "BI", "name" => "Burundi", "d_code" => "00257");
		$countries[] = array("code" => "KH", "name" => "Cambodia", "d_code" => "00855");
		$countries[] = array("code" => "CM", "name" => "Cameroon", "d_code" => "00237");
		$countries[] = array("code" => "CA", "name" => "Canada", "d_code" => "001");
		$countries[] = array("code" => "CV", "name" => "Cape Verde", "d_code" => "00238");
		$countries[] = array("code" => "KY", "name" => "Cayman Islands", "d_code" => "001");
		$countries[] = array("code" => "CF", "name" => "Central African Republic", "d_code" => "00236");
		$countries[] = array("code" => "TD", "name" => "Chad", "d_code" => "00235");
		$countries[] = array("code" => "CL", "name" => "Chile", "d_code" => "0056");
		$countries[] = array("code" => "CN", "name" => "China", "d_code" => "0086");
		$countries[] = array("code" => "CO", "name" => "Colombia", "d_code" => "0057");
		$countries[] = array("code" => "KM", "name" => "Comoros", "d_code" => "00269");
		$countries[] = array("code" => "CK", "name" => "Cook Islands", "d_code" => "00682");
		$countries[] = array("code" => "CR", "name" => "Costa Rica", "d_code" => "00506");
		$countries[] = array("code" => "CI", "name" => "Côte d'Ivoire", "d_code" => "00225");
		$countries[] = array("code" => "HR", "name" => "Croatia", "d_code" => "00385");
		$countries[] = array("code" => "CU", "name" => "Cuba", "d_code" => "0053");
		$countries[] = array("code" => "CY", "name" => "Cyprus", "d_code" => "00357");
		$countries[] = array("code" => "CZ", "name" => "Czech Republic", "d_code" => "00420");
		$countries[] = array("code" => "CD", "name" => "Democratic Republic of Congo", "d_code" => "00243");
		$countries[] = array("code" => "DK", "name" => "Denmark", "d_code" => "0045");
		$countries[] = array("code" => "DJ", "name" => "Djibouti", "d_code" => "00253");
		$countries[] = array("code" => "DM", "name" => "Dominica", "d_code" => "001");
		$countries[] = array("code" => "DO", "name" => "Dominican Republic", "d_code" => "001");
		$countries[] = array("code" => "EC", "name" => "Ecuador", "d_code" => "00593");
		$countries[] = array("code" => "EG", "name" => "Egypt", "d_code" => "0020");
		$countries[] = array("code" => "SV", "name" => "El Salvador", "d_code" => "00503");
		$countries[] = array("code" => "GQ", "name" => "Equatorial Guinea", "d_code" => "00240");
		$countries[] = array("code" => "ER", "name" => "Eritrea", "d_code" => "00291");
		$countries[] = array("code" => "EE", "name" => "Estonia", "d_code" => "00372");
		$countries[] = array("code" => "ET", "name" => "Ethiopia", "d_code" => "00251");
		$countries[] = array("code" => "FK", "name" => "Falkland Islands", "d_code" => "00500");
		$countries[] = array("code" => "FO", "name" => "Faroe Islands", "d_code" => "00298");
		$countries[] = array("code" => "FM", "name" => "Federated States of Micronesia", "d_code" => "00691");
		$countries[] = array("code" => "FJ", "name" => "Fiji", "d_code" => "00679");
		$countries[] = array("code" => "FI", "name" => "Finland", "d_code" => "00358");
		$countries[] = array("code" => "FR", "name" => "France", "d_code" => "0033");
		$countries[] = array("code" => "GF", "name" => "French Guiana", "d_code" => "00594");
		$countries[] = array("code" => "PF", "name" => "French Polynesia", "d_code" => "00689");
		$countries[] = array("code" => "GA", "name" => "Gabon", "d_code" => "00241");
		$countries[] = array("code" => "GE", "name" => "Georgia", "d_code" => "00995");
		$countries[] = array("code" => "DE", "name" => "Germany", "d_code" => "0049");
		$countries[] = array("code" => "GH", "name" => "Ghana", "d_code" => "00233");
		$countries[] = array("code" => "GI", "name" => "Gibraltar", "d_code" => "00350");
		$countries[] = array("code" => "GR", "name" => "Greece", "d_code" => "0030");
		$countries[] = array("code" => "GL", "name" => "Greenland", "d_code" => "00299");
		$countries[] = array("code" => "GD", "name" => "Grenada", "d_code" => "001");
		$countries[] = array("code" => "GP", "name" => "Guadeloupe", "d_code" => "00590");
		$countries[] = array("code" => "GU", "name" => "Guam", "d_code" => "001");
		$countries[] = array("code" => "GT", "name" => "Guatemala", "d_code" => "00502");
		$countries[] = array("code" => "GN", "name" => "Guinea", "d_code" => "00224");
		$countries[] = array("code" => "GW", "name" => "Guinea-Bissau", "d_code" => "00245");
		$countries[] = array("code" => "GY", "name" => "Guyana", "d_code" => "00592");
		$countries[] = array("code" => "HT", "name" => "Haiti", "d_code" => "00509");
		$countries[] = array("code" => "HN", "name" => "Honduras", "d_code" => "00504");
		$countries[] = array("code" => "HK", "name" => "Hong Kong", "d_code" => "00852");
		$countries[] = array("code" => "HU", "name" => "Hungary", "d_code" => "0036");
		$countries[] = array("code" => "IS", "name" => "Iceland", "d_code" => "00354");
		$countries[] = array("code" => "IN", "name" => "India", "d_code" => "0091");
		$countries[] = array("code" => "ID", "name" => "Indonesia", "d_code" => "0062");
		$countries[] = array("code" => "IR", "name" => "Iran", "d_code" => "0098");
		$countries[] = array("code" => "IQ", "name" => "Iraq", "d_code" => "00964");
		$countries[] = array("code" => "IE", "name" => "Ireland", "d_code" => "00353");
		$countries[] = array("code" => "IL", "name" => "Israel", "d_code" => "00972");
		$countries[] = array("code" => "IT", "name" => "Italy", "d_code" => "0039");
		$countries[] = array("code" => "JM", "name" => "Jamaica", "d_code" => "001");
		$countries[] = array("code" => "JP", "name" => "Japan", "d_code" => "0081");
		$countries[] = array("code" => "JO", "name" => "Jordan", "d_code" => "00962");
		$countries[] = array("code" => "KZ", "name" => "Kazakhstan", "d_code" => "007");
		$countries[] = array("code" => "KE", "name" => "Kenya", "d_code" => "00254");
		$countries[] = array("code" => "KI", "name" => "Kiribati", "d_code" => "00686");
		$countries[] = array("code" => "XK", "name" => "Kosovo", "d_code" => "00381");
		$countries[] = array("code" => "KW", "name" => "Kuwait", "d_code" => "00965");
		$countries[] = array("code" => "KG", "name" => "Kyrgyzstan", "d_code" => "00996");
		$countries[] = array("code" => "LA", "name" => "Laos", "d_code" => "00856");
		$countries[] = array("code" => "LV", "name" => "Latvia", "d_code" => "00371");
		$countries[] = array("code" => "LB", "name" => "Lebanon", "d_code" => "00961");
		$countries[] = array("code" => "LS", "name" => "Lesotho", "d_code" => "00266");
		$countries[] = array("code" => "LR", "name" => "Liberia", "d_code" => "00231");
		$countries[] = array("code" => "LY", "name" => "Libya", "d_code" => "00218");
		$countries[] = array("code" => "LI", "name" => "Liechtenstein", "d_code" => "00423");
		$countries[] = array("code" => "LT", "name" => "Lithuania", "d_code" => "00370");
		$countries[] = array("code" => "LU", "name" => "Luxembourg", "d_code" => "00352");
		$countries[] = array("code" => "MO", "name" => "Macau", "d_code" => "00853");
		$countries[] = array("code" => "MK", "name" => "Macedonia", "d_code" => "00389");
		$countries[] = array("code" => "MG", "name" => "Madagascar", "d_code" => "00261");
		$countries[] = array("code" => "MW", "name" => "Malawi", "d_code" => "00265");
		$countries[] = array("code" => "MY", "name" => "Malaysia", "d_code" => "0060");
		$countries[] = array("code" => "MV", "name" => "Maldives", "d_code" => "00960");
		$countries[] = array("code" => "ML", "name" => "Mali", "d_code" => "00223");
		$countries[] = array("code" => "MT", "name" => "Malta", "d_code" => "00356");
		$countries[] = array("code" => "MH", "name" => "Marshall Islands", "d_code" => "00692");
		$countries[] = array("code" => "MQ", "name" => "Martinique", "d_code" => "00596");
		$countries[] = array("code" => "MR", "name" => "Mauritania", "d_code" => "00222");
		$countries[] = array("code" => "MU", "name" => "Mauritius", "d_code" => "00230");
		$countries[] = array("code" => "YT", "name" => "Mayotte", "d_code" => "00262");
		$countries[] = array("code" => "MX", "name" => "Mexico", "d_code" => "0052");
		$countries[] = array("code" => "MD", "name" => "Moldova", "d_code" => "00373");
		$countries[] = array("code" => "MC", "name" => "Monaco", "d_code" => "00377");
		$countries[] = array("code" => "MN", "name" => "Mongolia", "d_code" => "00976");
		$countries[] = array("code" => "ME", "name" => "Montenegro", "d_code" => "00382");
		$countries[] = array("code" => "MS", "name" => "Montserrat", "d_code" => "001");
		$countries[] = array("code" => "MA", "name" => "Morocco", "d_code" => "00212");
		$countries[] = array("code" => "MZ", "name" => "Mozambique", "d_code" => "00258");
		$countries[] = array("code" => "NA", "name" => "Namibia", "d_code" => "00264");
		$countries[] = array("code" => "NR", "name" => "Nauru", "d_code" => "00674");
		$countries[] = array("code" => "NP", "name" => "Nepal", "d_code" => "00977");
		$countries[] = array("code" => "NL", "name" => "Netherlands", "d_code" => "0031");
		$countries[] = array("code" => "AN", "name" => "Netherlands Antilles", "d_code" => "00599");
		$countries[] = array("code" => "NC", "name" => "New Caledonia", "d_code" => "00687");
		$countries[] = array("code" => "NZ", "name" => "New Zealand", "d_code" => "0064");
		$countries[] = array("code" => "NI", "name" => "Nicaragua", "d_code" => "00505");
		$countries[] = array("code" => "NE", "name" => "Niger", "d_code" => "00227");
		$countries[] = array("code" => "NG", "name" => "Nigeria", "d_code" => "00234");
		$countries[] = array("code" => "NU", "name" => "Niue", "d_code" => "00683");
		$countries[] = array("code" => "NF", "name" => "Norfolk Island", "d_code" => "00672");
		$countries[] = array("code" => "KP", "name" => "North Korea", "d_code" => "00850");
		$countries[] = array("code" => "MP", "name" => "Northern Mariana Islands", "d_code" => "001");
		$countries[] = array("code" => "NO", "name" => "Norway", "d_code" => "0047");
		$countries[] = array("code" => "OM", "name" => "Oman", "d_code" => "00968");
		$countries[] = array("code" => "PK", "name" => "Pakistan", "d_code" => "0092");
		$countries[] = array("code" => "PW", "name" => "Palau", "d_code" => "00680");
		$countries[] = array("code" => "PS", "name" => "Palestine", "d_code" => "00970");
		$countries[] = array("code" => "PA", "name" => "Panama", "d_code" => "00507");
		$countries[] = array("code" => "PG", "name" => "Papua New Guinea", "d_code" => "00675");
		$countries[] = array("code" => "PY", "name" => "Paraguay", "d_code" => "00595");
		$countries[] = array("code" => "PE", "name" => "Peru", "d_code" => "0051");
		$countries[] = array("code" => "PH", "name" => "Philippines", "d_code" => "0063");
		$countries[] = array("code" => "PL", "name" => "Poland", "d_code" => "0048");
		$countries[] = array("code" => "PT", "name" => "Portugal", "d_code" => "00351");
		$countries[] = array("code" => "PR", "name" => "Puerto Rico", "d_code" => "001");
		$countries[] = array("code" => "QA", "name" => "Qatar", "d_code" => "00974");
		$countries[] = array("code" => "CG", "name" => "Republic of the Congo", "d_code" => "00242");
		$countries[] = array("code" => "RE", "name" => "Réunion", "d_code" => "00262");
		$countries[] = array("code" => "RO", "name" => "Romania", "d_code" => "0040");
		$countries[] = array("code" => "RU", "name" => "Russia", "d_code" => "007");
		$countries[] = array("code" => "RW", "name" => "Rwanda", "d_code" => "00250");
		$countries[] = array("code" => "BL", "name" => "Saint Barthélemy", "d_code" => "00590");
		$countries[] = array("code" => "SH", "name" => "Saint Helena", "d_code" => "00290");
		$countries[] = array("code" => "KN", "name" => "Saint Kitts and Nevis", "d_code" => "001");
		$countries[] = array("code" => "MF", "name" => "Saint Martin", "d_code" => "00590");
		$countries[] = array("code" => "PM", "name" => "Saint Pierre and Miquelon", "d_code" => "00508");
		$countries[] = array("code" => "VC", "name" => "Saint Vincent and the Grenadines", "d_code" => "001");
		$countries[] = array("code" => "WS", "name" => "Samoa", "d_code" => "00685");
		$countries[] = array("code" => "SM", "name" => "San Marino", "d_code" => "00378");
		$countries[] = array("code" => "ST", "name" => "São Tomé and Príncipe", "d_code" => "00239");
		$countries[] = array("code" => "SA", "name" => "Saudi Arabia", "d_code" => "00966");
		$countries[] = array("code" => "SN", "name" => "Senegal", "d_code" => "00221");
		$countries[] = array("code" => "RS", "name" => "Serbia", "d_code" => "00381");
		$countries[] = array("code" => "SC", "name" => "Seychelles", "d_code" => "00248");
		$countries[] = array("code" => "SL", "name" => "Sierra Leone", "d_code" => "00232");
		$countries[] = array("code" => "SG", "name" => "Singapore", "d_code" => "0065");
		$countries[] = array("code" => "SK", "name" => "Slovakia", "d_code" => "00421");
		$countries[] = array("code" => "SI", "name" => "Slovenia", "d_code" => "00386");
		$countries[] = array("code" => "SB", "name" => "Solomon Islands", "d_code" => "00677");
		$countries[] = array("code" => "SO", "name" => "Somalia", "d_code" => "00252");
		$countries[] = array("code" => "ZA", "name" => "South Africa", "d_code" => "0027");
		$countries[] = array("code" => "KR", "name" => "South Korea", "d_code" => "0082");
		$countries[] = array("code" => "ES", "name" => "Spain", "d_code" => "0034");
		$countries[] = array("code" => "LK", "name" => "Sri Lanka", "d_code" => "0094");
		$countries[] = array("code" => "LC", "name" => "St. Lucia", "d_code" => "001");
		$countries[] = array("code" => "SD", "name" => "Sudan", "d_code" => "00249");
		$countries[] = array("code" => "SR", "name" => "Suriname", "d_code" => "00597");
		$countries[] = array("code" => "SZ", "name" => "Swaziland", "d_code" => "00268");
		$countries[] = array("code" => "SE", "name" => "Sweden", "d_code" => "0046");
		$countries[] = array("code" => "CH", "name" => "Switzerland", "d_code" => "0041");
		$countries[] = array("code" => "SY", "name" => "Syria", "d_code" => "00963");
		$countries[] = array("code" => "TW", "name" => "Taiwan", "d_code" => "00886");
		$countries[] = array("code" => "TJ", "name" => "Tajikistan", "d_code" => "00992");
		$countries[] = array("code" => "TZ", "name" => "Tanzania", "d_code" => "00255");
		$countries[] = array("code" => "TH", "name" => "Thailand", "d_code" => "0066");
		$countries[] = array("code" => "BS", "name" => "The Bahamas", "d_code" => "001");
		$countries[] = array("code" => "GM", "name" => "The Gambia", "d_code" => "00220");
		$countries[] = array("code" => "TL", "name" => "Timor-Leste", "d_code" => "00670");
		$countries[] = array("code" => "TG", "name" => "Togo", "d_code" => "00228");
		$countries[] = array("code" => "TK", "name" => "Tokelau", "d_code" => "00690");
		$countries[] = array("code" => "TO", "name" => "Tonga", "d_code" => "00676");
		$countries[] = array("code" => "TT", "name" => "Trinidad and Tobago", "d_code" => "001");
		$countries[] = array("code" => "TN", "name" => "Tunisia", "d_code" => "00216");
		$countries[] = array("code" => "TR", "name" => "Turkey", "d_code" => "0090");
		$countries[] = array("code" => "TM", "name" => "Turkmenistan", "d_code" => "00993");
		$countries[] = array("code" => "TC", "name" => "Turks and Caicos Islands", "d_code" => "001");
		$countries[] = array("code" => "TV", "name" => "Tuvalu", "d_code" => "00688");
		$countries[] = array("code" => "UG", "name" => "Uganda", "d_code" => "00256");
		$countries[] = array("code" => "UA", "name" => "Ukraine", "d_code" => "00380");
		$countries[] = array("code" => "AE", "name" => "United Arab Emirates", "d_code" => "00971");
		$countries[] = array("code" => "GB", "name" => "United Kingdom", "d_code" => "0044");
		$countries[] = array("code" => "US", "name" => "United States", "d_code" => "001");
		$countries[] = array("code" => "UY", "name" => "Uruguay", "d_code" => "00598");
		$countries[] = array("code" => "VI", "name" => "US Virgin Islands", "d_code" => "001");
		$countries[] = array("code" => "UZ", "name" => "Uzbekistan", "d_code" => "00998");
		$countries[] = array("code" => "VU", "name" => "Vanuatu", "d_code" => "00678");
		$countries[] = array("code" => "VA", "name" => "Vatican City", "d_code" => "0039");
		$countries[] = array("code" => "VE", "name" => "Venezuela", "d_code" => "0058");
		$countries[] = array("code" => "VN", "name" => "Vietnam", "d_code" => "0084");
		$countries[] = array("code" => "WF", "name" => "Wallis and Futuna", "d_code" => "00681");
		$countries[] = array("code" => "YE", "name" => "Yemen", "d_code" => "00967");
		$countries[] = array("code" => "ZM", "name" => "Zambia", "d_code" => "00260");
		$countries[] = array("code" => "ZW", "name" => "Zimbabwe", "d_code" => "00263");
		return $countries;
	}
}
