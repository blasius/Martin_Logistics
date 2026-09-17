<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Access Review Thresholds
    |--------------------------------------------------------------------------
    | Tunable thresholds for the `access:review` job and the portal access
    | review report. All values can be overridden via env.
    |
    */

    // How many days without any session activity before an account is flagged
    // as inactive (only applies to users that have logged in before).
    'inactive_days' => (int) env('ACCESS_REVIEW_INACTIVE_DAYS', 60),

    // Dispatchers with no managed trips in this window and no active vehicle
    // ownership are considered idle.
    'dispatcher_inactive_days' => (int) env('ACCESS_REVIEW_DISPATCHER_INACTIVE_DAYS', 45),

    // Drivers with no current vehicle assignment are graded by how long ago
    // their last trip was relative to this window.
    'driver_inactive_days' => (int) env('ACCESS_REVIEW_DRIVER_INACTIVE_DAYS', 45),

    // Mechanics whose profile exists but is inactive or who have no user bound.
    'check_mechanics' => (bool) env('ACCESS_REVIEW_CHECK_MECHANICS', true),

    // Whether the review job may lock (deactivate) flagged accounts when they
    // exceed the hard thresholds. Off by default: the job only reports unless
    // this is enabled.
    'auto_deactivate' => (bool) env('ACCESS_REVIEW_AUTO_DEACTIVATE', false),

    // Accounts inactive for this many days are eligible for auto-deactivation
    // (only consulted when auto_deactivate is enabled). Stricter than the
    // reporting threshold to allow a manager a grace window.
    'auto_deactivate_after_days' => (int) env('ACCESS_REVIEW_AUTO_DEACTIVATE_AFTER_DAYS', 120),

    // Never lock accounts holding these roles, regardless of the review.
    'protected_roles' => ['super_admin'],
];