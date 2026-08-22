<?php
// app_version.php
// Returns the minimum app version required to keep using EJR Mobile.
// The app calls this on every launch and blocks with an "Update Required"
// dialog if its own version is below min_version.
//
// To force an update after a future release, just change min_version below
// (and optionally the message) — no other backend changes needed.

header('Content-Type: application/json');

echo json_encode([
    // Update this whenever you ship a release you want to force users onto
    // (e.g. after fixing a security issue). Must match pubspec.yaml's
    // "version:" line (the part before the "+build" number), e.g. "1.1.7".
    'min_version' => '1.1.6',

    // Optional: shown in the update dialog instead of the default message.
    'message' => 'A new version of EJR Mobile is available. Please update to continue.',
]);
