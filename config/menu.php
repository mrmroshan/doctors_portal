<?php

return [
    'items' => [
        [
            'text' => 'Dashboard',
            'icon' => 'fas fa-tachometer-alt',
            'url' => '/home',
            'submenu' => [],
        ],
        [
            'text' => 'Prescription',
            'icon' => 'fas fa-prescription',  // Updated icon
            'url' => '/prescriptions',
            'submenu' => [
                ['text' => 'New', 'url' => '/prescriptions/create'],                
                ['text' => 'All', 'url' => '/prescriptions'],                
          //      ['text' => 'Odoo', 'url' => '/prescription/odoo-prescriptions'],                
            ],
        ],
        [
            'text' => 'Patients',
            'icon' => 'fas fa-users',  // Updated icon
            'url' => '/patients',
            'submenu' => [
                ['text' => 'New', 'url' => '/patients/create'],                
                ['text' => 'All', 'url' => '/patients'],                                
            ],
        ],
        [
            'role' => 'admin',
            'text' => 'Users',
            'icon' => 'fas fa-user-cog',
            'url' => '/admin/users',  // Updated URL
            'submenu' => [
                ['text' => 'New', 'url' => '/admin/users/create'],  // Updated URL               
                ['text' => 'All', 'url' => '/admin/users'],  // Updated URL                             
            ],
              // Optional: Add this if you want to show this menu only to admins
        ]
    ],
];