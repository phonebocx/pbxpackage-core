#!/usr/bin/env php
<?php

include __DIR__ . "/../php/boot.php";

use PhoneBocx\Queue;
use PhoneBocx\Queue\Pdo\SqlitePdoQueue;
use PhoneBocx\Queue\NoItemAvailableException;

$dbfile = Queue::getSqliteFile();

if (!is_dir(dirname($dbfile))) {
  print "No dir for $dbfile\n";
  exit(1);
}

$optmaps = [
  "count" => "count_queue_jobs",
  "add" => "add_job_to_queue",
  "nuke" => "purge_jobs",
  "run::" => "process_queue",
  "package:" => false,
  "data:" => false,
  "delay:" => false
];

// You probably don't want to set the table name.
$opts = getopt("t:", array_keys($optmaps));

if (!empty($opts['t'])) {
  $table = $opts['t'];
} else {
  $table = 'core';
}

if (!file_exists($dbfile)) {
  touch($dbfile);
  chmod($dbfile, 0777);
}

foreach ($optmaps as $k => $func) {
  if (isset($opts[rtrim($k, ':')])) {
    if ($func) {
      // print "Calling $func\n";
      $func($opts);
    }
  }
}

function getQueue($regen = false): SqlitePdoQueue
{
  global $table;
  return Queue::getQueue($table, $regen);
}

function count_queue_jobs($opts)
{
  $q = getQueue();
  print $q->count();
}

function purge_jobs($opts)
{
  $q = getQueue();
  $q->clear();
  print "0";
}

function add_job_to_queue($opts, $attempts = 0, $delay = null)
{
  if (empty($opts['package'])) {
    throw new \Exception("Required --package missing: " . json_encode($opts));
  }
  if (!$delay) {
    if (!empty($opts['delay'])) {
      $delay = $opts['delay'];
      unset($opts['delay']);
    }
  }
  $q = getQueue();
  $job = ["package" => $opts['package'], "submitted" => time(), "lastattempt" => false, "attempts" => $attempts, "opts" => $opts];
  print "Adding " . json_encode($job) . " and '$delay'\n";
  $q->push(json_encode($job), $delay);
}

function process_queue($opts)
{
  $loops = (int) $opts['run'];
  if ($loops < 1) {
    $loops = 5;
  }
  $q = getQueue();
  while ($loops--) {
    try {
      $rawjob = $q->pop();
    } catch (NoItemAvailableException $e) {
      // Nothing to do, nice.
      return;
    }
    if (is_string($rawjob)) {
      $job = json_decode($rawjob, true);
      $job['lastattempt'] = time();
      // Note: pass-by-ref of $job
      if (!run_job($job['package'], $job)) {
        add_job_to_queue($job['opts'], $job['attempts'], time() + 30);
      }
    } else {
      throw new \Exception("Job is an object");
    }
  }
}

function get_pkg_handler($pkg)
{
  $loc = __DIR__ . "/../../$pkg/meta/hooks/job";
  $path = realpath($loc);
  if (is_executable($path)) {
    return $path;
  }
  return false;
}

function run_job($pkg, &$job)
{
  $job['attempts']++;
  $handler = get_pkg_handler($pkg);
  if (!$handler) {
    // That's... very strange. If we have run LESS than 10 times,
    // sleep for 60 seconds, and then retry. Otherwise just discard
    // this job.
    if ($job['attempts'] > 10) {
      print "Discarding job for $pkg as handler missing after 10 attempts\n";
      return true;
    }
  }
  $cmd = get_pkg_handler($pkg) . " --job=" . base64_encode(json_encode($job)) . " 2>&1";
  exec($cmd, $output, $ret);
  // This is temporarily kept in /var/run/queue.log
  print json_encode([$cmd, $output, $ret]);
  return ($ret === 0);
}
