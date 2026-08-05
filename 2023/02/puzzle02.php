<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    public static $MAX_CUBES = [
        'red' => 12,
        'green' => 13,
        'blue' => 14
    ];

    const string DEFAULT_INPUT = './test';
    const string DEBUG_MODE = '--debug';
    const string TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;

    private Parser $parser;

    public function __construct(array $args)
    {
        $path = $this->getPath($args);
        $this->setOptions($args);
        Logger::$should_log = $this->debug_mode;

        $path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $path . '.log';
    }

    private function getPath(array $args): string
    {
        $path = array_filter($args, fn($arg) => strpos(haystack: $arg, needle: '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function isGameValid(Game $game): bool
    {
        foreach ($game->gameSets as $gameSet) {
            if (!$this->isGameSetValid($gameSet)) {
                return false;
            }
        }
        return true;
    }

    private function isGameSetValid(GameSet $gameSet): bool
    {
        if ($gameSet->red > static::$MAX_CUBES['red']) return false;
        if ($gameSet->green > static::$MAX_CUBES['green']) return false;
        if ($gameSet->blue > static::$MAX_CUBES['blue']) return false;

        return true;
    }

    private function parseGames(array $input): array
    {
        $games = [];
        $game_id = 0;

        foreach ($input as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            if (preg_match('/^Game (\d+):/', $line, $matches)) {
                $game_id = (int) $matches[1];
                $prefix = sprintf('Game %d:', $game_id);
                $set_inputs = substr($line, strlen($prefix));
                $game_sets = $this->parseGameSets($set_inputs);
                $game = new Game(id: $game_id, gameSets: $game_sets, isValid: false);
                $game->isValid = $this->isGameValid($game);
                $games[$game_id] = $game;
            }
        }

        return $games;
    }

    private function parseGameSets(string $line): array
    {
        $game_sets = [];
        $sets = explode(';', trim($line));
        foreach ($sets as $set) {
            $game_set = $this->parseGameSet($set);
            if ($game_set) {
                $game_sets[] = $game_set;
            }
        }

        return $game_sets;
    }

    private function parseGameSet(string $line): ?GameSet
    {
        $sets = explode(',', trim($line));
        $colors = ['red' => 0, 'green' => 0, 'blue' => 0];
        foreach ($sets as $set) {
            if (preg_match('/^(\d+) (red|green|blue)$/', trim($set), $matches)) {
                $count = (int) $matches[1];
                $color = $matches[2];
                if (isset($colors[$color])) {
                    $colors[$color] += $count;
                }
            }
        }

        if (array_sum($colors) > 0) {
            return new GameSet(
                red: $colors['red'],
                green: $colors['green'],
                blue: $colors['blue'],
            );
        }

        return null;
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();

        $games = $this->parseGames($this->parser->getInput());
        $valid_games = array_filter($games, fn($game) => $game->isValid);
        echo sprintf("Valid games ID sum: %d", array_sum(array_keys($valid_games))), PHP_EOL;
    }

    public function runTest(): void
    {
        $test_passed = 0;

        $tests_delimiter = '----------    TESTS    ----------';
        $tests_result = ($test_passed === 6) ? 'Success' : 'Failed';
        $tests_output = sprintf("Passed %d out of %d tests (%s)", $test_passed, 6, $tests_result);
        Logger::log('tests-all', $tests_output);
        echo
            $tests_delimiter, PHP_EOL,
            $tests_output, PHP_EOL,
            $tests_delimiter, str_repeat(PHP_EOL, 2);
    }

    private function setOptions(array $args): void
    {
        $this->debug_mode = (array_search(haystack: $args, needle: static::DEBUG_MODE) !== false);
        $this->test_mode = (array_search(haystack: $args, needle: static::TEST_MODE) !== false);
    }
}

class Game
{
    public function __construct(
        public int $id,
        public array $gameSets,
        public bool $isValid
    ) {}
}

class GameSet
{
    public function __construct(
        public int $red = 0,
        public int $green = 0,
        public int $blue = 0,
        public bool $isValid = true
    ) {}
}

class Parser
{
    public array $input;

    public function __construct(public string $path = './test')
    {
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

        $this->input = file($this->path);
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
