<?php

// config for Hwkdo/IntranetAppTeamsBot
return [
'roles' => [
        'admin' => [
            'name' => 'App-TeamsBot-Admin',
            'permissions' => [
                'see-app-teams-bot',
                'manage-app-teams-bot',
            ]
        ],
        'user' => [
            'name' => 'App-TeamsBot-Benutzer',
            'permissions' => [
                'see-app-teams-bot',                
            ]
        ],
]
];
