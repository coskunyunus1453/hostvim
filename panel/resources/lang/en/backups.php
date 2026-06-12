<?php

return [
    'queued' => 'Backup queued',
    'deleted' => 'Backup deleted',
    'restore_started' => 'Restore started',
    'restore_no_engine_id' => 'No engine backup id; create a new backup and restore from it.',
    'remote_restore_missing' => 'Remote restore requires destination and backup path.',
    'remote_restore_invalid_path' => 'Invalid backup path.',
    'remote_restore_not_found' => 'Backup file not found on remote destination.',
    'remote_restore_download_failed' => 'Could not download the backup from the remote destination.',
    'remote_restore_destination_inactive' => 'Selected backup destination is inactive.',
    'destination_saved' => 'Backup destination saved',
    'schedule_saved' => 'Backup schedule saved',
    'synced' => 'Backup synced to remote destination',
    'download_unavailable' => 'No downloadable backup file found.',
    'upload_required' => 'Backup archive (.tar.gz) is required.',
    'upload_failed' => 'File upload failed.',
    'google_drive_not_configured' => 'Google Drive OAuth is not configured. Set it in Panelze.com → Panel integrations or server .env.',
    'google_drive_state_invalid' => 'OAuth session invalid or expired. Try again.',
    'google_drive_connected' => 'Google Drive connected.',
    'google_drive_disconnected' => 'Google Drive disconnected.',
    'google_drive_token_expired' => 'Google Drive session expired. Reconnect.',
];
