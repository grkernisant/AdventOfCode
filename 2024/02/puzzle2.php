<?php

define('MIN_LEVELS_OFFSET', 1);
define('MAX_LEVELS_OFFSET', 3);

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;
$file = fopen($path, 'r');
if (!$file) die ("Could not open input file.\n");

function applyDampener(string $line, int $index): string {
    $arr = getLevels($line);
    $before = $index > 0 ? array_slice($arr, 0, $index) : array();
    $after  = $index + 1 <= count($arr) - 1 ? array_slice($arr, $index + 1) : array();
    $dampener = array(
        ...$before,
        ...$after
    );

    return implode(' ', $dampener);
}

function checkLevel(string $line, int $index, bool $incr = true): bool {
    if (checkDirection($line, $index) !== $incr) return false;

    $levels = getLevels($line);
    $offset = abs($levels[$index+1] - $levels[$index]);
    if ($offset < MIN_LEVELS_OFFSET) return false;

    if ($offset > MAX_LEVELS_OFFSET) return false;

    return true;
}

function checkDirection(string $line, int $index = 0): bool {
    $levels = getLevels($line);
    $is_increasing = ($levels[$index+1] - $levels[$index]) >= 0;
    return $is_increasing;
}

/**
 * returns the index where the error occured
 * if no errors it returns -1
 */
function checkReport(string $line): int {
    $arr = getLevels($line);
    $nb_levels = count($arr);
    $is_safe = false;
    $is_incr = null;
    $i = 0;
    do {
        if ($is_incr === null) {
            $is_incr = checkDirection($line);
        }

        $is_safe = checkLevel($line, $i, $is_incr);
        $i++;
    } while(($i<=$nb_levels-2) && $is_safe);

    if ($is_safe) return -1;

    return $i;
}

function getLevels(string $line): array {
    return explode(' ', $line);
}

$nb_safe = 0;
$nb_attempts = 0;
while(($report = fgets($file))) {
    try {
        $report = trim($report);
        $nb_attempts++;
        // Attempt #1
        $bad_level = checkReport($report);
        if ($bad_level !== -1) {
            // For max 1 error, 3 possibilities
            // - error on bad level or previous
            // - wrong direction from the start
            // let's remove duplicate entries for performance
            $possibilities = array_unique([
                applyDampener($report, $bad_level), // bad level
                applyDampener($report, $bad_level-1), // previous entry
                applyDampener($report, 0), // incr or decr error from start?
            ]);

            while (count($possibilities)>0 && $bad_level !== -1) {
                $report = array_shift($possibilities);
                $bad_level = checkReport($report);
            }
        }

        if ($bad_level === -1) $nb_safe++;
    } catch(\Throwable $e) {
        fclose($file);
        echo sprintf('Error (%d): %s', $e->getLine(), $e->getMessage()), PHP_EOL;
        exit;
    }
}
fclose($file);

echo sprintf('Nb safe levels: %d (out of %d)', $nb_safe, $nb_attempts), PHP_EOL;