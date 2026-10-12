<?php

return [
    /**
     * Control if the seeder should create a user per role while seeding the data.
     */
    'create_users' => false,

    /**
     * Control if all the laratrust tables should be truncated before running the seeder.
     */
    'truncate_tables' => true,

    'roles_structure' => [
        'direktur' => [
            'employees' => 'r',
            'cash_advances' => 'r',
            'payrolls' => 'r,u',
            'receipts' => 'r',
        ],
        'admin' => [
            'users' => 'c,r,u,d',
            'departments' => 'c,r,u,d',
            'positions' => 'c,r,u,d',
            'employees' => 'c,r,u,d',
            'salary_components' => 'c,r,u,d',
            'cash_advances' => 'c,r,u,d',
            'posts' => 'c,r,u,d',
            'logs' => 'r',
            'payrolls' => 'c,r,u,d',
            'receipts' => 'c,r,u,d',
        ],
        'karyawan' => [
            'employees' => 'r',
            'cash_advances' => 'c,r',
            'payrolls' => 'r',
        ],
        'developer' => [
            'logs' => 'r',
        ],
        'user' => [
            'users' => 'r',
        ],
    ],

    'permissions_map' => [
        'c' => 'create',
        'r' => 'read',
        'u' => 'update',
        'd' => 'delete',
    ],
];
