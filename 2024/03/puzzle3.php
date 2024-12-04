<?php

define('MULTIPLY_REGEX', '#mul\((\d{1,3}),(\d{1,3})\)#');
define('CONDITIONAL_REGEX', "#(do|don't)\(\).+?#");
define('CONDITIONAL_DO', 'do');

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;
$file = file_get_contents($path);

function multiply(string $input): int {
    $sum = 0;
    if (preg_match_all(MULTIPLY_REGEX, $input, $matches)) {
        $i = 1;
        foreach($matches[1] as $index => $x) {
            $y = $matches[2][$index];
            $mul = $x * $y;
            $sum+= $mul;
            echo sprintf("%d. mul(%d,%d) = %d", str_pad($i++, 5, ' ', STR_PAD_LEFT), $x, $y, $mul), PHP_EOL;
        }
    }

    return $sum;
}

echo 'part 2: ', multiply($file), PHP_EOL;
$file = 'do()' . $file;
if (preg_match_all(CONDITIONAL_REGEX, $file, $matches)) {
    $sum = 0;
    $l = count($matches[0])-1;
    for($n=$l; $n>=0; $n--) {
        $pos = strrpos($file, $matches[0][$n]);
        $content = substr($file, $pos);
        if ($matches[1][$n] === CONDITIONAL_DO) {
            echo $content;
            $sum+= multiply($content);
        }
        echo str_repeat(PHP_EOL, 5);
        // re-adjust file
        $file = substr($file, 0, $pos);
    }

    echo 'part 2: ', $sum, PHP_EOL;
}
//function multiply