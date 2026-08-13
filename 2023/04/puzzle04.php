<?php

declare(strict_types=1);
error_reporting(E_ALL);

class Main
{
    public const DEFAULT_INPUT = './test';
    public const DEBUG_MODE = '--debug';
    public const TEST_MODE = '--test';

    private bool $test_mode = false;
    private bool $debug_mode = false;
    private array $options;

    private Parser $parser;
    private array $scratchCards;

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
        $path = array_filter($args, fn($arg) => strpos($arg, '--') === false);
        return reset($path) ?: static::DEFAULT_INPUT;
    }

    private function hasOption(string $option): bool {
        return array_search($option, $this->options) !== false;
    }

    private function parseScratchCards(array $input): array
    {
        $cards = array();
        foreach($input as $in) {
            $sc = ScratchCard::from($in);
            if ($sc) $cards[] = $sc;
        }

        return $cards;
    }

    private function getScratchCardPoints(): int
    {
        return array_reduce(
            $this->scratchCards,
            fn($sum, $sc) => $sum + $sc->getPoints(),
            0
        );
    }

    private function initLogs(array $names)
    {
        foreach($names as $log) {
            Logger::log($log, '');
        }
    }

    public function run(): void
    {
        if ($this->test_mode) $this->runTest();
        $this->initLogs(['std_err']);

        $this->scratchCards = $this->parseScratchCards($this->parser->getInput());
        echo sprintf("Part1: %d", $this->getScratchCardPoints()), PHP_EOL;
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
        $this->options = $args;
        $this->debug_mode = $this->hasOption(static::DEBUG_MODE);
        $this->test_mode = $this->hasOption(static::TEST_MODE);
    }
}

class ScratchCard
{
    // Card 1: 41 48 83 86 17 | 83 86  6 31 17  9 48 53
    private static string $CARD_REGEX = '/^Card\s+(\d+):((?:\s+\d+)+)\s\|((?:\s+\d+)+)$/';

    public array $matches;
    public int $winningCardCount;

    public function __construct(
        public int $id,
        public array $winningNumbers,
        public array $numbers
    ) {
        $this->matches = array_intersect($this->winningNumbers, $this->numbers);
        $this->winningCardCount = count($this->matches);
    }

    public function getPoints(): int
    {
        return $this->winningCardCount > 0 ? pow(2, $this->winningCardCount -1) : 0;
    }

    public static function from(string $sc): ?ScratchCard
    {
        preg_match(static::$CARD_REGEX, trim($sc), $matches);
        if ($matches === null) return null;

        $winningNumbers = static::parseNumbers($matches[2]);
        $numbers = static::parseNumbers($matches[3]);
        return new ScratchCard((int) $matches[1], $winningNumbers, $numbers);
    }

    private static function parseNumbers(string $numbers): array
    {
        return array_map(function($a) {
                return (int) $a;
            },
            explode(' ', preg_replace("/\s+/", " ", trim($numbers)))
        );
    }
}

class ColoredOutput
{
    private static $colors = [
        'reset'   => "\033[0m",   // Reset to default
        'red'     => "\033[31m",
        'green'   => "\033[32m",
        'yellow'  => "\033[33m",
        'blue'    => "\033[34m",
        'magenta' => "\033[35m",
        'cyan'    => "\033[36m",
        'white'   => "\033[37m",
        'bold'    => "\033[1m"
    ];

    // Function to print colored text
    static public function paintText(string $text, string $color): string {
        if(!isset(static::$colors[$color])) return $text;

        return sprintf(
            "%s%s%s",
            static::$colors[$color],
            $text,
            static::$colors['reset']
        );
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path = './test')
    {
        if (!is_readable($path)) throw new Exception("Unreadable input: '$path'");

        $this->input = file($this->path, FILE_IGNORE_NEW_LINES);
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
