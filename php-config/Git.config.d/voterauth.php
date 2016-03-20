<?php

Git::$repositories['voterauth'] = [
    'remote' => 'git@github.com:CfABrigadePhiladelphia/voterauth.git',
    'originBranch' => 'master',
    'workingBranch' => 'master',
    'trees' => [
        'php-classes/VoterAuth',
        'php-config/VoterAuth/Voter.config.php',
        'php-config/Git.config.d/php-jwt.php',
        'php-config/Git.config.d/voterauth.php',
        'site-root/oauth2.php'
    ]
];