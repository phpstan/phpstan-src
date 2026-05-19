<?php

namespace Bug12800;

class a
{
	/**
	 * @param array<mixed> $labels
	 */
	public function b(array $labels, \stdClass $payment): bool
	{
		$fullData = '
        {
            "additionalData": {
                "acquirerAccountCode": "TestPmmAcquirerAccount",
                "authorisationMid": "1009",
                "cvcResult": "1 Matches",
                "avsResult": "4 AVS not supported for this card type",
                "authCode": "25595",
                "acquirerReference": "acquirerReference",
                "expiryDate": "8/2018",
                "avsResultRaw": "Y",
                "cvcResultRaw": "M",
                "refusalReasonRaw": "00 : Approved or completed successfully",
                "refusalCodeRaw": "00",
                "acquirerCode": "TestPmmAcquirer",
                "inferredRefusalReason": "3D Secure Mandated",
                "networkTxReference": "MCC123456789012",
                "cardHolderName": "Test Cardholder",
                "issuerCountry": "NL",
                "countryCode": "NL",
                "cardBin": "411111",
                "issuerBin": "41111101",
                "cardSchemeCommercial": "true",
                "cardPaymentMethod": "visa",
                "cardIssuingBank": "Bank of America",
                "cardIssuingCountry": "US",
                "cardIssuingCurrency": "USD",
                "fundingSource": "PREPAID_RELOADABLE",
                "cardSummary": "1111",
                "isCardCommercial": "true",
                "paymentMethodVariant": "visadebit",
                "paymentMethod": "visa",
                "coBrandedWith": "visa",
                "businessTypeIdentifier": "PP",
                "cardProductId": "P",
                "bankSummary": "1111",
                "bankAccount.ownerName": "A. Klaassen",
                "bankAccount.iban": "NL13TEST0123456789",
                "cavv": "AQIDBAUGBw",
                "xid": "ODgxNDc2MDg2",
                "cavvAlgorithm": "3",
                "eci": "02",
                "dsTransID": "f8062b92-66e9-4c5a-979a-f465e66a6e48",
                "threeDSVersion": "2.1.0",
                "threeDAuthenticatedResponse": "Y",
                "liabilityShift": "true",
                "threeDOffered": "true",
                "threeDAuthenticated": "false",
                "challengeCancel": "01",
                "fraudResultType": "FRAUD",
                "fraudManualReview": "false"
            },
            "fraudResult": {
                "accountScore": 10,
                "result": {
                    "fraudCheckResult": {
                        "accountScore": "10",
                        "checkId": "26",
                        "name": "ShopperEmailRefCheck"
                    }
                }
            },
         "response": "[cancelOrRefund-received]"
        }';

		$result = json_decode($fullData, true);

		$r = $labels['result_code'] === ''
			&& $labels['merchant_reference'] === $payment->merchant_reference
			&& $labels['brand_code'] === $payment->brand_code
			&& $labels['acquirer_account_code'] === $result['additionalData']['acquirerAccountCode']
			&& $labels['authorisation_mid'] === $result['additionalData']['authorisationMid']
			&& $labels['cvc_result'] === $result['additionalData']['cvcResult']
			&& $labels['auth_code'] === $result['additionalData']['authCode']
			&& $labels['acquirer_reference'] === $result['additionalData']['acquirerReference']
			&& $labels['expiry_date'] === $result['additionalData']['expiryDate']
			&& $labels['avs_result_raw'] === $result['additionalData']['avsResultRaw']
			&& $labels['cvc_result_raw'] === $result['additionalData']['cvcResultRaw']
			&& $labels['acquirer_code'] === $result['additionalData']['acquirerCode']
			&& $labels['inferred_refusal_reason'] === $result['additionalData']['inferredRefusalReason']
			&& $labels['network_tx_reference'] === $result['additionalData']['networkTxReference']
			&& $labels['issuer_country'] === $result['additionalData']['issuerCountry']
			&& $labels['country_code'] === $result['additionalData']['countryCode']
			&& $labels['card_bin'] === $result['additionalData']['cardBin']
			&& $labels['issuer_bin'] === $result['additionalData']['issuerBin']
			&& $labels['card_scheme_commercial'] === $result['additionalData']['cardSchemeCommercial']
			&& $labels['card_payment_method'] === $result['additionalData']['cardPaymentMethod']
			&& $labels['card_issuing_bank'] === $result['additionalData']['cardIssuingBank']
			&& $labels['card_issuing_country'] === $result['additionalData']['cardIssuingCountry']
			&& $labels['card_issuing_currency'] === $result['additionalData']['cardIssuingCurrency']
			&& $labels['card_summary'] === $result['additionalData']['cardSummary']
			&& $labels['payment_method_variant'] === $result['additionalData']['paymentMethodVariant']
			&& $labels['payment_method'] === $result['additionalData']['paymentMethod']
			&& $labels['co_branded_with'] === $result['additionalData']['coBrandedWith']
			&& $labels['business_type_identifier'] === $result['additionalData']['businessTypeIdentifier']
			&& $labels['card_product_id'] === $result['additionalData']['cardProductId']
			&& $labels['bank_summary'] === $result['additionalData']['bankSummary']
			&& $labels['cavv'] === $result['additionalData']['cavv']
			&& $labels['xid'] === $result['additionalData']['xid']
			&& $labels['cavv_algorithm'] === $result['additionalData']['cavvAlgorithm']
			&& $labels['eci'] === $result['additionalData']['eci']
			&& $labels['ds_trans_id'] === $result['additionalData']['dsTransID']
			&& $labels['liability_shift'] === $result['additionalData']['liabilityShift']
			&& $labels['fraud_result_type'] === $result['additionalData']['fraudResultType']
			&& $labels['fraud_manual_review'] === $result['additionalData']['fraudManualReview']
			&& $labels['fraud_result_account_score'] === $result['fraudResult']['accountScore']
			&& $labels['fraud_result_check_id'] === $result['fraudResult']['result']['fraudCheckResult']['checkId']
			&& $labels['fraud_result_name'] === $result['fraudResult']['result']['fraudCheckResult']['name']
			&& $labels['response'] === $result['response'];
		return $r;
	}
}
