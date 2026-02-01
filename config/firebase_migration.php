<?php

return [
    // Order matters (parents first)
    'tables' => [
        // You probably DON'T want to migrate these:
        // ['table' => 'cache', 'collection' => 'cache'],
        // ['table' => 'jobs', 'collection' => 'jobs'],

        ['table' => 'faculties', 'collection' => 'faculties', 'pk' => 'id'],
        ['table' => 'departments', 'collection' => 'departments', 'pk' => 'id'],
        ['table' => 'designations', 'collection' => 'designations', 'pk' => 'id'],

        ['table' => 'users', 'collection' => 'users', 'pk' => 'id'],
        ['table' => 'courses', 'collection' => 'courses', 'pk' => 'id'],
        ['table' => 'course_registrations', 'collection' => 'course_registrations', 'pk' => 'id'],
        ['table' => 'course_prerequisites', 'collection' => 'course_prerequisites', 'pk' => 'id'],

        ['table' => 'class_schedules', 'collection' => 'class_schedules', 'pk' => 'id'],
        ['table' => 'course_materials', 'collection' => 'course_materials', 'pk' => 'id'],

        ['table' => 'tests', 'collection' => 'tests', 'pk' => 'id'],
        ['table' => 'questions', 'collection' => 'questions', 'pk' => 'id'],
        ['table' => 'responses', 'collection' => 'responses', 'pk' => 'id'],
        ['table' => 'results', 'collection' => 'results', 'pk' => 'id'],

        // Teams / tokens (migrate only if you really need them in Firestore)
        ['table' => 'teams', 'collection' => 'teams', 'pk' => 'id'],
        ['table' => 'team_user', 'collection' => 'team_user', 'pk' => 'id', 'doc_id_fields' => ['team_id', 'user_id']],
        ['table' => 'team_invitations', 'collection' => 'team_invitations', 'pk' => 'id'],
        ['table' => 'personal_access_tokens', 'collection' => 'personal_access_tokens', 'pk' => 'id'],
    ],
];
