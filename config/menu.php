<?php

return [
    'items' => [
        [
            'text' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/home',
            'submenu' => [         
            ],
        ],
        [
            'text' => 'Prescription',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/prescriptions',
            'submenu' => [
                ['text' => 'New', 'url' => '/prescriptions/create'],                
                ['text' => 'All', 'url' => '/prescriptions'],                
                ['text' => 'Prescribed Products', 'url' => '/prescription/prescribed-products'],                
            ],
        ],
        [
            'text' => 'Patients',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/patients',
            'submenu' => [
                ['text' => 'New', 'url' => '/patients/create'],                
                ['text' => 'All', 'url' => '/patients'],                                
            ],
        ],
        [
            'text' => 'Users',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/users',
            'submenu' => [
                ['text' => 'New', 'url' => '/users/create'],                
                ['text' => 'All', 'url' => '/users/all'],                                
            ],
        ]
    ],
];