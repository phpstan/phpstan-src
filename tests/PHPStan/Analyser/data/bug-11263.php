<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug11263;

enum FirstEnum: string
{

	case XyzSaturdayStopDomestic = 'XYZ_DOMESTIC_300';
	case XyzSaturdayDeliveryAir = 'XYZ_AIR_300';
	case XyzAdditionalHandling = 'XYZ_100';
	case XyzCommercialDomesticAirDeliveryArea = 'XYZ_COMMERCIAL_AIR_376';
	case XyzCommercialDomesticAirExtendedDeliveryArea = 'XYZ_COMMERCIAL_AIR_EXTENDED_376';
	case XyzCommercialDomesticGroundDeliveryArea = 'XYZ_COMMERCIAL_GROUND_376';
	case XyzCommercialDomesticGroundExtendedDeliveryArea = 'XYZ_COMMERCIAL_GROUND_EXTENDED_376';
	case XyzResidentialDomesticAirDeliveryArea = 'XYZ_RESIDENTIAL_AIR_376';
	case XyzResidentialDomesticAirExtendedDeliveryArea = 'XYZ_RESIDENTIAL_AIR_EXTENDED_376';
	case XyzResidentialDomesticGroundDeliveryArea = 'XYZ_RESIDENTIAL_GROUND_376';
	case XyzResidentialDomesticGroundExtendedDeliveryArea = 'XYZ_RESIDENTIAL_GROUND_EXTENDED_376';
	case XyzDeliveryAreaSurchargeSurePost = 'XYZ_SURE_POST_376';
	case XyzDeliveryAreaSurchargeSurePostExtended = 'XYZ_SURE_POST_EXTENDED_376';
	case XyzDeliveryAreaSurchargeOther = 'XYZ_DELIVERY_AREA_OTHER_376';
	case XyzResidentialSurchargeAir = 'XYZ_RESIDENTIAL_SURCHARGE_AIR_270';
	case XyzResidentialSurchargeGround = 'XYZ_RESIDENTIAL_SURCHARGE_GROUND_270';
	case XyzSurchargeCodeFuel = 'XYZ_375';
	case XyzCod = 'XYZ_COD_110';
	case XyzDeliveryConfirmation = 'XYZ_DELIVERY_CONFIRMATION_120';
	case XyzShipDeliveryConfirmation = 'XYZ_SHIP_DELIVERY_CONFIRMATION_121';
	case XyzExtendedArea = 'XYZ_EXTENDED_AREA_190';
	case XyzHazMat = 'XYZ_HAZ_MAT_199';
	case XyzDryIce = 'XYZ_DRY_ICE_200';
	case XyzIscSeeds = 'XYZ_ISC_SEEDS_201';
	case XyzIscPerishables = 'XYZ_ISC_PERISHABLES_202';
	case XyzIscTobacco = 'XYZ_ISC_TOBACCO_203';
	case XyzIscPlants = 'XYZ_ISC_PLANTS_204';
	case XyzIscAlcoholicBeverages = 'XYZ_ISC_ALCOHOLIC_BEVERAGES_205';
	case XyzIscBiologicalSubstances = 'XYZ_ISC_BIOLOGICAL_SUBSTANCES_206';
	case XyzIscSpecialExceptions = 'XYZ_ISC_SPECIAL_EXCEPTIONS_207';
	case XyzHoldForPickup = 'XYZ_HOLD_FOR_PICKUP_220';
	case XyzOriginCertificate = 'XYZ_ORIGIN_CERTIFICATE_240';
	case XyzPrintReturnLabel = 'XYZ_PRINT_RETURN_LABEL_250';
	case XyzExportLicenseVerification = 'XYZ_EXPORT_LICENSE_VERIFICATION_258';
	case XyzPrintNMail = 'XYZ_PRINT_N_MAIL_260';
	case XyzReturnService1attempt = 'XYZ_RETURN_SERVICE_1ATTEMPT_280';
	case XyzReturnService3attempt = 'XYZ_RETURN_SERVICE_3ATTEMPT_290';
	case XyzSaturdayInternationalProcessingFee = 'XYZ_SATURDAY_INTERNATIONAL_PROCESSING_FEE_310';
	case XyzElectronicReturnLabel = 'XYZ_ELECTRONIC_RETURN_LABEL_350';
	case XyzPreparedSedForm = 'XYZ_PREPARED_SED_FORM_374';
	case XyzLargePackage = 'XYZ_LARGE_PACKAGE_377';
	case XyzShipperPaysDutyTax = 'XYZ_SHIPPER_PAYS_DUTY_TAX_378';
	case XyzShipperPaysDutyTaxUnpaid = 'XYZ_SHIPPER_PAYS_DUTY_TAX_UNPAID_379';
	case XyzExpressPlusSurcharge = 'XYZ_EXPRESS_PLUS_SURCHARGE_380';
	case XyzInsurance = 'XYZ_INSURANCE_400';
	case XyzShipAdditionalHandling = 'XYZ_SHIP_ADDITIONAL_HANDLING_401';
	case XyzShipperRelease = 'XYZ_SHIPPER_RELEASE_402';
	case XyzCheckToShipper = 'XYZ_CHECK_TO_SHIPPER_403';
	case XyzProactiveResponse = 'XYZ_PROACTIVE_RESPONSE_404';
	case XyzGermanPickup = 'XYZ_GERMAN_PICKUP_405';
	case XyzGermanRoadTax = 'XYZ_GERMAN_ROAD_TAX_406';
	case XyzExtendedAreaPickup = 'XYZ_EXTENDED_AREA_PICKUP_407';
	case XyzReturnOfDocument = 'XYZ_RETURN_OF_DOCUMENT_410';
	case XyzPeakSeason = 'XYZ_PEAK_SEASON_430';
	case XyzLargePackageSeasonalSurcharge = 'XYZ_LARGE_PACKAGE_SEASONAL_SURCHARGE_431';
	case XyzAdditionalHandlingSeasonalSurchargeDiscontinued = 'XYZ_ADDITIONAL_HANDLING_SEASONAL_SURCHARGE_432';
	case XyzShipLargePackage = 'XYZ_SHIP_LARGE_PACKAGE_440';
	case XyzCarbonNeutral = 'XYZ_CARBON_NEUTRAL_441';
	case XyzImportControl = 'XYZ_IMPORT_CONTROL_444';
	case XyzCommercialInvoiceRemoval = 'XYZ_COMMERCIAL_INVOICE_REMOVAL_445';
	case XyzImportControlElectronicLabel = 'XYZ_IMPORT_CONTROL_ELECTRONIC_LABEL_446';
	case XyzImportControlPrintLabel = 'XYZ_IMPORT_CONTROL_PRINT_LABEL_447';
	case XyzImportControlPrintAndMailLabel = 'XYZ_IMPORT_CONTROL_PRINT_AND_MAIL_LABEL_448';
	case XyzImportControlOnePickupAttemptLabel = 'XYZ_IMPORT_CONTROL_ONE_PICKUP_ATTEMPT_LABEL_449';
	case XyzImportControlThreePickUpAttemptLabel = 'XYZ_IMPORT_CONTROL_THREE_PICK_UP_ATTEMPT_LABEL_450';
	case XyzRefrigeration = 'XYZ_REFRIGERATION_452';
	case XyzExchangePrintReturnLabel = 'XYZ_EXCHANGE_PRINT_RETURN_LABEL_464';
	case XyzCommittedDeliveryWindow = 'XYZ_COMMITTED_DELIVERY_WINDOW_470';
	case XyzSecuritySurcharge = 'XYZ_SECURITY_SURCHARGE_480';
	case XyzNonMachinableCharge = 'XYZ_NON_MACHINABLE_CHARGE_490';
	case XyzCustomerTransactionFee = 'XYZ_CUSTOMER_TRANSACTION_FEE_492';
	case XyzSurePostNonStandardLength = 'XYZ_493';
	case XyzSurePostNonStandardExtraLength = 'XYZ_494';
	case XyzSurePostNonStandardCube = 'XYZ_NON_STANDARD_CUBE_CHARGE_495';
	case XyzShipmentCod = 'XYZ_SHIPMENT_COD_500';
	case XyzLiftGateForPickup = 'XYZ_LIFT_GATE_FOR_PICKUP_510';
	case XyzLiftGateForDelivery = 'XYZ_LIFT_GATE_FOR_DELIVERY_511';
	case XyzDropOffAtXyzFacility = 'XYZ_DROP_OFF_AT_XYZ_FACILITY_512';
	case XyzPremiumCare = 'XYZ_PREMIUM_CARE_515';
	case XyzOversizePallet = 'XYZ_OVERSIZE_PALLET_520';
	case XyzFreightDeliverySurcharge = 'XYZ_FREIGHT_DELIVERY_SURCHARGE_530';
	case XyzFreightPickxyzurcharge = 'XYZ_FREIGHT_PICKUP_SURCHARGE_531';
	case XyzDirectToRetail = 'XYZ_DIRECT_TO_RETAIL_540';
	case XyzDirectDeliveryOnly = 'XYZ_DIRECT_DELIVERY_ONLY_541';
	case XyzNoAccessPoint = 'XYZ_NO_ACCESS_POINT_541';
	case XyzDeliverToAddresseeOnly = 'XYZ_DELIVER_TO_ADDRESSEE_ONLY_542';
	case XyzDirectToRetailCod = 'XYZ_DIRECT_TO_RETAIL_COD_543';
	case XyzRetailAccessPoint = 'XYZ_RETAIL_ACCESS_POINT_544';
	case XyzElectronicPackageReleaseAuthentication = 'XYZ_ELECTRONIC_PACKAGE_RELEASE_AUTHENTICATION_546';
	case XyzPayAtStore = 'XYZ_PAY_AT_STORE_547';
	case XyzInsideDelivery = 'XYZ_INSIDE_DELIVERY_549';
	case XyzItemDisposal = 'XYZ_ITEM_DISPOSAL_550';
	case XyzAddressCorrections = 'XYZ_ADDRESS_CORRECTIONS';
	case XyzNotPreviouslyBilledFee = 'XYZ_NOT_PREVIOUSLY_BILLED_FEE';
	case XyzPickxyzurcharge = 'XYZ_PICKUP_SURCHARGE';
	case XyzChargeback = 'XYZ_CHARGEBACK';
	case XyzAdditionalHandlingPeakDemand = 'XYZ_ADDITIONAL_HANDLING_PEAK_DEMAND';
	case XyzOtherSurcharge = 'XYZ_OTHER_SURCHARGE';

	case XyzRemoteAreaSurcharge = 'XYZ_REMOTE_AREA_SURCHARGE';
	case XyzRemoteAreaOtherSurcharge = 'XYZ_REMOTE_AREA_OTHER_SURCHARGE';

	case CompanyEconomyResidentialSurchargeLightweight = 'COMPANY_ECONOMY_RESIDENTIAL_SURCHARGE_LIGHTWEIGHT';
	case CompanyEconomyDeliverySurchargeLightweight = 'COMPANY_ECONOMY_DELIVERY_SURCHARGE_LIGHTWEIGHT';
	case CompanyEconomyExtendedDeliverySurchargeLightweight = 'COMPANY_ECONOMY_EXTENDED_DELIVERY_SURCHARGE_LIGHTWEIGHT';
	case CompanyStandardResidentialSurchargeLightweight = 'COMPANY_STANDARD_RESIDENTIAL_SURCHARGE_LIGHTWEIGHT';
	case CompanyStandardDeliverySurchargeLightweight = 'COMPANY_STANDARD_DELIVERY_SURCHARGE_LIGHTWEIGHT';
	case CompanyStandardExtendedDeliverySurchargeLightweight = 'COMPANY_STANDARD_EXTENDED_DELIVERY_SURCHARGE_LIGHTWEIGHT';
	case CompanyEconomyResidentialSurchargePlus = 'COMPANY_ECONOMY_RESIDENTIAL_SURCHARGE_PLUS';
	case CompanyEconomyDeliverySurchargePlus = 'COMPANY_ECONOMY_DELIVERY_SURCHARGE_PLUS';
	case CompanyEconomyExtendedDeliverySurchargePlus = 'COMPANY_ECONOMY_EXTENDED_DELIVERY_SURCHARGE_PLUS';
	case CompanyEconomyPeakSurchargeLightweight = 'COMPANY_ECONOMY_PEAK_SURCHARGE_LIGHTWEIGHT';
	case CompanyEconomyPeakSurchargePlus = 'COMPANY_ECONOMY_PEAK_SURCHARGE_PLUS';
	case CompanyEconomyPeakSurchargeOver5Lbs = 'COMPANY_ECONOMY_PEAK_SURCHARGE_OVER_5_LBS';
	case CompanyStandardResidentialSurchargePlus = 'COMPANY_STANDARD_RESIDENTIAL_SURCHARGE_PLUS';
	case CompanyStandardDeliverySurchargePlus = 'COMPANY_STANDARD_DELIVERY_SURCHARGE_PLUS';
	case CompanyStandardExtendedDeliverySurchargePlus = 'COMPANY_STANDARD_EXTENDED_DELIVERY_SURCHARGE_PLUS';
	case CompanyStandardPeakSurchargeLightweight = 'COMPANY_STANDARD_PEAK_SURCHARGE_LIGHTWEIGHT';
	case CompanyStandardPeakSurchargePlus = 'COMPANY_STANDARD_PEAK_SURCHARGE_PLUS';
	case CompanyStandardPeakSurchargeOver5Lbs = 'COMPANY_STANDARD_PEAK_SURCHARGE_OVER_5_LBS';

	case Company2DayResidentialSurcharge = 'COMPANY_2_DAY_RESIDENTIAL_SURCHARGE';
	case Company2DayDeliverySurcharge = 'COMPANY_2_DAY_DELIVERY_SURCHARGE';
	case Company2DayExtendedDeliverySurcharge = 'COMPANY_2_DAY_EXTENDED_DELIVERY_SURCHARGE';
	case Company2DayPeakSurcharge = 'COMPANY_2_DAY_PEAK_SURCHARGE';

	case CompanyHazmatResidentialSurcharge = 'COMPANY_HAZMAT_RESIDENTIAL_SURCHARGE';
	case CompanyHazmatResidentialSurchargeLightweight = 'COMPANY_HAZMAT_RESIDENTIAL_SURCHARGE_LIGHTWEIGHT';
	case CompanyHazmatDeliverySurcharge = 'COMPANY_HAZMAT_DELIVERY_SURCHARGE';
	case CompanyHazmatDeliverySurchargeLightweight = 'COMPANY_HAZMAT_DELIVERY_SURCHARGE_LIGHTWEIGHT';
	case CompanyHazmatExtendedDeliverySurcharge = 'COMPANY_HAZMAT_EXTENDED_DELIVERY_SURCHARGE';
	case CompanyHazmatExtendedDeliverySurchargeLightweight = 'COMPANY_HAZMAT_EXTENDED_DELIVERY_SURCHARGE_LIGHTWEIGHT';
	case CompanyHazmatPeakSurchargeLightweight = 'COMPANY_HAZMAT_PEAK_SURCHARGE_LIGHTWEIGHT';
	case CompanyHazmatPeakSurcharge = 'COMPANY_HAZMAT_PEAK_SURCHARGE';
	case CompanyHazmatPeakSurchargePlus = 'COMPANY_HAZMAT_PEAK_SURCHARGE_PLUS';
	case CompanyHazmatPeakSurchargeOver5Lbs = 'COMPANY_HAZMAT_PEAK_SURCHARGE_OVER_5_LBS';

	case CompanyFuelSurcharge = 'COMPANY_FUEL_SURCHARGE';

	case Company2DayAdditionalHandlingSurchargeDimensions = 'COMPANY_2DAY_ADDITIONAL_HANDLING_SURCHARGE_DIMENSIONS';
	case Company2DayAdditionalHandlingSurchargeWeight = 'COMPANY_2DAY_ADDITIONAL_HANDLING_SURCHARGE_WEIGHT';
	case CompanyStandardAdditionalHandlingSurchargeDimensions = 'COMPANY_STANDARD_ADDITIONAL_HANDLING_SURCHARGE_DIMENSIONS';
	case CompanyStandardAdditionalHandlingSurchargeWeight = 'COMPANY_STANDARD_ADDITIONAL_HANDLING_SURCHARGE_WEIGHT';
	case CompanyEconomyAdditionalHandlingSurchargeDimensions = 'COMPANY_ECONOMY_ADDITIONAL_HANDLING_SURCHARGE_DIMENSIONS';
	case CompanyEconomyAdditionalHandlingSurchargeWeight = 'COMPANY_ECONOMY_ADDITIONAL_HANDLING_SURCHARGE_WEIGHT';
	case CompanyHazmatAdditionalHandlingSurchargeDimensions = 'COMPANY_HAZMAT_ADDITIONAL_HANDLING_SURCHARGE_DIMENSIONS';
	case CompanyHazmatAdditionalHandlingSurchargeWeight = 'COMPANY_HAZMAT_ADDITIONAL_HANDLING_SURCHARGE_WEIGHT';
	case Company2DayLargePackageSurcharge = 'COMPANY_2DAY_LARGE_PACKAGE_SURCHARGE';
	case CompanyStandardLargePackageSurcharge = 'COMPANY_STANDARD_LARGE_PACKAGE_SURCHARGE';
	case CompanyEconomyLargePackageSurcharge = 'COMPANY_ECONOMY_LARGE_PACKAGE_SURCHARGE';
	case CompanyHazmatLargePackageSurcharge = 'COMPANY_HAZMAT_LARGE_PACKAGE_SURCHARGE';
	case CompanyLargePackagePeakSurcharge = 'COMPANY_LARGE_PACKAGE_PEAK_SURCHARGE';

	case CompanyUkSignatureSurcharge = 'COMPANY_UK_SIGNATURE_SURCHARGE';

	case AciFuelSurcharge = 'ACI_FUEL_SURCHARGE';
	case AciUnmanifestedSurcharge = 'ACI_UNMANIFESTED_SURCHARGE';
	case AciPeakSurcharge = 'ACI_PEAK_SURCHARGE';
	case AciOversizeSurcharge = 'ACI_OVERSIZE_SURCHARGE';
	case AciUltraUrbanSurcharge = 'ACI_ULTRA_URBAN_SURCHARGE';
	case AciNonStandardSurcharge = 'ACI_NON_STANDARD_SURCHARGE';

	case XyzmiFuelSurcharge = 'XYZMI_FUEL_SURCHARGE';
	case XyzmiNonStandardSurcharge = 'XYZMI_NON_STANDARD_SURCHARGE';

	case FooEcommerceFuelSurcharge = 'FOO_ECOMMERCE_FUEL_SURCHARGE';
	case FooEcommercePeakSurcharge = 'FOO_ECOMMERCE_PEAK_SURCHARGE';
	case FooEcommerceOversizeSurcharge = 'FOO_ECOMMERCE_OVERSIZE_SURCHARGE';
	case FooEcommerceFutureUseSurcharge = 'FOO_ECOMMERCE_FUTURE_USE_SURCHARGE';
	case FooEcommerceDimLengthSurcharge = 'FOO_ECOMMERCE_DIM_LENGTH_SURCHARGE';

	case HooFuelSurcharge = 'LASER_SHIP_FUEL_SURCHARGE';
	case HooResidentialSurcharge = 'LASER_SHIP_RESIDENTIAL_SURCHARGE';
	case HooDeliverySurcharge = 'LASER_SHIP_DELIVERY_SURCHARGE';
	case HooExtendedDeliverySurcharge = 'LASER_SHIP_EXTENDED_DELIVERY_SURCHARGE';

	case MooSmartPostFuelSurcharge = 'MOO_SMART_POST_FUEL_SURCHARGE';
	case MooSmartPostDeliverySurcharge = 'MOO_SMART_POST_DELIVERY_SURCHARGE';
	case MooSmartPostExtendedDeliverySurcharge = 'MOO_SMART_POST_EXTENDED_DELIVERY_SURCHARGE';
	case MooSmartPostNonMachinableSurcharge = 'MOO_SMART_POST_NON_MACHINABLE_SURCHARGE';

	case MooAdditionalHandlingDomesticDimensionSurcharge = 'MOO_ADDITIONAL_HANDLING_DOMESTIC_DIMENSION_SURCHARGE';
	case MooOversizeSurcharge = 'MOO_OVERSIZE_SURCHARGE';

	case MooAdditionalHandling = 'MOO_ADDITIONAL_HANDLING';
	case MooAdditionalHandlingChargeDimensions = 'MOO_ADDITIONAL_HANDLING_CHARGE_DIMENSIONS';
	case MooAdditionalHandlingChargePackage = 'MOO_ADDITIONAL_HANDLING_CHARGE_PACKAGE';
	case MooAdditionalHandlingChargeWeight = 'MOO_ADDITIONAL_HANDLING_CHARGE_WEIGHT';
	case MooAdditionalWeightCharge = 'MOO_ADDITIONAL_WEIGHT_CHARGE';
	case MooAdvancementFee = 'MOO_ADVANCEMENT_FEE';
	case MooAhsDimensions = 'MOO_AHS_DIMENSIONS';
	case MooAhsWeight = 'MOO_AHS_WEIGHT';
	case MooAlaskaOrHawaiiOrPuertoRicoPkupOrDel = 'MOO_ALASKA/HAWAII/PUERTO_RICO_PKUP/DEL';
	case MooAppointment = 'MOO_APPOINTMENT';
	case MooBox24X24X18DblWalledProductQuantity2 = 'MOO_BOX_24_X_24_X_18_DBL_WALLED_PRODUCT_QUANTITY_2';
	case MooBox28X28X28DblWalledProductQuantity3 = 'MOO_BOX_28_X_28_X_28_DBL_WALLED_PRODUCT_QUANTITY_3';
	case MooBoxMultiDepth22X22X22ProductQuantity7 = 'MOO_BOX_MULTI_DEPTH_22_X_22_X_22_PRODUCT_QUANTITY_7';
	case MooBrokerDocumentTransferFee = 'MOO_BROKER_DOCUMENT_TRANSFER_FEE';
	case MooCallTag = 'MOO_CALL_TAG';
	case MooCustomsOvertimeFee = 'MOO_CUSTOMS_OVERTIME_FEE';
	case MooDasAlaskaComm = 'MOO_DAS_ALASKA_COMM';
	case MooDasAlaskaResi = 'MOO_DAS_ALASKA_RESI';
	case MooDasComm = 'MOO_DAS_COMM';
	case MooDasExtendedComm = 'MOO_DAS_EXTENDED_COMM';
	case MooDasExtendedResi = 'MOO_DAS_EXTENDED_RESI';
	case MooDasHawaiiComm = 'MOO_DAS_HAWAII_COMM';
	case MooDasHawaiiResi = 'MOO_DAS_HAWAII_RESI';
	case MooDasRemoteComm = 'MOO_DAS_REMOTE_COMM';
	case MooDasRemoteResi = 'MOO_DAS_REMOTE_RESI';
	case MooDasResi = 'MOO_DAS_RESI';
	case MooDateCertain = 'MOO_DATE_CERTAIN';
	case MooDeclaredValue = 'MOO_DECLARED_VALUE';
	case MooDeclaredValueCharge = 'MOO_DECLARED_VALUE_CHARGE';
	case MooDeliveryAndReturns = 'MOO_DELIVERY_AND_RETURNS';
	case MooDeliveryAreaSurcharge = 'MOO_DELIVERY_AREA_SURCHARGE';
	case MooDeliveryAreaSurchargeAlaska = 'MOO_DELIVERY_AREA_SURCHARGE_ALASKA';
	case MooDeliveryAreaSurchargeExtended = 'MOO_DELIVERY_AREA_SURCHARGE_EXTENDED';
	case MooDeliveryAreaSurchargeHawaii = 'MOO_DELIVERY_AREA_SURCHARGE_HAWAII';
	case MooElectronicEntryForFormalEntry = 'MOO_ELECTRONIC_ENTRY_FOR_FORMAL_ENTRY';
	case MooEvening = 'MOO_EVENING';
	case MooExtendedDeliveryArea = 'MOO_EXTENDED_DELIVERY_AREA';
	case MooFoodAndDrugAdministrationClearance = 'MOO_FOOD_AND_DRUG_ADMINISTRATION_CLEARANCE';
	case MooFragileLarge20X20X12ProductQuantity2 = 'MOO_FRAGILE_LARGE_20_X_20_X_12_PRODUCT_QUANTITY_2';
	case MooFragileLarge23X17X12ProductQuantity1 = 'MOO_FRAGILE_LARGE_23_X_17_X_12_PRODUCT_QUANTITY_1';
	case MooFreeTradeZone = 'MOO_FREE_TRADE_ZONE';
	case MooFuelSurcharge = 'MOO_FUEL_SURCHARGE';
	case MooHandlingFee = 'MOO_HANDLING_FEE';
	case MooHoldForPickup = 'MOO_HOLD_FOR_PICKUP';
	case MooImportPermitsAndLicensesFee = 'MOO_IMPORT_PERMITS_AND_LICENSES_FEE';
	case MooPeakAhsCharge = 'MOO_PEAK_AHS_CHARGE';
	case MooResidential = 'MOO_RESIDENTIAL';
	case MooOversizeCharge = 'MOO_OVERSIZE_CHARGE';
	case MooPeakOversizeSurcharge = 'MOO_PEAK_OVERSIZE_CHARGE';
	case MooAdditionalVat = 'MOO_ADDITIONAL_VAT';
	case MooGstOnDisbOrAncillaryServiceFees = 'MOO_GST_ON_DISB_OR_ANCILLARY_SERVICE_FEES';
	case MooHstOnAdvOrAncillaryServiceFees = 'MOO_HST_ON_ADV_OR_ANCILLARY_SERVICE_FEES';
	case MooHstOnDisbOrAncillaryServiceFees = 'MOO_HST_ON_DISB_OR_ANCILLARY_SERVICE_FEES';
	case MooIndiaCgst = 'MOO_INDIA_CGST';
	case MooIndiaSgst = 'MOO_INDIA_SGST';
	case MooMooAdditionalVat = 'MOO_MOO_ADDITIONAL_VAT';
	case MooMooAdditionalDuty = 'MOO_MOO_ADDITIONAL_DUTY';
	case MooEgyptVatOnFreight = 'MOO_EGYPT_VAT_ON_FREIGHT';
	case MooDutyAndTaxAmendmentFee = 'MOO_DUTY_AND_TAX_AMENDMENT_FEE';
	case MooCustomsDuty = 'MOO_CUSTOMS_DUTY';
	case MooCstAdditionalDuty = 'MOO_CST_ADDITIONAL_DUTY';
	case MooChinaVatDutyOrTax = 'MOO_CHINA_VAT_DUTY_OR_TAX';
	case MooArgentinaExportDuty = 'MOO_ARGENTINA_EXPORT_DUTY';
	case MooAustraliaGst = 'MOO_AUSTRALIA_GST';
	case MooBhFreightVat = 'MOO_BH_FREIGHT_VAT';
	case MooCanadaGst = 'MOO_CANADA_GST';
	case MooCanadaHst = 'MOO_CANADA_HST';
	case MooCanadaHstNb = 'MOO_CANADA_HST_NB';
	case MooCanadaHstOn = 'MOO_CANADA_HST_ON';
	case MooGstSingapore = 'MOO_GST_SINGAPORE';
	case MooBritishColumbiaPst = 'MOO_BRITISH_COLUMBIA_PST';
	case MooDisbursementFee = 'MOO_DISBURSEMENT_FEE';
	case MooVatOnDisbursementFee = 'MOO_VAT_ON_DISBURSEMENT_FEE';
	case MooResidentialRuralZone = 'MOO_RESIDENTIAL_RURAL_ZONE';
	case MooOriginalVat = 'MOO_ORIGINAL_VAT';
	case MooMexicoIvaFreight = 'MOO_MEXICO_IVA_FREIGHT';
	case MooOtherGovernmentAgencyFee = 'MOO_OTHER_GOVERNMENT_AGENCY_FEE';
	case MooCustodyFee = 'MOO_CUSTODY_FEE';
	case MooProcessingFee = 'MOO_PROCESSING_FEE';
	case MooStorageFee = 'MOO_STORAGE_FEE';
	case MooIndividualFormalEntry = 'MOO_INDIVIDUAL_FORMAL_ENTRY';
	case MooRebillDuty = 'MOO_REBILL_DUTY';
	case MooClearanceEntryFee = 'MOO_CLEARANCE_ENTRY_FEE';
	case MooCustomsClearanceFee = 'MOO_CUSTOMS_CLEARANCE_FEE';
	case MooRebillVAT = 'MOO_REBILL_VAT';
	case MooIdVatOnAncillaries = 'MOO_ID_VAT_ON_ANCILLARIES';

	case MooPeakSurcharge = 'MOO_PEAK_CHARGE';
	case MooOutOfDeliveryAreaTier = 'MOO_OUT_OF_DELIVERY_AREA_TIER';
	case MooMerchandiseProcessingFee = 'MOO_MERCHANDISE_PROCESSING_FEE';
	case MooReturnOnCallSurcharge = 'MOO_RETURN_ON_CALL_SURCHARGE';
	case MooUnauthorizedOSSurcharge = 'MOO_UNAUTHORIZED_OS';
	case MooPeakUnauthCharge = 'MOO_PEAK_UNAUTH_CHARGE';
	case MooMissingAccountNumber = 'MOO_MISSING_ACCOUNT_NUMBER';
	case MooHazardousMaterial = 'MOO_HAZARDOUS_MATERIAL';
	case MooReturnPickupFee = 'MOO_RETURN_PICKUP_FEE';
	case MooPeakResiCharge = 'MOO_PEAK_RESI_CHARGE';
	case MooSalesTax = 'MOO_SALES_TAX';
	case MooOther = 'MOO_OTHER';

	case ZooFuelSurcharge = 'CANADA_POST_FUEL_SURCHARGE';

	case ZooGoodsAndServicesTaxSurcharge = 'CANADA_POST_GST_SURCHARGE';

	case ZooHarmonizedSalesTaxSurcharge = 'CANADA_POST_HST_SURCHARGE';

	case ZooProvincialSalesTaxSurcharge = 'CANADA_POST_PST_SURCHARGE';

	case ZooPackageRedirectionSurcharge = 'CANADA_POST_REDIRECTION_SURCHARGE';

	case ZooDeliveryConfirmationSurcharge = 'CANADA_POST_DELIVERY_CONFIRMATION';

	case ZooSignatureOptionSurcharge = 'CANADA_POST_SIGNATURE_OPTION_SURCHARGE';

	case ZooOnDemandPickxyzurcharge = 'CANADA_POST_ON_DEMAND_PICKUP';

	case ZooOutOfSpecSurcharge = 'CANADA_POST_OUT_OF_SPEC_SURCHARGE';

	case ZooAutoBillingSurcharge = 'CANADA_POST_AUTO_BILLING_SURCHARGE';

	case ZooOversizeNotPackagedSurcharge = 'CANADA_POST_OVERSIZE_NOT_PACKAGED_SURCHARGE';

	case EvriRelabellingSurcharge = 'EVRI_RELABELLING_SURCHARGE';

	case EvriNetworkUndeliveredSurcharge = 'EVRI_NETWORK_UNDELIVERED_SURCHARGE';

	case GooInvalidAddressCorrection = 'GOO_INVALID_ADDRESS_CORRECTION';
	case GooIncorrectAddressCorrection = 'GOO_INCORRECT_ADDRESS_CORRECTION';
	case GooGroupCZip = 'GOO_GROUP_CZIP';
	case GooDeliveryAreaSurcharge = 'GOO_DELIVERY_AREA_SURCHARGE';
	case GooExtendedDeliveryAreaSurcharge = 'GOO_EXTENDED_DELIVERY_AREA_SURCHARGE';
	case GooEnergySurcharge = 'GOO_ENERGY_SURCHARGE';
	case GooExtraPiece = 'GOO_EXTRA_PIECE';
	case GooResidentialCharge = 'GOO_RESIDENTIAL_CHARGE';
	case GooRelabelCharge = 'GOO_RELABEL_CHARGE';
	case GooWeekendPerPiece = 'GOO_WEEKEND_PER_PIECE';
	case GooExtraWeight = 'GOO_EXTRA_WEIGHT';
	case GooReturnBaseCharge = 'GOO_RETURN_BASE_CHARGE';
	case GooReturnExtraWeight = 'GOO_RETURN_EXTRA_WEIGHT';
	case GooPeakSurcharge = 'GOO_PEAK_SURCHARGE';
	case GooAdditionalHandling = 'GOO_ADDITIONAL_HANDLING';
	case GooVolumeRebate = 'GOO_VOLUME_REBATE';
	case GooOverMaxLimit = 'GOO_OVER_MAX_LIMIT';
	case GooRemoteDeliveryAreaSurcharge = 'GOO_REMOTE_DELIVERY_AREA_SURCHARGE';
	case GooOffHour = 'GOO_OFF_HOUR';
	case GooVolumeRebateBase = 'GOO_VOLUME_REBATE_BASE';
	case GooResidentialSignature = 'GOO_RESIDENTIAL_SIGNATURE';
	case GooAHDemandSurcharge = 'GOO_AHDEMAND_SURCHARGE';
	case GooOversizeDemandSurcharge = 'GOO_OVERSIZE_DEMAND_SURCHARGE';
	case GooAuditFee = 'GOO_AUDIT_FEE';
	case GooVolumeRebate2 = 'GOO_VOLUME_REBATE_2';
	case GooVolumeRebate3 = 'GOO_VOLUME_REBATE_3';
	case GooUnmappedSurcharge = 'GOO_UNMAPPED_SURCHARGE';

	case LooDeliveryAreaSurcharge = 'PITNEY_BOWES_DELIVERY_AREA_SURCHARGE';
	case LooFuelSurcharge = 'PITNEY_BOWES_FUEL_SURCHARGE';

	case FooExpressSaturdayDelivery = 'FOO_EXPRESS_SATURDAY_DELIVERY';
	case FooExpressElevatedRisk = 'FOO_EXPRESS_ELEVATED_RISK';
	case FooExpressEmergencySituation = 'FOO_EXPRESS_EMERGENCY_SITUATION';
	case FooExpressDutiesTaxesPaid = 'FOO_EXPRESS_DUTIES_TAXES_PAID';
	case FooExpressDutyTaxPaid = 'FOO_EXPRESS_DUTY_TAX_PAID';
	case FooExpressFuelSurcharge = 'FOO_EXPRESS_FUEL_SURCHARGE';
	case FooExpressShipmentValueProtection = 'FOO_EXPRESS_SHIPMENT_VALUE_PROTECTION';
	case FooExpressAddressCorrection = 'FOO_EXPRESS_ADDRESS_CORRECTION';
	case FooExpressNeutralDelivery = 'FOO_EXPRESS_NEUTRAL_DELIVERY';
	case FooExpressRemoteAreaPickup = 'FOO_EXPRESS_REMOTE_AREA_PICKUP';
	case FooExpressRemoteAreaDelivery = 'FOO_EXPRESS_REMOTE_AREA_DELIVERY';
	case FooExpressShipmentPreparation = 'FOO_EXPRESS_SHIPMENT_PREPARATION';
	case FooExpressStandardPickup = 'FOO_EXPRESS_STANDARD_PICKUP';
	case FooExpressNonStandardPickup = 'FOO_EXPRESS_NON_STANDARD_PICKUP';
	case FooExpressMonthlyPickxyzervice = 'FOO_EXPRESS_MONTHLY_PICKUP_SERVICE';
	case FooExpressResidentialAddress = 'FOO_EXPRESS_RESIDENTIAL_ADDRESS';
	case FooExpressResidentialDelivery = 'FOO_EXPRESS_RESIDENTIAL_DELIVERY';
	case FooExpressSingleClearance = 'FOO_EXPRESS_SINGLE_CLEARANCE';
	case FooExpressUnderBondGuarantee = 'FOO_EXPRESS_UNDER_BOND_GUARANTEE';
	case FooExpressFormalClearance = 'FOO_EXPRESS_FORMAL_CLEARANCE';
	case FooExpressNonRoutineEntry = 'FOO_EXPRESS_NON_ROUTINE_ENTRY';
	case FooExpressDisbursements = 'FOO_EXPRESS_DISBURSEMENTS';
	case FooExpressDutyTaxImporter = 'FOO_EXPRESS_DUTY_TAX_IMPORTER';
	case FooExpressDutyTaxProcessing = 'FOO_EXPRESS_DUTY_TAX_PROCESSING';
	case FooExpressMultilineEntry = 'FOO_EXPRESS_MULTILINE_ENTRY';
	case FooExpressOtherGovtAgcyBorderControls = 'FOO_EXPRESS_OTHER_GOVT_AGCY_BORDER_CONTROLS';
	case FooExpressPrintedInvoice = 'FOO_EXPRESS_PRINTED_INVOICE';
	case FooExpressObtainingPermitsLicenses = 'FOO_EXPRESS_OBTAINING_PERMITS_LICENSES';
	case FooExpressPermitsLicences = 'FOO_EXPRESS_PERMITS_LICENCES';
	case FooExpressBondedStorage = 'FOO_EXPRESS_BONDED_STORAGE';
	case FooExpressExportDeclaration = 'FOO_EXPRESS_EXPORT_DECLARATION';
	case FooExpressExporterValidation = 'FOO_EXPRESS_EXPORTER_VALIDATION';
	case FooExpressRestrictedDestination = 'FOO_EXPRESS_RESTRICTED_DESTINATION';
	case FooExpressAdditionalDuty = 'FOO_EXPRESS_ADDITIONAL_DUTY';
	case FooExpressImportExportTaxes = 'FOO_EXPRESS_IMPORT_EXPORT_TAXES';
	case FooExpressQuarantineInspection = 'FOO_EXPRESS_QUARANTINE_INSPECTION';
	case FooExpressMerchandiseProcessing = 'FOO_EXPRESS_MERCHANDISE_PROCESSING';
	case FooExpressMerchandiseProcess = 'FOO_EXPRESS_MERCHANDISE_PROCESS';
	case FooExpressImportPenalty = 'FOO_EXPRESS_IMPORT_PENALTY';
	case FooExpressTradeZoneProcess = 'FOO_EXPRESS_TRADE_ZONE_PROCESS';
	case FooExpressRegulatoryCharge = 'FOO_EXPRESS_REGULATORY_CHARGE';
	case FooExpressRegulatoryCharges = 'FOO_EXPRESS_REGULATORY_CHARGES';
	case FooExpressVatOnNonRevenueItem = 'FOO_EXPRESS_VAT_ON_NON_REVENUE_ITEM';
	case FooExpressExciseTax = 'FOO_EXPRESS_EXCISE_TAX';
	case FooExpressImportExportDuties = 'FOO_EXPRESS_IMPORT_EXPORT_DUTIES';
	case FooExpressOversizePieceDimension = 'FOO_EXPRESS_OVERSIZE_PIECE_DIMENSION';
	case FooExpressOversizePiece = 'FOO_EXPRESS_OVERSIZE_PIECE';
	case FooExpressNonStackablePallet = 'FOO_EXPRESS_NON_STACKABLE_PALLET';
	case FooExpressPremium900 = 'FOO_EXPRESS_PREMIUM_9_00';
	case FooExpressPremium1200 = 'FOO_EXPRESS_PREMIUM_12_00';
	case FooExpressOverweightPiece = 'FOO_EXPRESS_OVERWEIGHT_PIECE';
	case FooExpressCommercialGesture = 'FOO_EXPRESS_COMMERCIAL_GESTURE';

	case PassportTaxes = 'PASSPORT_TAXES';
	case PassportDuties = 'PASSPORT_DUTIES';
	case PassportClearanceFee = 'PASSPORT_CLEARANCE_FEE';

	case IooProvincialTax = 'IOO_PST';
	case IooGoodsAndServicesTax = 'IOO_GST';
	case IooHarmonizedTax = 'IOO_HST';
	case IooTaxes = 'IOO_TAXES';
	case IooDuties = 'IOO_DUTIES';

	case FooExpressEuFuel = 'FOO_EXPRESS_EU_FUEL';
	case FooExpressEuRemoteAreaDelivery = 'FOO_EXPRESS_EU_REMOTE_AREA_DELIVERY';
	case FooExpressEuOverWeight = 'FOO_EXPRESS_EU_OVER_WEIGHT';

}

enum SecondEnum: string
{

	case Duties = 'duties';
	case ProcessingFees = 'processing_fees';
	case Taxes = 'taxes';

	public function getLabel(): string
	{
		return match ($this) {
			self::Duties => 'duties',
			self::ProcessingFees => 'processing fees',
			self::Taxes => 'taxes',
		};
	}

	public static function fromFirstEnum(FirstEnum $FirstEnum): ?self
	{
		return match ($FirstEnum) {
			FirstEnum::FooExpressExciseTax,
			FirstEnum::FooExpressVatOnNonRevenueItem,
			FirstEnum::FooExpressImportExportTaxes,
			FirstEnum::IooTaxes,
			FirstEnum::IooProvincialTax,
			FirstEnum::IooHarmonizedTax,
			FirstEnum::IooGoodsAndServicesTax,
			FirstEnum::PassportTaxes => self::Taxes,
			FirstEnum::FooExpressRegulatoryCharges,
			FirstEnum::FooExpressRegulatoryCharge,
			FirstEnum::FooExpressTradeZoneProcess,
			FirstEnum::FooExpressImportPenalty,
			FirstEnum::FooExpressMerchandiseProcess,
			FirstEnum::FooExpressMerchandiseProcessing,
			FirstEnum::FooExpressQuarantineInspection,
			FirstEnum::FooExpressBondedStorage,
			FirstEnum::FooExpressPermitsLicences,
			FirstEnum::FooExpressObtainingPermitsLicenses,
			FirstEnum::FooExpressPrintedInvoice,
			FirstEnum::FooExpressOtherGovtAgcyBorderControls,
			FirstEnum::FooExpressMultilineEntry,
			FirstEnum::FooExpressDutyTaxProcessing,
			FirstEnum::FooExpressDutyTaxImporter,
			FirstEnum::FooExpressDisbursements,
			FirstEnum::FooExpressNonRoutineEntry,
			FirstEnum::FooExpressFormalClearance,
			FirstEnum::FooExpressUnderBondGuarantee,
			FirstEnum::FooExpressSingleClearance,
			FirstEnum::FooExpressDutyTaxPaid,
			FirstEnum::FooExpressDutiesTaxesPaid,
			FirstEnum::PassportClearanceFee => self::ProcessingFees,
			FirstEnum::FooExpressAdditionalDuty,
			FirstEnum::FooExpressImportExportDuties,
			FirstEnum::IooDuties,
			FirstEnum::PassportDuties => self::Duties,
			FirstEnum::XyzSaturdayStopDomestic,
			FirstEnum::XyzSaturdayDeliveryAir,
			FirstEnum::XyzAdditionalHandling,
			FirstEnum::XyzCommercialDomesticAirDeliveryArea,
			FirstEnum::XyzCommercialDomesticAirExtendedDeliveryArea,
			FirstEnum::XyzCommercialDomesticGroundDeliveryArea,
			FirstEnum::XyzCommercialDomesticGroundExtendedDeliveryArea,
			FirstEnum::XyzResidentialDomesticAirDeliveryArea,
			FirstEnum::XyzResidentialDomesticAirExtendedDeliveryArea,
			FirstEnum::XyzResidentialDomesticGroundDeliveryArea,
			FirstEnum::XyzResidentialDomesticGroundExtendedDeliveryArea,
			FirstEnum::XyzDeliveryAreaSurchargeSurePost,
			FirstEnum::XyzDeliveryAreaSurchargeSurePostExtended,
			FirstEnum::XyzDeliveryAreaSurchargeOther,
			FirstEnum::XyzResidentialSurchargeAir,
			FirstEnum::XyzResidentialSurchargeGround,
			FirstEnum::XyzSurchargeCodeFuel,
			FirstEnum::XyzCod,
			FirstEnum::XyzDeliveryConfirmation,
			FirstEnum::XyzShipDeliveryConfirmation,
			FirstEnum::XyzExtendedArea,
			FirstEnum::XyzHazMat,
			FirstEnum::XyzDryIce,
			FirstEnum::XyzIscSeeds,
			FirstEnum::XyzIscPerishables,
			FirstEnum::XyzIscTobacco,
			FirstEnum::XyzIscPlants,
			FirstEnum::XyzIscAlcoholicBeverages,
			FirstEnum::XyzIscBiologicalSubstances,
			FirstEnum::XyzIscSpecialExceptions,
			FirstEnum::XyzHoldForPickup,
			FirstEnum::XyzOriginCertificate,
			FirstEnum::XyzPrintReturnLabel,
			FirstEnum::XyzExportLicenseVerification,
			FirstEnum::XyzPrintNMail,
			FirstEnum::XyzReturnService1attempt,
			FirstEnum::XyzReturnService3attempt,
			FirstEnum::XyzSaturdayInternationalProcessingFee,
			FirstEnum::XyzElectronicReturnLabel,
			FirstEnum::XyzPreparedSedForm,
			FirstEnum::XyzLargePackage,
			FirstEnum::XyzShipperPaysDutyTax,
			FirstEnum::XyzShipperPaysDutyTaxUnpaid,
			FirstEnum::XyzExpressPlusSurcharge,
			FirstEnum::XyzInsurance,
			FirstEnum::XyzShipAdditionalHandling,
			FirstEnum::XyzShipperRelease,
			FirstEnum::XyzCheckToShipper,
			FirstEnum::XyzProactiveResponse,
			FirstEnum::XyzGermanPickup,
			FirstEnum::XyzGermanRoadTax,
			FirstEnum::XyzExtendedAreaPickup,
			FirstEnum::XyzReturnOfDocument,
			FirstEnum::XyzPeakSeason,
			FirstEnum::XyzLargePackageSeasonalSurcharge,
			FirstEnum::XyzAdditionalHandlingSeasonalSurchargeDiscontinued,
			FirstEnum::XyzShipLargePackage,
			FirstEnum::XyzCarbonNeutral,
			FirstEnum::XyzImportControl,
			FirstEnum::XyzCommercialInvoiceRemoval,
			FirstEnum::XyzImportControlElectronicLabel,
			FirstEnum::XyzImportControlPrintLabel,
			FirstEnum::XyzImportControlPrintAndMailLabel,
			FirstEnum::XyzImportControlOnePickupAttemptLabel,
			FirstEnum::XyzImportControlThreePickUpAttemptLabel,
			FirstEnum::XyzRefrigeration,
			FirstEnum::XyzExchangePrintReturnLabel,
			FirstEnum::XyzCommittedDeliveryWindow,
			FirstEnum::XyzSecuritySurcharge,
			FirstEnum::XyzNonMachinableCharge,
			FirstEnum::XyzCustomerTransactionFee,
			FirstEnum::XyzSurePostNonStandardLength,
			FirstEnum::XyzSurePostNonStandardExtraLength,
			FirstEnum::XyzSurePostNonStandardCube,
			FirstEnum::XyzShipmentCod,
			FirstEnum::XyzLiftGateForPickup,
			FirstEnum::XyzLiftGateForDelivery,
			FirstEnum::XyzDropOffAtXyzFacility,
			FirstEnum::XyzPremiumCare,
			FirstEnum::XyzOversizePallet,
			FirstEnum::XyzFreightDeliverySurcharge,
			FirstEnum::XyzFreightPickxyzurcharge,
			FirstEnum::XyzDirectToRetail,
			FirstEnum::XyzDirectDeliveryOnly,
			FirstEnum::XyzNoAccessPoint,
			FirstEnum::XyzDeliverToAddresseeOnly,
			FirstEnum::XyzDirectToRetailCod,
			FirstEnum::XyzRetailAccessPoint,
			FirstEnum::XyzElectronicPackageReleaseAuthentication,
			FirstEnum::XyzPayAtStore,
			FirstEnum::XyzInsideDelivery,
			FirstEnum::XyzItemDisposal,
			FirstEnum::XyzAddressCorrections,
			FirstEnum::XyzNotPreviouslyBilledFee,
			FirstEnum::XyzPickxyzurcharge,
			FirstEnum::XyzChargeback,
			FirstEnum::XyzAdditionalHandlingPeakDemand,
			FirstEnum::XyzOtherSurcharge,
			FirstEnum::XyzRemoteAreaSurcharge,
			FirstEnum::XyzRemoteAreaOtherSurcharge,
			FirstEnum::CompanyEconomyResidentialSurchargeLightweight,
			FirstEnum::CompanyEconomyDeliverySurchargeLightweight,
			FirstEnum::CompanyEconomyExtendedDeliverySurchargeLightweight,
			FirstEnum::CompanyStandardResidentialSurchargeLightweight,
			FirstEnum::CompanyStandardDeliverySurchargeLightweight,
			FirstEnum::CompanyStandardExtendedDeliverySurchargeLightweight,
			FirstEnum::CompanyEconomyResidentialSurchargePlus,
			FirstEnum::CompanyEconomyDeliverySurchargePlus,
			FirstEnum::CompanyEconomyExtendedDeliverySurchargePlus,
			FirstEnum::CompanyEconomyPeakSurchargeLightweight,
			FirstEnum::CompanyEconomyPeakSurchargePlus,
			FirstEnum::CompanyEconomyPeakSurchargeOver5Lbs,
			FirstEnum::CompanyStandardResidentialSurchargePlus,
			FirstEnum::CompanyStandardDeliverySurchargePlus,
			FirstEnum::CompanyStandardExtendedDeliverySurchargePlus,
			FirstEnum::CompanyStandardPeakSurchargeLightweight,
			FirstEnum::CompanyStandardPeakSurchargePlus,
			FirstEnum::CompanyStandardPeakSurchargeOver5Lbs,
			FirstEnum::Company2DayResidentialSurcharge,
			FirstEnum::Company2DayDeliverySurcharge,
			FirstEnum::Company2DayExtendedDeliverySurcharge,
			FirstEnum::Company2DayPeakSurcharge,
			FirstEnum::CompanyHazmatResidentialSurcharge,
			FirstEnum::CompanyHazmatResidentialSurchargeLightweight,
			FirstEnum::CompanyHazmatDeliverySurcharge,
			FirstEnum::CompanyHazmatDeliverySurchargeLightweight,
			FirstEnum::CompanyHazmatExtendedDeliverySurcharge,
			FirstEnum::CompanyHazmatExtendedDeliverySurchargeLightweight,
			FirstEnum::CompanyHazmatPeakSurchargeLightweight,
			FirstEnum::CompanyHazmatPeakSurcharge,
			FirstEnum::CompanyHazmatPeakSurchargePlus,
			FirstEnum::CompanyHazmatPeakSurchargeOver5Lbs,
			FirstEnum::CompanyFuelSurcharge,
			FirstEnum::Company2DayAdditionalHandlingSurchargeDimensions,
			FirstEnum::Company2DayAdditionalHandlingSurchargeWeight,
			FirstEnum::CompanyStandardAdditionalHandlingSurchargeDimensions,
			FirstEnum::CompanyStandardAdditionalHandlingSurchargeWeight,
			FirstEnum::CompanyEconomyAdditionalHandlingSurchargeDimensions,
			FirstEnum::CompanyEconomyAdditionalHandlingSurchargeWeight,
			FirstEnum::CompanyHazmatAdditionalHandlingSurchargeDimensions,
			FirstEnum::CompanyHazmatAdditionalHandlingSurchargeWeight,
			FirstEnum::Company2DayLargePackageSurcharge,
			FirstEnum::CompanyStandardLargePackageSurcharge,
			FirstEnum::CompanyEconomyLargePackageSurcharge,
			FirstEnum::CompanyHazmatLargePackageSurcharge,
			FirstEnum::CompanyLargePackagePeakSurcharge,
			FirstEnum::CompanyUkSignatureSurcharge,
			FirstEnum::AciFuelSurcharge,
			FirstEnum::AciUnmanifestedSurcharge,
			FirstEnum::AciPeakSurcharge,
			FirstEnum::AciOversizeSurcharge,
			FirstEnum::AciUltraUrbanSurcharge,
			FirstEnum::AciNonStandardSurcharge,
			FirstEnum::XyzmiFuelSurcharge,
			FirstEnum::XyzmiNonStandardSurcharge,
			FirstEnum::FooEcommerceFuelSurcharge,
			FirstEnum::FooEcommercePeakSurcharge,
			FirstEnum::FooEcommerceOversizeSurcharge,
			FirstEnum::FooEcommerceFutureUseSurcharge,
			FirstEnum::FooEcommerceDimLengthSurcharge,
			FirstEnum::HooFuelSurcharge,
			FirstEnum::HooResidentialSurcharge,
			FirstEnum::HooDeliverySurcharge,
			FirstEnum::HooExtendedDeliverySurcharge,
			FirstEnum::MooSmartPostFuelSurcharge,
			FirstEnum::MooSmartPostDeliverySurcharge,
			FirstEnum::MooSmartPostExtendedDeliverySurcharge,
			FirstEnum::MooSmartPostNonMachinableSurcharge,
			FirstEnum::MooAdditionalHandlingDomesticDimensionSurcharge,
			FirstEnum::MooOversizeSurcharge,
			FirstEnum::MooAdditionalHandling,
			FirstEnum::MooAdditionalHandlingChargeDimensions,
			FirstEnum::MooAdditionalHandlingChargePackage,
			FirstEnum::MooAdditionalHandlingChargeWeight,
			FirstEnum::MooAdditionalWeightCharge,
			FirstEnum::MooAdvancementFee,
			FirstEnum::MooAhsDimensions,
			FirstEnum::MooAhsWeight,
			FirstEnum::MooAlaskaOrHawaiiOrPuertoRicoPkupOrDel,
			FirstEnum::MooAppointment,
			FirstEnum::MooBox24X24X18DblWalledProductQuantity2,
			FirstEnum::MooBox28X28X28DblWalledProductQuantity3,
			FirstEnum::MooBoxMultiDepth22X22X22ProductQuantity7,
			FirstEnum::MooBrokerDocumentTransferFee,
			FirstEnum::MooCallTag,
			FirstEnum::MooCustomsOvertimeFee,
			FirstEnum::MooDasAlaskaComm,
			FirstEnum::MooDasAlaskaResi,
			FirstEnum::MooDasComm,
			FirstEnum::MooDasExtendedComm,
			FirstEnum::MooDasExtendedResi,
			FirstEnum::MooDasHawaiiComm,
			FirstEnum::MooDasHawaiiResi,
			FirstEnum::MooDasRemoteComm,
			FirstEnum::MooDasRemoteResi,
			FirstEnum::MooDasResi,
			FirstEnum::MooDateCertain,
			FirstEnum::MooDeclaredValue,
			FirstEnum::MooDeclaredValueCharge,
			FirstEnum::MooDeliveryAndReturns,
			FirstEnum::MooDeliveryAreaSurcharge,
			FirstEnum::MooDeliveryAreaSurchargeAlaska,
			FirstEnum::MooDeliveryAreaSurchargeExtended,
			FirstEnum::MooDeliveryAreaSurchargeHawaii,
			FirstEnum::MooElectronicEntryForFormalEntry,
			FirstEnum::MooEvening,
			FirstEnum::MooExtendedDeliveryArea,
			FirstEnum::MooFoodAndDrugAdministrationClearance,
			FirstEnum::MooFragileLarge20X20X12ProductQuantity2,
			FirstEnum::MooFragileLarge23X17X12ProductQuantity1,
			FirstEnum::MooFreeTradeZone,
			FirstEnum::MooFuelSurcharge,
			FirstEnum::MooHandlingFee,
			FirstEnum::MooHoldForPickup,
			FirstEnum::MooImportPermitsAndLicensesFee,
			FirstEnum::MooPeakAhsCharge,
			FirstEnum::MooResidential,
			FirstEnum::MooOversizeCharge,
			FirstEnum::MooPeakOversizeSurcharge,
			FirstEnum::MooAdditionalVat,
			FirstEnum::MooGstOnDisbOrAncillaryServiceFees,
			FirstEnum::MooHstOnAdvOrAncillaryServiceFees,
			FirstEnum::MooHstOnDisbOrAncillaryServiceFees,
			FirstEnum::MooIndiaCgst,
			FirstEnum::MooIndiaSgst,
			FirstEnum::MooMooAdditionalVat,
			FirstEnum::MooMooAdditionalDuty,
			FirstEnum::MooEgyptVatOnFreight,
			FirstEnum::MooDutyAndTaxAmendmentFee,
			FirstEnum::MooCustomsDuty,
			FirstEnum::MooCstAdditionalDuty,
			FirstEnum::MooChinaVatDutyOrTax,
			FirstEnum::MooArgentinaExportDuty,
			FirstEnum::MooAustraliaGst,
			FirstEnum::MooBhFreightVat,
			FirstEnum::MooCanadaGst,
			FirstEnum::MooCanadaHst,
			FirstEnum::MooCanadaHstNb,
			FirstEnum::MooCanadaHstOn,
			FirstEnum::MooGstSingapore,
			FirstEnum::MooBritishColumbiaPst,
			FirstEnum::MooDisbursementFee,
			FirstEnum::MooVatOnDisbursementFee,
			FirstEnum::MooResidentialRuralZone,
			FirstEnum::MooOriginalVat,
			FirstEnum::MooMexicoIvaFreight,
			FirstEnum::MooOtherGovernmentAgencyFee,
			FirstEnum::MooCustodyFee,
			FirstEnum::MooProcessingFee,
			FirstEnum::MooStorageFee,
			FirstEnum::MooIndividualFormalEntry,
			FirstEnum::MooRebillDuty,
			FirstEnum::MooClearanceEntryFee,
			FirstEnum::MooCustomsClearanceFee,
			FirstEnum::MooRebillVAT,
			FirstEnum::MooIdVatOnAncillaries,
			FirstEnum::MooPeakSurcharge,
			FirstEnum::MooOutOfDeliveryAreaTier,
			FirstEnum::MooMerchandiseProcessingFee,
			FirstEnum::MooReturnOnCallSurcharge,
			FirstEnum::MooUnauthorizedOSSurcharge,
			FirstEnum::MooPeakUnauthCharge,
			FirstEnum::MooMissingAccountNumber,
			FirstEnum::MooHazardousMaterial,
			FirstEnum::MooReturnPickupFee,
			FirstEnum::MooPeakResiCharge,
			FirstEnum::MooSalesTax,
			FirstEnum::MooOther,
			FirstEnum::ZooFuelSurcharge,
			FirstEnum::ZooGoodsAndServicesTaxSurcharge,
			FirstEnum::ZooHarmonizedSalesTaxSurcharge,
			FirstEnum::ZooProvincialSalesTaxSurcharge,
			FirstEnum::ZooPackageRedirectionSurcharge,
			FirstEnum::ZooDeliveryConfirmationSurcharge,
			FirstEnum::ZooSignatureOptionSurcharge,
			FirstEnum::ZooOnDemandPickxyzurcharge,
			FirstEnum::ZooOutOfSpecSurcharge,
			FirstEnum::ZooAutoBillingSurcharge,
			FirstEnum::ZooOversizeNotPackagedSurcharge,
			FirstEnum::EvriRelabellingSurcharge,
			FirstEnum::EvriNetworkUndeliveredSurcharge,
			FirstEnum::GooInvalidAddressCorrection,
			FirstEnum::GooIncorrectAddressCorrection,
			FirstEnum::GooGroupCZip,
			FirstEnum::GooDeliveryAreaSurcharge,
			FirstEnum::GooExtendedDeliveryAreaSurcharge,
			FirstEnum::GooEnergySurcharge,
			FirstEnum::GooExtraPiece,
			FirstEnum::GooResidentialCharge,
			FirstEnum::GooRelabelCharge,
			FirstEnum::GooWeekendPerPiece,
			FirstEnum::GooExtraWeight,
			FirstEnum::GooReturnBaseCharge,
			FirstEnum::GooReturnExtraWeight,
			FirstEnum::GooPeakSurcharge,
			FirstEnum::GooAdditionalHandling,
			FirstEnum::GooVolumeRebate,
			FirstEnum::GooOverMaxLimit,
			FirstEnum::GooRemoteDeliveryAreaSurcharge,
			FirstEnum::GooOffHour,
			FirstEnum::GooVolumeRebateBase,
			FirstEnum::GooResidentialSignature,
			FirstEnum::GooAHDemandSurcharge,
			FirstEnum::GooOversizeDemandSurcharge,
			FirstEnum::GooAuditFee,
			FirstEnum::GooVolumeRebate2,
			FirstEnum::GooVolumeRebate3,
			FirstEnum::GooUnmappedSurcharge,
			FirstEnum::LooDeliveryAreaSurcharge,
			FirstEnum::LooFuelSurcharge,
			FirstEnum::FooExpressSaturdayDelivery,
			FirstEnum::FooExpressElevatedRisk,
			FirstEnum::FooExpressEmergencySituation,
			FirstEnum::FooExpressFuelSurcharge,
			FirstEnum::FooExpressShipmentValueProtection,
			FirstEnum::FooExpressAddressCorrection,
			FirstEnum::FooExpressNeutralDelivery,
			FirstEnum::FooExpressRemoteAreaPickup,
			FirstEnum::FooExpressRemoteAreaDelivery,
			FirstEnum::FooExpressShipmentPreparation,
			FirstEnum::FooExpressStandardPickup,
			FirstEnum::FooExpressNonStandardPickup,
			FirstEnum::FooExpressMonthlyPickxyzervice,
			FirstEnum::FooExpressResidentialAddress,
			FirstEnum::FooExpressResidentialDelivery,
			FirstEnum::FooExpressExportDeclaration,
			FirstEnum::FooExpressExporterValidation,
			FirstEnum::FooExpressRestrictedDestination,
			FirstEnum::FooExpressOversizePieceDimension,
			FirstEnum::FooExpressOversizePiece,
			FirstEnum::FooExpressNonStackablePallet,
			FirstEnum::FooExpressPremium900,
			FirstEnum::FooExpressPremium1200,
			FirstEnum::FooExpressOverweightPiece,
			FirstEnum::FooExpressEuFuel,
			FirstEnum::FooExpressEuOverWeight,
			FirstEnum::FooExpressEuRemoteAreaDelivery,
			FirstEnum::FooExpressCommercialGesture => null,
		};
	}

}
