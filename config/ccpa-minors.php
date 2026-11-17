<?php

return [

    'tables' => [
        'assurances' => 'ccpa_age_assurances',
        'guardians' => 'ccpa_guardian_links',
        'opt_ins' => 'ccpa_minor_opt_ins',
    ],

    /*
     * Below this age, personal information may not be sold or shared without opt-in
     * (Cal. Civ. Code §1798.120(c)).
     */
    'opt_in_age' => 16,

    /*
     * Below this age, the opt-in must come from a parent or guardian (§1798.120(c)).
     */
    'parent_age' => 13,

    /*
     * The law applies when you have actual knowledge of the age; willfully disregarding it counts as
     * knowledge. Turn this on to treat users with no age result as under 13 as well (no sale without
     * a parent's opt-in), e.g. for a service directed to children.
     */
    'treat_unknown_as_minor' => false,

    /*
     * Methods that count as reliable age checks. Self-declaration never does.
     */
    'reliable_methods' => [
        'os_signal',
        'store_signal',
        'document',
        'facial_estimation',
        'verifiable_credential',
    ],

    /*
     * How long a result stays valid, per method (CarbonInterval string, null = forever).
     */
    'valid_for' => [
        'self_declaration' => '1 year',
        'os_signal' => '30 days',
        'store_signal' => '30 days',
        'document' => '1 year',
        'facial_estimation' => '1 year',
        'verifiable_credential' => '1 year',
        'guardian' => '1 year',
    ],

    /*
     * Where `ccpa.adult` sends users without a reliable adult result. Null = 403.
     */
    'verify_route' => null,

];
