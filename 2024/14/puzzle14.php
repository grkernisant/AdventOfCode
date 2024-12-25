<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const ROBOT_REGEX = '#^p=(-?\d+),(-?\d+)\sv=(-?\d+),(-?\d+)$#';

    public Parser $parser;
    public array $robots;
    public int $cols;
    public int $rows;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $this->path . '.log';

        $this->cols = 101;
        $this->rows = 103;
        if (strpos($this->path, 'test') === 0) {
            $this->cols = 11;
            $this->rows = 7;
        }

        $this->robots = array();
        $file = $this->parser->getInput();
        foreach($file as $line) {
            preg_match(static::ROBOT_REGEX, $line, $matches);
            if ($matches === null) continue;

            $pos = new Position((int) $matches[2], (int) $matches[1]);
            $speed = new Vector((int) $matches[4], (int) $matches[3]);
            $this->robots[] = new Robot($pos, $speed);
        }
    }

    public function __toArray(): array
    {
        $out = array();
        for($y = 0; $y < $this->rows; $y++) {
            $out[] = str_split(str_repeat('.', $this->cols)); 
        }
        foreach($this->robots as $r) {
            $out[$r->pos->y][$r->pos->x] = "1";
        }

        return $out;
    }

    public function checkXmasTree(): bool
    {
        $out = $this->__toArray();
        $l = count($out);
        $i = $l-1;
        $found = false;
        do {
            $row = implode('', $out[$i]);
            $found = preg_match('/1{10,}/', $row) === 1;
            $i--;
        } while($i >= 0 && !$found);

        return $found;
    }

    public function findXmasTree(): int | null
    {
        $time = null;
        $x_cutoff = floor($this->cols/2);
        $y_cutoff = floor($this->rows/2);

        $t = 0;
        $found = false;
        do {
            $this->setPositionsAfter($t);
            $found = $this->checkXmasTree();
            $t++;
        } while(!$found);

        if ($found) {
            $this->setPositionsAfter($t-1);
            $out = $this->__toArray();
            $this->print($out);
            $time = $t-1;
        }

        return $time;
    }

    public function run(): void
    {
        // part 1
        // position after 100s
        $this->setPositionsAfter(100);

        $x_cutoff = floor($this->cols/2);
        $y_cutoff = floor($this->rows/2);

        $quandrant_count = array_reduce($this->robots, function($acc, $curr) use ($x_cutoff, $y_cutoff) {
            if ($curr->pos->x === $x_cutoff || $curr->pos->y === $y_cutoff) return $acc;

            if ($curr->pos->x < $x_cutoff && $curr->pos->y < $y_cutoff) $acc[0]++;
            if ($curr->pos->x > $x_cutoff && $curr->pos->y < $y_cutoff) $acc[1]++;
            if ($curr->pos->x < $x_cutoff && $curr->pos->y > $y_cutoff) $acc[2]++;
            if ($curr->pos->x > $x_cutoff && $curr->pos->y > $y_cutoff) $acc[3]++;

            return $acc;
        }, [0, 0, 0, 0]);

        $safety_factor = array_reduce($quandrant_count, fn($acc, $curr) => $curr * $acc, 1);
        echo sprintf('The safety factor after a 100 sec is %d', $safety_factor), PHP_EOL;

        $xmas_tree_time = $this->findXmasTree();
        echo sprintf('We found an easter egg at %d', $xmas_tree_time ?? -1), PHP_EOL;
    }

    public function print(array $bathroom): void
    {
        $ly = count($bathroom);
        $lx = count($bathroom[0]);
        for($y = 0; $y < $ly; $y++) {
            for($x = 0; $x < $lx; $x++) {
                echo $bathroom[$y][$x];
            }
            echo PHP_EOL;
        }

        echo PHP_EOL, PHP_EOL;
    }

    public function setPositionsAfter(int $t): void
    {
        foreach($this->robots as $i => $r) {
            // d(t) = v * t + x0
            $this->robots[$i]->pos->x = ($t * $r->speed->x + $r->initial->x) % $this->cols;
            if ($this->robots[$i]->pos->x < 0) $this->robots[$i]->pos->x+= $this->cols;

            $this->robots[$i]->pos->y = ($t * $r->speed->y + $r->initial->y) % $this->rows;
            if ($this->robots[$i]->pos->y < 0) $this->robots[$i]->pos->y+= $this->rows;
        }
    }
}

class Robot
{
    public Position $pos;

    public function __construct(public Position $initial, public Vector $speed)
    {
        $this->pos = new Position($initial->y, $initial->x);
    }
}

class Position
{
    public function __construct(public int $y, public int $x) {}

    public function __toString(): string
    {
        return sprintf('[%s,%s]', $this->y, $this->x);
    }

    public function __toArray(): array
    {
        return [$y, $x];
    }

    static function getFromOffset(Position $p, array $v): Position
    {
        return new Position($p->y + $v[0], $p->x + $v[1]); 
    }

    static function getPositionKey(Position $p, string $prefix = ''): string
    {
        return ltrim(sprintf('%s_%d_%d', $prefix, $p->y, $p->x), '_');
    }
}

class Vector
{
    public function __construct(public int $y, public int $x) {}
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $file = file($path);
        $this->input = array_reduce($file, function($acc, $curr) {
            if (!empty(trim($curr))) {
                $acc[]= trim($curr);
            }

            return $acc;
        }, []);
    }

    public function getInput(): array { return $this->input; }
}

class Logger
{
    static public ?string $logger = null;

    static public function log(string $message, $content, bool $append = false)
    {
        $debug = str_replace('    ', ' ', print_r($content, true));
        $filepath = trim(sprintf('%s-%s', $message, static::$logger ?? ''));
        if ($append) {
            file_put_contents($filepath, $debug . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }

        file_put_contents($filepath, $debug);
    }
}

try {
    $default = './test';
    $path = isset($argv[1]) ? $argv[1] : $default;

    $main = new Main($path);
    $main->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
