<?php

/**
 * @file
 * Copies content from the PostgreSQL 'source' connection to the MariaDB
 * 'default' connection, preserving IDs and revisions.
 *
 * Dry run (default, read-only):
 *   lando drush php:script scripts/mariadb-migration/copy-pg-to-mariadb.php
 *
 * Execute (writes to the 'default' connection):
 *   lando drush php:script scripts/mariadb-migration/copy-pg-to-mariadb.php -- --execute
 *
 * All access goes through PHP/PDO; the mysql client is never used.
 */

use Drupal\Core\Database\Database;

ini_set('memory_limit', '-1');

const BATCH_SIZE = 500;
// Stay under the 65535 placeholder limit of a prepared statement.
const MAX_PLACEHOLDERS = 60000;
const NULL_MARKER = "\0NULL\0";

$execute = in_array('--execute', $_SERVER['argv'] ?? [], TRUE);

$src = Database::getConnection('default', 'source');
$dst = Database::getConnection('default', 'default');
$sp = $src->getClientConnection();
$dp = $dst->getClientConnection();
$sp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$dp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
// uid 0 (and similar zero IDs) must be inserted literally.
$dp->exec("SET SESSION sql_mode = CONCAT(@@sql_mode, ',NO_AUTO_VALUE_ON_ZERO')");

// Collections of key_value that are copied; everything else is left alone.
const KV_WHERE = "(collection LIKE ? OR collection IN ('media', 'simple_sitemap'))";
const KV_PARAMS = ['pathauto\\_state.%'];

$skip_exact = [
  'cachetags', 'config', 'config_export', 'config_import', 'config_snapshot',
  'router', 'menu_tree', 'sessions', 'flood', 'batch', 'queue', 'semaphore',
  'watchdog', 'key_value_expire', 'node_access', 'schemadotorg_types',
  'schemadotorg_properties', 'ultimate_cron_log', 'ultimate_cron_lock',
];
$skip_prefix = ['cache_', 'autosave_form_'];

/**
 * Returns the reason a table is skipped, or NULL when it is not in SKIP.
 */
function skip_reason(string $t, array $exact, array $prefix): ?string {
  if (in_array($t, $exact, TRUE)) {
    return 'install-owned / rebuildable / transient';
  }
  foreach ($prefix as $p) {
    if (str_starts_with($t, $p)) {
      return "matches {$p}* (rebuildable / transient)";
    }
  }
  if (str_starts_with($t, 'simple_sitemap') && $t !== 'simple_sitemap_entity_overrides') {
    return 'simple_sitemap generated data';
  }
  return NULL;
}

function out(string $s = ''): void {
  echo $s . "\n";
}

// ---------------------------------------------------------------------------
// Schema introspection.
// ---------------------------------------------------------------------------
$src_tables = $sp->query("SELECT table_name FROM information_schema.tables WHERE table_schema = current_schema() AND table_type = 'BASE TABLE' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);
$dst_tables = $dp->query("SELECT table_name FROM information_schema.tables WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE' ORDER BY 1")->fetchAll(PDO::FETCH_COLUMN);

function src_columns(PDO $p, string $t): array {
  $st = $p->prepare("SELECT column_name FROM information_schema.columns WHERE table_schema = current_schema() AND table_name = ? ORDER BY ordinal_position");
  $st->execute([$t]);
  return $st->fetchAll(PDO::FETCH_COLUMN);
}

function dst_columns(PDO $p, string $t): array {
  $st = $p->prepare("SELECT column_name AS c, data_type AS d, extra AS e FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ordinal_position");
  $st->execute([$t]);
  $cols = [];
  foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $cols[$r['c']] = ['type' => strtolower($r['d']), 'auto' => stripos($r['e'], 'auto_increment') !== FALSE];
  }
  return $cols;
}

function dst_pk(PDO $p, string $t): array {
  $st = $p->prepare("SELECT column_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = 'PRIMARY' ORDER BY seq_in_index");
  $st->execute([$t]);
  return $st->fetchAll(PDO::FETCH_COLUMN);
}

function is_string_type(string $type): bool {
  return in_array($type, ['char', 'varchar', 'tinytext', 'text', 'mediumtext', 'longtext'], TRUE);
}

function is_float_type(string $type): bool {
  return in_array($type, ['float', 'double', 'real'], TRUE);
}

// ---------------------------------------------------------------------------
// Classification.
// ---------------------------------------------------------------------------
$plan = [];
$skipped = [];
$source_only = [];
$col_mismatch = [];
$empty_source = [];

foreach ($src_tables as $t) {
  if ($t === 'key_value') {
    $plan[$t] = ['class' => 'KEY_VALUE'];
    continue;
  }
  if ($reason = skip_reason($t, $skip_exact, $skip_prefix)) {
    $skipped[$t] = $reason;
    continue;
  }
  if (!in_array($t, $dst_tables, TRUE)) {
    $source_only[] = $t;
    continue;
  }
  $plan[$t] = ['class' => 'COPY'];
}
$target_only = array_values(array_filter($dst_tables, fn($t) => !in_array($t, $src_tables, TRUE) && !skip_reason($t, $skip_exact, $skip_prefix)));

out('=== Mode: ' . ($execute ? 'EXECUTE' : 'DRY RUN (no writes)'));
out('Source tables: ' . count($src_tables) . '   Target tables: ' . count($dst_tables));
out();

if ($source_only) {
  out('!!! Source-only tables not in SKIP (stopping):');
  foreach ($source_only as $t) {
    $n = $sp->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn();
    out(sprintf('    %-50s %d rows', $t, $n));
  }
  out();
}

// Column comparison and row counts.
foreach ($plan as $t => &$info) {
  $scols = src_columns($sp, $t);
  $dcols = dst_columns($dp, $t);
  $a = $scols;
  $b = array_keys($dcols);
  sort($a);
  sort($b);
  $info['src_rows'] = (int) $sp->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn();
  $info['dst_rows'] = (int) $dp->query('SELECT COUNT(*) FROM `' . $t . '`')->fetchColumn();
  $extra_src = array_values(array_diff($a, $b));
  $extra_dst = array_values(array_diff($b, $a));
  $only_ct = $extra_src && !$extra_dst && count(array_filter($extra_src, fn($c) => !str_starts_with($c, 'content_translation_'))) === 0;
  if ($only_ct) {
    // Verified by hand: only defaults (und / 0 / 1 / timestamps). Copy the
    // target's columns only.
    $info['dropped_cols'] = $extra_src;
  }
  elseif ($a !== $b) {
    $col_mismatch[$t] = [
      'only_source' => array_values(array_diff($a, $b)),
      'only_target' => array_values(array_diff($b, $a)),
    ];
    $info['class'] = 'COLUMN_MISMATCH';
    continue;
  }
  $info['cols'] = $dcols;
  if ($t !== 'key_value' && $info['src_rows'] === 0) {
    $info['class'] = 'EMPTY_SOURCE';
    $empty_source[] = $t;
  }
}
unset($info);

// ---------------------------------------------------------------------------
// Report classification.
// ---------------------------------------------------------------------------
out('--- COPY tables (planned)');
$total_planned = 0;
foreach ($plan as $t => $info) {
  if ($info['class'] !== 'COPY') {
    continue;
  }
  out(sprintf('    %-55s src=%-8d target-now=%d%s', $t, $info['src_rows'], $info['dst_rows'], isset($info['dropped_cols']) ? '  [source-only cols not copied: ' . implode(', ', $info['dropped_cols']) . ']' : ''));
  $total_planned += $info['src_rows'];
}
out('    Total rows planned: ' . $total_planned);
$fd = array_filter(array_keys($plan), fn($t) => str_starts_with($t, 'field_deleted_'));
out('    field_deleted_* tables classified COPY: ' . ($fd ? implode(', ', $fd) : 'none'));
out('    field_deleted_* tables in source: ' . (implode(', ', array_filter($src_tables, fn($t) => str_starts_with($t, 'field_deleted_'))) ?: 'none'));
out();

out('--- KEY_VALUE (collections: pathauto_state.*, media, simple_sitemap)');
$kvq = $sp->prepare('SELECT collection, COUNT(*) FROM key_value WHERE ' . KV_WHERE . ' GROUP BY collection ORDER BY 1');
$kvq->execute(KV_PARAMS);
$kv_src_rows = 0;
foreach ($kvq->fetchAll(PDO::FETCH_NUM) as [$c, $n]) {
  out(sprintf('    %-50s %d rows', $c, $n));
  $kv_src_rows += $n;
}
$kvt = $dp->prepare('SELECT COUNT(*) FROM key_value WHERE ' . KV_WHERE);
$kvt->execute(KV_PARAMS);
out(sprintf('    planned rows: %d   target rows currently in those collections: %d', $kv_src_rows, $kvt->fetchColumn()));
out();

if ($empty_source) {
  out('--- Exist on both sides but empty in source (not copied)');
  foreach ($empty_source as $t) {
    out(sprintf('    %-55s target-now=%d', $t, $plan[$t]['dst_rows']));
  }
  out();
}

if ($col_mismatch) {
  out('--- COLUMN MISMATCH (skipped, no mapping guessed)');
  foreach ($col_mismatch as $t => $m) {
    out("    $t");
    out('        only in source: ' . implode(', ', $m['only_source']));
    out('        only in target: ' . implode(', ', $m['only_target']));
  }
  out();
}

out('--- SKIP (' . count($skipped) . ')');
foreach ($skipped as $t => $r) {
  out(sprintf('    %-55s %s', $t, $r));
}
out();

if ($target_only) {
  out('--- Target-only tables (informational)');
  foreach ($target_only as $t) {
    out('    ' . $t);
  }
  out();
}

// key_value `state` comparison (read-only, never copied).
out('--- key_value collection "state": NOT copied. Source vs target:');
function kv_state(PDO $p): array {
  $q = $p->query("SELECT name, value FROM key_value WHERE collection = 'state'");
  $rows = [];
  foreach ($q->fetchAll(PDO::FETCH_NUM) as [$n, $v]) {
    $rows[$n] = is_resource($v) ? stream_get_contents($v) : (string) $v;
  }
  ksort($rows);
  return $rows;
}
function preview(string $v): string {
  $p = preg_replace('/[^\x20-\x7e]/', '?', substr($v, 0, 90));
  return $p . (strlen($v) > 90 ? '…' : '');
}
$ss = kv_state($sp);
$ds = kv_state($dp);
foreach (array_unique(array_merge(array_keys($ss), array_keys($ds))) as $k) {
  if (!array_key_exists($k, $ds)) {
    out(sprintf('    %-55s SOURCE-ONLY', $k));
  }
  elseif (!array_key_exists($k, $ss)) {
    out(sprintf('    %-55s TARGET-ONLY', $k));
  }
  elseif ($ss[$k] === $ds[$k]) {
    out(sprintf('    %-55s EQUAL', $k));
  }
  else {
    out(sprintf('    %-55s DIFFERENT', $k));
    out('        source: ' . preview($ss[$k]));
    out('        target: ' . preview($ds[$k]));
  }
}
out();

// State keys to copy (decided by hand); maintenance_mode only when it is 0.
$state_copy = ['system.private_key', 'scheduler_lightweight_cron_access_key', 'webform.version', 'webform.element.message'];
out('system.maintenance_mode in source: ' . preview($ss['system.maintenance_mode'] ?? '(absent)'));
if (isset($ss['system.maintenance_mode']) && in_array($ss['system.maintenance_mode'], ['b:0;', 'i:0;', 's:1:"0";'], TRUE)) {
  $state_copy[] = 'system.maintenance_mode';
  out('  -> value is 0, will copy');
}
else {
  out('  -> not 0 (or absent), will NOT copy');
}
$state_where = "collection = 'state' AND name IN (" . implode(', ', array_fill(0, count($state_copy), '?')) . ')';
out('State keys to copy: ' . implode(', ', $state_copy));
out();

if ($source_only) {
  out('Stopped: resolve source-only tables first.');
  return;
}

if (!$execute) {
  out('Dry run complete. Re-run with `-- --execute` to write.');
  return;
}

// ---------------------------------------------------------------------------
// Copy.
// ---------------------------------------------------------------------------

/**
 * Copies one table (optionally filtered) inside a transaction on the target.
 *
 * @return int
 *   Rows inserted.
 */
function copy_table(PDO $sp, PDO $dp, string $t, array $cols, string $where = '', array $params = []): int {
  $names = array_keys($cols);
  $qcols = implode(', ', array_map(fn($c) => "`$c`", $names));
  $per_batch = max(1, min(BATCH_SIZE, intdiv(MAX_PLACEHOLDERS, count($names))));
  $dp->beginTransaction();
  try {
    $del = $dp->prepare("DELETE FROM `$t`" . ($where ? " WHERE $where" : ''));
    $del->execute($params);
    $sel = $sp->prepare('SELECT ' . implode(', ', array_map(fn($c) => '"' . $c . '"', $names)) . " FROM \"$t\"" . ($where ? " WHERE $where" : ''));
    $sel->execute($params);
    $buf = [];
    $count = 0;
    $flush = function () use (&$buf, $dp, $t, $qcols, $names, &$count) {
      if (!$buf) {
        return;
      }
      $row_ph = '(' . implode(', ', array_fill(0, count($names), '?')) . ')';
      $stmt = $dp->prepare("INSERT INTO `$t` ($qcols) VALUES " . implode(', ', array_fill(0, count($buf), $row_ph)));
      $i = 1;
      foreach ($buf as $row) {
        foreach ($names as $c) {
          $v = $row[$c];
          if ($v === NULL) {
            $stmt->bindValue($i++, NULL, PDO::PARAM_NULL);
          }
          elseif (is_resource($v)) {
            $stmt->bindValue($i++, stream_get_contents($v), PDO::PARAM_LOB);
          }
          elseif (is_bool($v)) {
            $stmt->bindValue($i++, $v ? 1 : 0, PDO::PARAM_INT);
          }
          else {
            $stmt->bindValue($i++, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
          }
        }
      }
      $stmt->execute();
      $count += count($buf);
      $buf = [];
    };
    while ($row = $sel->fetch(PDO::FETCH_ASSOC)) {
      $buf[] = $row;
      if (count($buf) >= $per_batch) {
        $flush();
      }
    }
    $flush();
    $dp->commit();
    return $count;
  }
  catch (Throwable $e) {
    if ($dp->inTransaction()) {
      $dp->rollBack();
    }
    throw $e;
  }
}

/**
 * Finds source rows that collide on a target unique index (case-insensitive).
 */
function find_collisions(PDO $sp, PDO $dp, string $t, array $names): array {
  $st = $dp->prepare("SELECT index_name, column_name FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND non_unique = 0 ORDER BY index_name, seq_in_index");
  $st->execute([$t]);
  $idx = [];
  foreach ($st->fetchAll(PDO::FETCH_NUM) as [$i, $c]) {
    $idx[$i][] = $c;
  }
  $found = [];
  foreach ($idx as $i => $icols) {
    $sel = $sp->query('SELECT ' . implode(', ', array_map(fn($c) => '"' . $c . '"', $icols)) . " FROM \"$t\"");
    $groups = [];
    while ($r = $sel->fetch(PDO::FETCH_NUM)) {
      $raw = implode(' | ', array_map(fn($v) => is_resource($v) ? stream_get_contents($v) : (string) $v, $r));
      $groups[mb_strtolower($raw)][$raw] = TRUE;
    }
    foreach ($groups as $g) {
      if (count($g) > 1) {
        $found[] = "index $i: " . implode('  <->  ', array_keys($g));
        if (count($found) >= 10) {
          return $found;
        }
      }
    }
  }
  return $found;
}

$copied = [];
$failed = [];
$rows_copied = 0;
$jobs = [];
foreach ($plan as $t => $info) {
  if ($info['class'] === 'COPY') {
    $jobs[$t] = [$t, '', []];
  }
  elseif ($info['class'] === 'KEY_VALUE') {
    $jobs['key_value'] = ['key_value', KV_WHERE, KV_PARAMS];
    $jobs['key_value[state]'] = ['key_value', $state_where, $state_copy];
  }
}
foreach ($jobs as $label => [$t, $where, $params]) {
  try {
    $n = copy_table($sp, $dp, $t, $plan[$t]['cols'], $where, $params);
    $copied[$label] = ['table' => $t, 'n' => $n, 'where' => $where, 'params' => $params];
    $rows_copied += $n;
    out(sprintf('copied %-55s %d rows', $label, $n));
  }
  catch (Throwable $e) {
    $failed[$label] = $e->getMessage();
    out(sprintf('FAILED (rolled back) %s', $label));
    out('    error: ' . $e->getMessage());
    foreach (find_collisions($sp, $dp, $t, array_keys($plan[$t]['cols'])) as $line) {
      out('    collision: ' . $line);
    }
  }
}
out();

// ---------------------------------------------------------------------------
// Auto-increment repair.
// ---------------------------------------------------------------------------
out('--- AUTO_INCREMENT check');
$ai_changes = 0;
foreach ($copied as $label => $c) {
  $t = $c['table'];
  if ($label === 'key_value[state]') {
    continue;
  }
  foreach ($plan[$t]['cols'] as $col => $meta) {
    if (!$meta['auto']) {
      continue;
    }
    $max = $dp->query("SELECT MAX(`$col`) FROM `$t`")->fetchColumn();
    $want = ((int) $max) + 1;
    $st = $dp->prepare('SELECT auto_increment FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?');
    $st->execute([$t]);
    $cur = $st->fetchColumn();
    if ($cur === NULL || (int) $cur < $want) {
      $dp->exec("ALTER TABLE `$t` AUTO_INCREMENT = $want");
      out(sprintf('    %-50s %s: %s -> %d', $t, $col, $cur ?? 'NULL', $want));
      $ai_changes++;
    }
  }
}
out("    changes: $ai_changes");
out();

// ---------------------------------------------------------------------------
// Reconcile.
// ---------------------------------------------------------------------------

/**
 * Stringifies a fetched value for checksumming, identically on both sides.
 */
function norm($v, string $type): string {
  if ($v === NULL) {
    return NULL_MARKER;
  }
  if (is_resource($v)) {
    $v = stream_get_contents($v);
  }
  if (is_bool($v)) {
    return $v ? '1' : '0';
  }
  if (is_float_type($type)) {
    return sprintf('%.7f', round((float) $v, 7));
  }
  return (string) $v;
}

/**
 * Checksum over rows ordered by primary key (binary order for strings).
 *
 * Tables without a primary key use sorted per-row hashes instead.
 *
 * @return array{0: string, 1: int}
 *   Hash and row count.
 */
function table_checksum(PDO $p, bool $pg, string $t, array $cols, array $pk, string $where, array $params): array {
  $names = array_keys($cols);
  sort($names);
  $q = fn($c) => $pg ? '"' . $c . '"' : "`$c`";
  $sql = 'SELECT ' . implode(', ', array_map($q, $names)) . ' FROM ' . $q($t) . ($where ? " WHERE $where" : '');
  $st = $p->prepare($sql);
  $st->execute($params);
  $rows = [];
  while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
    $buf = '';
    $key = '';
    $norm = [];
    foreach ($names as $c) {
      $norm[$c] = norm($row[$c], $cols[$c]['type']);
      $buf .= strlen($norm[$c]) . ':' . $norm[$c] . '|';
    }
    // Sort key: primary key values (binary comparison), else the whole row.
    if ($pk) {
      foreach ($pk as $c) {
        $key .= strlen($norm[$c]) . ':' . $norm[$c] . '|';
      }
    }
    else {
      $key = $buf;
    }
    $rows[] = [$key, $buf];
  }
  usort($rows, fn($x, $y) => strcmp($x[0], $y[0]) ?: strcmp($x[1], $y[1]));
  $ctx = hash_init('sha256');
  foreach ($rows as $r) {
    hash_update($ctx, $r[1] . "\n");
  }
  return [hash_final($ctx), count($rows)];
}

out('--- Reconcile (row counts + checksums)');
$mismatches = [];
foreach ($copied as $label => $c) {
  $t = $c['table'];
  $cols = $plan[$t]['cols'];
  $pk = dst_pk($dp, $t);
  [$sh, $sn] = table_checksum($sp, TRUE, $t, $cols, $pk, $c['where'], $c['params']);
  [$dh, $dn] = table_checksum($dp, FALSE, $t, $cols, $pk, $c['where'], $c['params']);
  $ok = $sn === $dn && $sh === $dh;
  out(sprintf('    %-50s src=%-8d dst=%-8d %s%s', $label, $sn, $dn, $ok ? 'OK' : 'MISMATCH', $pk ? '' : ' (no PK: sorted row hashes)'));
  if (!$ok) {
    $mismatches[] = $label;
  }
}
out();

// ---------------------------------------------------------------------------
// Summary.
// ---------------------------------------------------------------------------
out('=== SUMMARY');
out('Tables copied:      ' . count($copied));
out('Rows copied:        ' . $rows_copied);
out('Copy failures:      ' . count($failed) . ($failed ? ' (' . implode(', ', array_keys($failed)) . ')' : ''));
out('Column mismatches:  ' . count($col_mismatch) . ($col_mismatch ? ' (' . implode(', ', array_keys($col_mismatch)) . ')' : ''));
out('Checksum mismatches (expected 0): ' . count($mismatches) . ($mismatches ? ' (' . implode(', ', $mismatches) . ')' : ''));
out('AUTO_INCREMENT raised: ' . $ai_changes);
out('Skipped tables:     ' . count($skipped));
foreach ($skipped as $t => $r) {
  out("    $t — $r");
}
return;
