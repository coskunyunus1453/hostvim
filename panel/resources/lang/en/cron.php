<?php

return [
    'created' => 'Scheduled task created',
    'updated' => 'Scheduled task updated',
    'deleted' => 'Scheduled task deleted',
    'run_success' => 'Task executed successfully',
    'run_failed' => 'Task execution failed',
    'run_timeout' => 'Task timed out (180s)',
    'invalid_schedule' => 'Use exactly five cron fields: minute hour day month weekday (space-separated).',
    'schedule_in_command_field' => 'Put the schedule in the field above (e.g. 0 * * * *). The command box is for the shell line only.',
    'command_empty' => 'Command cannot be empty.',
    'command_too_long' => 'Command may be at most 2000 characters.',
    'command_no_multiline' => 'Command must be a single line.',
    'command_no_substitution' => 'Security: command substitution via `$(...)` or backticks is not allowed.',
    'command_forbidden_pattern' => 'Security: this command pattern is not allowed (e.g. piping into a shell, rm -rf /).',
    'command_path_not_allowed' => 'Paths in the command must stay under your site directories or system tools like /usr/bin.:hint',
];
