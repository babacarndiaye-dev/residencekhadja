<?php

/*
|--------------------------------------------------------------------------
| Paie (§45) — brut / net paramétrable
|--------------------------------------------------------------------------
*/

return [

    'working_days_per_month' => 26,
    'hours_per_day' => 8,
    'overtime_multiplier' => 1.15,   // majoration heures supplémentaires (jour)
    'night_multiplier' => 1.60,      // heures de nuit (réservé — calcul par créneau à venir)
    'sunday_multiplier' => 1.60,
    'holiday_multiplier' => 2.00,

    /*
    | Barème IRPP progressif (Sénégal, CGI). Appliqué sur le revenu imposable
    | ANNUEL divisé par le nombre de parts, puis multiplié par les parts.
    | Version simplifiée (sans la réduction pour charges de famille détaillée) —
    | à faire valider par le comptable ; entièrement paramétrable ici.
    */
    'irpp_brackets' => [
        ['up_to' => 630000, 'rate' => 0.00],
        ['up_to' => 1500000, 'rate' => 0.20],
        ['up_to' => 4000000, 'rate' => 0.30],
        ['up_to' => 8000000, 'rate' => 0.35],
        ['up_to' => 13500000, 'rate' => 0.37],
        ['up_to' => null, 'rate' => 0.40],
    ],

    // Quotient familial : parts selon la situation + 0,5 par personne à charge.
    'family_quotient' => [
        'situations' => [
            'celibataire' => 1.0,
            'divorce' => 1.0,
            'veuf' => 1.0,
            'marie' => 1.5,
        ],
        'per_dependent' => 0.5,
        'max_parts' => 5.0,
    ],

    // TRIMF (Taxe Représentative de l'Impôt du Minimum Fiscal) — montant ANNUEL par tranche de revenu imposable.
    'trimf_brackets' => [
        ['up_to' => 599999, 'amount' => 900],
        ['up_to' => 1000000, 'amount' => 3600],
        ['up_to' => 2000000, 'amount' => 4800],
        ['up_to' => 7000000, 'amount' => 12000],
        ['up_to' => 12000000, 'amount' => 18000],
        ['up_to' => null, 'amount' => 36000],
    ],

    // Prime d'ancienneté (convention interprofessionnelle) : x % après N ans, +step %/an, plafond.
    'seniority' => [
        'enabled' => true,
        'start_years' => 2,
        'start_percent' => 2,
        'step_percent' => 1,
        'cap_percent' => 25,
    ],

    // Prime de fin d'année / 13e mois (fraction du salaire de base, versée sur le run de décembre).
    'thirteenth_month' => [
        'enabled' => false,
        'fraction' => 1.0,
        'month' => 12,
    ],

    // Composants de rémunération créés au seed.
    // kind: earning | deduction   calc: fixed | percent_base | rate_per_hour
    'components' => [
        ['code' => 'BASE', 'name' => 'Salaire de base', 'kind' => 'earning', 'calc' => 'fixed', 'taxable' => true, 'system' => 'base', 'all' => false],
        ['code' => 'TRANSPORT', 'name' => 'Prime de transport', 'kind' => 'earning', 'calc' => 'fixed', 'taxable' => false, 'default_amount' => 26000, 'all' => true],
        ['code' => 'LOGEMENT', 'name' => 'Indemnité de logement', 'kind' => 'earning', 'calc' => 'fixed', 'taxable' => true, 'all' => false],
        ['code' => 'RESPONSABILITE', 'name' => 'Indemnité de responsabilité', 'kind' => 'earning', 'calc' => 'fixed', 'taxable' => true, 'all' => false],
        ['code' => 'HSUP', 'name' => 'Heures supplémentaires', 'kind' => 'earning', 'calc' => 'rate_per_hour', 'taxable' => true, 'system' => 'overtime', 'all' => false],
        ['code' => 'ANCIENNETE', 'name' => 'Prime d’ancienneté', 'kind' => 'earning', 'calc' => 'percent_base', 'taxable' => true, 'system' => 'seniority', 'all' => true],
        ['code' => 'M13', 'name' => 'Prime de fin d’année', 'kind' => 'earning', 'calc' => 'fixed', 'taxable' => true, 'system' => 'thirteenth', 'all' => false],
        ['code' => 'PRIME', 'name' => 'Prime exceptionnelle', 'kind' => 'earning', 'calc' => 'fixed', 'taxable' => true, 'system' => 'adjustment', 'all' => false],

        ['code' => 'IPRES', 'name' => 'Retenue IPRES', 'kind' => 'deduction', 'calc' => 'percent_base', 'default_rate' => 5.6, 'all' => true],
        ['code' => 'CSS', 'name' => 'Retenue CSS (part salariale)', 'kind' => 'deduction', 'calc' => 'percent_base', 'default_rate' => 0, 'all' => true],
        ['code' => 'IRPP', 'name' => 'Impôt sur le revenu (IRPP)', 'kind' => 'deduction', 'calc' => 'bareme', 'system' => 'irpp', 'all' => true],
        ['code' => 'TRIMF', 'name' => 'TRIMF', 'kind' => 'deduction', 'calc' => 'bracket', 'system' => 'trimf', 'all' => true],
        ['code' => 'ABSENCE', 'name' => 'Retenue pour absence', 'kind' => 'deduction', 'calc' => 'fixed', 'system' => 'absence', 'all' => false],
        ['code' => 'AVANCE', 'name' => 'Remboursement avance', 'kind' => 'deduction', 'calc' => 'fixed', 'system' => 'advance', 'all' => false],
        ['code' => 'RETENUE', 'name' => 'Retenue diverse', 'kind' => 'deduction', 'calc' => 'fixed', 'system' => 'adjustment', 'all' => false],
    ],

    // Comptes comptables pour la passation de la paie.
    'accounts' => [
        'gross_expense' => '661000',   // Charges de personnel
        'withholdings' => '447000',    // État & organismes sociaux, retenues à reverser
    ],
];
