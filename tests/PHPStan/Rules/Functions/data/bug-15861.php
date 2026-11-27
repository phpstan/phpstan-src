<?php declare(strict_types = 1);

namespace Bug15861;

function xl(string $s): string { return $s; }
function text(string $s): string { return $s; }
function f(): void {
$search_options = [
    "demos" => [
        "title" => xl("Demographics"),
        "cols" => [
            "patient_name"   => ["heading" => xl("Patient Name"), "width" => "10%"],
            "patient_id"     => ["heading" => xl("PID"),          "width" => "nowrap"],
            "patient_age"    => ["heading" => xl("Age"),          "width" => "nowrap"],
            "patient_sex"    => ["heading" => xl("Gender"),       "width" => "nowrap"],
            "patient_ethnic" => ["heading" => xl("Ethnicity"),    "width" => "10%"],
            "patient_race"   => ["heading" => xl("Race"),         "width" => "10%"],
            "users_provider" => ["heading" => xl("Provider"),     "width" => "10%"]
        ],
    ],
    "diagnosis_check" => [
        "hidden" => true,
        "cols" => [
            "other_date"      => ["heading" => xl("Diagnosis Date"),  "width" => "nowrap"],
            "patient_name"    => ["heading" => xl("Patient Name"),    "width" => "10%"],
            "patient_id"      => ["heading" => xl("PID"),             "width" => "nowrap"],
            "patient_age"     => ["heading" => xl("Age"),             "width" => "nowrap"],
            "patient_sex"     => ["heading" => xl("Gender"),          "width" => "nowrap"],
            "patient_ethnic"  => ["heading" => xl("Ethnicity"),       "width" => "10%"],
            "users_provider"  => ["heading" => xl("Provider"),        "width" => "10%"],
            "lists_title"     => [                                    "width" => "15%"], // Heading assigned later
            "pr_diagnosis"    => ["heading" => xl("Diagnosis Codes"), "width" => "20%"]
        ],
        "sort_cols" => -1,
    ],
    "prescripts" => [
        "title" => xl("Prescriptions"),
        "cols" => [
            "other_date"        => ["heading" => xl("Filled"),       "width" => "10%"],
            "patient_name"      => ["heading" => xl("Patient Name"), "width" => "10%"],
            "patient_id"        => ["heading" => xl("PID"),          "width" => "nowrap"],
            "patient_age"       => ["heading" => xl("Age"),          "width" => "nowrap"],
            "patient_sex"       => ["heading" => xl("Gender"),       "width" => "nowrap"],
            "rx_drug"           => ["heading" => xl("Drug"),         "width" => "20%"],
            "rx_medicine_units" => ["heading" => xl("Units"),        "width" => "nowrap"],
            "rx_directions"     => ["heading" => xl("Directions"),   "width" => "10%"],
            "rx_quantity"       => ["heading" => xl("Quantity"),     "width" => "nowrap"],
            "rx_refills"        => ["heading" => xl("Refills"),      "width" => "nowrap"]
        ],
    ],
    "comms" => [
        "title" => xl("Communication"),
        "cols" => [
            "patient_date"   => ["heading" => xl("Date Created"),  "width" => "nowrap"],
            "patient_name"   => ["heading" => xl("Patient Name"),  "width" => "10%"],
            "patient_id"     => ["heading" => xl("PID"),           "width" => "nowrap"],
            "patient_age"    => ["heading" => xl("Age"),           "width" => "nowrap"],
            "patient_sex"    => ["heading" => xl("Gender"),        "width" => "nowrap"],
            "patient_ethnic" => ["heading" => xl("Ethnicity"),     "width" => "10%"],
            "users_provider" => ["heading" => xl("Provider"),      "width" => "10%"],
            "communications" => ["heading" => xl("Communication"), "width" => "15%"]
        ],
    ],
    "insurers" => [
        "title" => xl("Insurance Companies"),
        "cols" => [
            "patient_date"   => ["heading" => xl("Date Created"),       "width" => "nowrap"],
            "patient_name"   => ["heading" => xl("Patient Name"),       "width" => "10%"],
            "patient_id"     => ["heading" => xl("PID"),                "width" => "nowrap"],
            "patient_age"    => ["heading" => xl("Age"),                "width" => "nowrap"],
            "patient_sex"    => ["heading" => xl("Gender"),             "width" => "nowrap"],
            "patient_ethnic" => ["heading" => xl("Ethnicity"),          "width" => "10%"],
            "users_provider" => ["heading" => xl("Insurance Provider"), "width" => "10%"],
            "ins_name"       => ["heading" => xl("Primary Insurance"),  "width" => "10%"]
        ],
    ],
    "encounts" => [
        "title" => xl("Encounters"),
        "cols" => [
            "other_date"     => ["heading" => xl("Encounter Date"),        "width" => "nowrap"],
            "patient_name"   => ["heading" => xl("Patient Name"),          "width" => "10%"],
            "patient_id"     => ["heading" => xl("PID"),                   "width" => "nowrap"],
            "patient_age"    => ["heading" => xl("Age"),                   "width" => "nowrap"],
            "patient_sex"    => ["heading" => xl("Gender"),                "width" => "nowrap"],
            "users_provider" => ["heading" => xl("Provider"),              "width" => "10%"],
            "enc_type"       => ["heading" => xl("Encounter type"),        "width" => "20%"],
            "enc_reason"     => ["heading" => xl("Reason"),                "width" => "15%"],
            "enc_facility"   => ["heading" => xl("Facility"),              "width" => "10%"],
            "enc_discharge"  => ["heading" => xl("Discharge Disposition"), "width" => "10%"]
        ],
    ],
    "observs" => [
        "title" => xl("Observations"),
        "cols" => [
            "other_date"      => ["heading" => xl("Date"),         "width" => "nowrap"],
            "patient_name"    => ["heading" => xl("Patient Name"), "width" => "10%"],
            "patient_id"      => ["heading" => xl("PID"),          "width" => "nowrap"],
            "patient_age"     => ["heading" => xl("Age"),          "width" => "nowrap"],
            "patient_sex"     => ["heading" => xl("Gender"),       "width" => "nowrap"],
            "users_provider"  => ["heading" => xl("Provider"),     "width" => "10%"],
            "obs_code"        => ["heading" => xl("Code"),         "width" => "nowrap"],
            "obs_description" => ["heading" => xl("Description"),  "width" => "15%"],
            "obs_type"        => ["heading" => xl("Type"),         "width" => "10%"],
            "obs_value"       => ["heading" => xl("Value"),        "width" => "nowrap"],
            "obs_units"       => ["heading" => xl("Units"),        "width" => "nowrap"],
            "obs_comments"    => ["heading" => xl("Comments"),     "width" => "20%"]
        ],
        "sort_cols" => -1,
    ],
    "procs" => [
        "title" => xl("Procedures"),
        "cols" => [
            "other_date"      => ["heading" => xl("Order Date"),         "width" => "nowrap"],
            "patient_name"    => ["heading" => xl("Patient Name"),       "width" => "10%"],
            "patient_id"      => ["heading" => xl("PID"),                "width" => "nowrap"],
            "users_provider"  => ["heading" => xl("Procedure Provider"), "width" => "10%"],
            "pr_lab"          => ["heading" => xl("Lab"),                "width" => "10%"],
            "pr_status"       => ["heading" => xl("Status"),             "width" => "nowrap"],
            "prc_procedure"   => ["heading" => xl("Procedure Test"),     "width" => "10%"],
            "pr_diagnosis"    => ["heading" => xl("Primary Diagnosis"),  "width" => "20%"],
            "prc_diagnoses"   => ["heading" => xl("Diagnosis Codes"),    "width" => "20%"]
        ],
        "sort_cols" => -2,
        "acl" => ["encounters", "coding_a"]
    ],
    "results" => [
        "title" => xl("Lab Results"),
        "cols" => [
            "other_date"         => ["heading" => xl("Date"),           "width" => "nowrap"],
            "result_facility"    => ["heading" => xl("Facility"),       "width" => "10%"],
            "patient_id"         => ["heading" => xl("PID"),            "width" => "nowrap"],
            "result_description" => ["heading" => xl("Procedure Test"), "width" => "10%"],
            "result_result"      => ["heading" => xl("Result"),         "width" => "5%"],
            "result_units"       => ["heading" => xl("Unit"),           "width" => "nowrap"],
            "result_range"       => ["heading" => xl("Range"),          "width" => "5%"],
            "result_abnormal"    => ["heading" => xl("Abnormal"),       "width" => "nowrap"],
            "result_comments"    => ["heading" => xl("Comments"),       "width" => "20%"],
            "result_document_id" => ["heading" => xl("Document ID"),    "width" => "nowrap"],
        ],
        "sort_cols" => -2,
        "acl" => ["patients", "lab"]
    ]

];
    foreach ($search_options as $key => $items) {
        if (!isset($items["hidden"]) || !$items["hidden"]) {
            echo text($items["title"]);
        }
    }
}
