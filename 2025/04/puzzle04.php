<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    const string DEFAULT_INPUT = './test';
    const string DEBUG_MODE = '--debug';
    const string TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;

    private Parser $parser;
    private Grid $grid;

    public function __construct(array $args)
    {
        $path = $this->getPath($args);
        $this->setOptions($args);
        Logger::$should_log = $this->debug_mode;

        $path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $path . '.log';

        $this->init();
    }

    private function getPath(array $args): string
    {
        $path = array_filter($args, fn($arg) => strpos(haystack: $arg, needle: '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function init()
    {
        $grid = array_filter(array_map(function($line) {
            $line = trim($line);
            return $line ? str_split($line) : null;
        }, $this->parser->getInput()));
        $this->grid = new Grid($grid);
    }

    public function run(): void
    {
        try {
            if ($this->test_mode) $this->runTest();

            // Part 1
            echo sprintf("There are %d accessible toilet papers", $this->grid->getNbAccessible()), PHP_EOL;

            // Part 2

        } catch(Throwable $e) {}
    }

    public function runTest(): void
    {
        $test_passed = 0;

    }

    private function setOptions(array $args): void
    {
        $this->debug_mode = (array_search(haystack: $args, needle: static::DEBUG_MODE) !== false);
        $this->test_mode = (array_search(haystack: $args, needle: static::TEST_MODE) !== false);
    }
}

class Grid
{
    public int $cols;
    public int $rows;
    public array $grid;

    public function __construct(array $g)
    {
        $this->cols = count(reset($g));
        $this->rows = count($g);

        // positions
        foreach($g as $row_index => $row) {
            foreach($row as $col_index => $col) {
                if (!isset($this->grid[$row_index])) $this->grid[$row_index] = array();

                $char = GridPosition::fromChar($g[$row_index][$col_index]);
                $this->grid[$row_index][$col_index] = new GridPosition($col_index, $row_index, $char);
            }
        }

        // neighbors
        $this->setNeighbors();

    }

    public function getGridPosition(int $x, int $y): ?GridPosition
    {
        if (!$this->inBound($x, $y)) return null;

        return $this->grid[$y][$x];
    }

    public function getNbAccessible(): int
    {
        $nb_accessible = array_reduce($this->grid, function($acc_total, $current_row) {
            $nb_accessible_row = array_reduce($current_row, function($acc_subtotal, $current_grid_position) {
                $acc_subtotal+= $current_grid_position->isAccessible() === true ? 1 : 0;
                return $acc_subtotal;
            }, 0);
            $acc_total+= $nb_accessible_row;
            return $acc_total;
        }, 0);

        return $nb_accessible;
    }

    public function inBound(int $x, int $y): bool
    {
        if ($x < 0 || $x >= $this->cols) return false;
        if ($y < 0 || $y >= $this->rows) return false;

        return true;
    }

    private function setNeighbors(): void
    {
        $range_y = range(-1, 1);
        $range_x = range(-1, 1);
        for($row = 0; $row < $this->rows; $row++) {
            for($col = 0; $col < $this->cols; $col++) {
                $positions = array();
                if ($this->grid[$row][$col]->hasToiletPaper()) {
                    foreach ($range_y as $y) {
                        foreach($range_x as $x) {
                            $py = $row + $y;
                            $px = $col + $x;
                            if (($py !== $row || $px !== $col) && $this->inBound($px, $py)) {
                                array_push($positions, new Position($px, $py));
                            }
                        }
                    }
                    $this->grid[$row][$col]->setNeighborPositions($positions);
                    $self = $this;
                    $nb_busy_space = array_reduce($positions, function($acc, $curr) use ($self) {
                        $grid_p = $self->getGridPosition($curr->x, $curr->y);
                        $acc+= $grid_p->char === GridPosition::TOILET_PAPER ? 1 : 0;
                        return $acc;
                    }, 0);

                    $this->grid[$row][$col]->setAccessible($nb_busy_space < GridPosition::TILES_FOR_ACCESS);
                }
            }
        }
    }

}

class GridPosition extends Position
{
    const TOILET_PAPER = '@';
    const FLOOR = '.';
    const TILES_FOR_ACCESS = 4;

    private ?bool $accessible;
    private array $neighbor_positions;

    public function __construct(public int $x, public int $y, public ?string $char = null)
    {
        $this->accessible = null;
        $this->neighbor_positions = array();
    }

    public static function fromChar(string $c): string
    {
        if ($c === static::TOILET_PAPER) return static::TOILET_PAPER;

        return static::FLOOR;
    }

    public function hasToiletPaper(): bool
    {
        return $this->char === static::TOILET_PAPER;
    }

    public function isAccessible(): ?bool
    {
        return $this->accessible;
    }

    public function setAccessible(bool $accessible): void
    {
        $this->accessible = $accessible;
    }

    public function setNeighborPositions(array $positions): void
    {
        $this->neighbor_positions = $positions;
    }
}

class Position
{
    public function __construct(public int $x, public int $y) {}
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

        $this->input = file($path);
    }

    public function getInput(): array { return $this->input; }
}

class Logger
{
    static public ?string $logger = null;
    static public bool $should_log = true;

    static public function log(string $message, mixed $content, bool $append = false)
    {
        if (!static::$should_log) return;

        $debug = str_replace('    ', ' ', print_r($content, true));
        $filepath = trim(sprintf('%s-%s', $message, static::$logger ?? ''), '- ');
        if ($append) {
            file_put_contents($filepath, $debug . PHP_EOL, FILE_APPEND | LOCK_EX);
            return;
        }

        file_put_contents($filepath, $debug);
    }

    static public function sudoLog(string $message, mixed $content, bool $append = false)
    {
        $previous_log_state = static::$should_log;
        static::$should_log = true;
        static::log($message, $content, $append);
        static::$should_log = $previous_log_state;
    }
}

try {
    $default = Main::DEFAULT_INPUT;
    $args = isset($argv[1]) ? array_slice($argv, 1) : array($default);

    $main = new Main($args);
    $main->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}
