<?php

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;
$file = fopen($path, 'r');
if (!$file) die ("Could not open input file.\n");

$location_id1 = $location_id2 = array();
while (($line = fgets($file)) !== false) {
    if (preg_match('/^(\d+)\s+(\d+)$/', $line, $matches)) {
        array_push($location_id1, (int) $matches[1]);
        array_push($location_id2, (int) $matches[2]);
    }
}
fclose($file);

if (count($location_id1) !== count($location_id2)) die("Number of entries differ!");

sort($location_id1);
sort($location_id2);
$min_distances = array_map(fn($x, $y) => [$x, $y], $location_id1, $location_id2);

$total_distance = array_reduce($min_distances, function($acc, $current) {
    $acc+= abs($current[1] - $current[0]);
    return $acc;
}, 0);

echo sprintf('The total distance between the lists is %d', $total_distance), PHP_EOL;

$occurance_2 = array();
array_map(function($id) use (&$occurance_2) {
    if (!isset($occurance_2[$id])) $occurance_2[$id] = 0;
    $occurance_2[$id]++;
}, $location_id2);

$similarity_score = 0;
foreach($location_id1 as $loc_id) {
    $similarity_score+= isset($occurance_2[$loc_id])
        ? $loc_id * $occurance_2[$loc_id]
        : 0;
}

echo sprintf('The similarity score between both lists is %d', $similarity_score), PHP_EOL;
