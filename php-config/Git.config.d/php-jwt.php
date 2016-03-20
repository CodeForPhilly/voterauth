<?php

Git::$repositories['php-jwt'] = [
    'remote' => 'https://github.com/firebase/php-jwt.git',
    'originBranch' => 'master',
    'workingBranch' => 'master',
    'trees' => [
        'php-classes/Firebase/JWT' => 'src'
    ]
];