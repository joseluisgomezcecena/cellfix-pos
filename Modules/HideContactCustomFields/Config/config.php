<?php

return [
    'name' => 'HideContactCustomFields',
    'module_version' => '1.0.0',
    'description' => 'Hides contact custom fields 6-10 from all contact forms including POS modal',
    'hidden_fields' => [
        'custom_field6',
        'custom_field7',
        'custom_field8',
        'custom_field9',
        'custom_field10',
    ],
];
