<?php

/**
 * @file
 * All of the schedule handling code needed for Backup and Migrate.
 */

backup_migrate_include('crud');

/**
 * Run the preconfigured schedules. Called on cron.
 */
function backup_migrate_schedules_run() {
  backup_migrate_include('profiles');
  foreach (backup_migrate_get_schedules() as $schedule) {
    $schedule->cron();
  }
  backup_migrate_cleanup();
}

/**
 * Get all the available backup schedules.
 */
function backup_migrate_get_schedules() {
  static $schedules = NULL;
  // Get the list of schedules and cache them locally.
  if ($schedules === NULL) {
    $schedules = backup_migrate_crud_get_items('schedule');
  }
  return $schedules;
}

/**
 * Get the schedule info for the schedule with the given ID, or NULL if none exists.
 */
function backup_migrate_get_schedule($schedule_id) {
  $schedules = backup_migrate_get_schedules();
  return @$schedules[$schedule_id];
}
