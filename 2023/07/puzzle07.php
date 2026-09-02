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

    private function initLogs(array $names)
    {
        foreach($names as $log) {
            Logger::log($log, '');
        }
    }

    public static function getCamelPokerWinnings(array $hands): int
    {
        $winnings = 0;
        $sortByRank = CamelPokerHand::sortByRank(...);
        usort($hands, $sortByRank);
        foreach($hands as $i =>$hand) {
            $winnings += $hand->bid * ($i + 1);
        }

        return $winnings;
    }

    public static function parseCamelPokerHands(array $hands, bool $with_joker = false): array
    {
        $parsedHands = [];
        foreach($hands as $hand) {
            $hand = trim($hand);
            if (empty($hand)) continue;

            [$handString, $bid] = explode(' ', $hand);
            $parsedHands[] = new CamelPokerHand(
                cards: Hand::fromString($handString, $with_joker)->getCards(),
                bid: (int) $bid,
                with_joker: $with_joker
            );
        }

        return $parsedHands;
    }

    public function run(): void
    {
        if ($this->runTest()) return;

        $hands = Main::parseCamelPokerHands($this->parser->getInput());
        $winnings = Main::getCamelPokerWinnings($hands);
        echo sprintf("Part 1: %d\n", $winnings);

        $hands_with_jokers = Main::parseCamelPokerHands($this->parser->getInput(), with_joker: true);
        $winnings_with_jokers = Main::getCamelPokerWinnings($hands_with_jokers);
        echo sprintf("Part 2: %d\n", $winnings_with_jokers);
    }

    private function runTest(): bool
    {
        return defined('TEST_MODE') && TEST_MODE === 'TEST_MODE';
    }

    private function setOptions(array $args): void
    {
        $this->options = $args;
        $this->debug_mode = $this->hasOption(static::DEBUG_MODE);
        $this->test_mode = $this->hasOption(static::TEST_MODE);
    }

}

class Card
{
    const JOKER_LABEL = 'J';

    public int $value;

    public function __construct(public string $label, public bool $with_joker = false)
    {
        $this->value = $this->getValueFromLabel();
    }

    public static function getLabelFromValue(int $value): string
    {
        if ($value === 14) return 'A';
        if ($value === 13) return 'K';
        if ($value === 12) return 'Q';
        if ($value === 11 || $value === 1) return 'J';
        if ($value === 10) return 'T';
        if ($value >= 2 && $value <= 9) return (string) $value;

        throw new \Exception("Invalid card value: '$value'");
    }

    private function getValueFromLabel(): int
    {
        $label = strtoupper($this->label);
        if (is_numeric($label) && strlen($this->label) === 1) return (int) $label;
        if ($label === 'A') return 14;
        if ($label === 'K') return 13;
        if ($label === 'Q') return 12;
        if ($label === 'J') return $this->with_joker ? 1 : 11;
        if ($label === 'T') return 10;

        throw new \Exception("Invalid card label: '$label'");
    }
}

class Hand
{
    public array $cards = [];

    public function __construct(array $cards, public bool $with_joker = false)
    {
        foreach ($cards as $card) {
            if (!$card instanceof Card) throw new \Exception('Invalid card in hand');
            $this->cards[] = $card;
        }
    }

    public function __toString(): string
    {
        return implode('', array_map(fn($card) => $card->label, $this->cards));
    }

    public function getCards(): array { return $this->cards; }

    public static function fromString(string $handString, bool $with_joker = false): Hand
    {
        $cardStrings = str_split($handString);
        $cards = array_map(function($c) use ($with_joker) {
            return new Card(label: $c, with_joker: $with_joker);
        }, $cardStrings);

        $classname = get_called_class();
        return new $classname($cards, $with_joker);
    }
}

class CamelPokerHand extends Hand
{
    public CamelPokerHandRank $rank;
    public bool $has_joker;

    public function __construct(array $cards, public int $bid, bool $with_joker = false)
    {
        if (count($cards) !== 5) throw new \Exception('Poker hand must have exactly 5 cards');
        parent::__construct($cards, $with_joker);

        $this->has_joker = $this->checkForJoker();
        $this->rank = $this->getRankFromCards();
    }

    public function __toString(): string
    {
        $real_hand = implode('', array_map(fn($card) => $card->label, $this->cards));
        $optimized_hand = implode('', array_map(fn($card) => $card->label, $this->getOptimizedHand()));
        return sprintf(
            "Hand: %s | Optimized: %s | HasJoker: %b | Rank: %s | Bid: %d",
            $real_hand,
            $optimized_hand,
            $this->has_joker,
            $this->rank->name,
            $this->bid
        );
    }

    private function checkForJoker(): bool
    {
        if (!$this->with_joker) return false;

        foreach ($this->cards as $card) if ($card->label === Card::JOKER_LABEL) return true;
        return false;
    }

    public function getOptimizedHand(): array
    {
        if (!$this->has_joker) return $this->cards;

        // Find the most frequent non-joker card
        $values = array_map(fn($c) => $c->value, $this->cards);
        $counts = array_count_values($values);

        // Remove the joker (value 1) from counts
        unset($counts[1]);

        if (empty($counts)) {
            // All cards are jokers, so optimize to A
            $strongest_value = 14;
        } else {
            $highest_frequency = max($counts);
            $counts_filtered = array_filter($counts, fn($count) => $count === $highest_frequency);
            $strongest_value = max(array_keys($counts_filtered));
        }

        $strongest_label = Card::getLabelFromValue($strongest_value);
        $optimized_cards = array_map(function($card) use ($strongest_label) {
            if ($card->label === Card::JOKER_LABEL) {
                return new Card(label: $strongest_label, with_joker: true);
            }
            return $card;
        }, $this->cards);

        return $optimized_cards;
    }

    private function getRankFromCards(): CamelPokerHandRank
    {
        $values = array_reduce(
            $this->getOptimizedHand(),
            function($carry, $card) {
                $carry[] = $card->label;
                return $carry;
            },
            []
        );

        $counts = array_count_values($values);
        $keys = array_keys($counts);
        switch (count($keys)) {
            case 5:
                return CamelPokerHandRank::HighCard;
            case 4:
                return CamelPokerHandRank::OnePair;
            case 3:
                if (in_array(3, $counts)) return CamelPokerHandRank::ThreeOfAKind;
                return CamelPokerHandRank::TwoPair;
            case 2:
                if (in_array(4, $counts)) return CamelPokerHandRank::FourOfAKind;
                return CamelPokerHandRank::FullHouse;
            case 1:
                return CamelPokerHandRank::FiveOfAKind;
            default:
                throw new \Exception('Invalid hand');
        }
    }

    public static function sortByRank(CamelPokerHand $a, CamelPokerHand $b): int
    {
        // sort by rank | ASC
        if ($a->rank !== $b->rank) return $a->rank->value <=> $b->rank->value;

        // sort by highest card when ranks are equal | DESC
        $i = 0;
        while ($i < 5) {
            $a_card = $a->cards[$i];
            $b_card = $b->cards[$i];
            if ($a_card->value !== $b_card->value) return $a_card->value <=> $b_card->value;

            $i++;
        }

        return 0;
    }
}

enum CamelPokerHandRank: int
{
    case HighCard = 1;
    case OnePair = 2;
    case TwoPair = 3;
    case ThreeOfAKind = 4;
    case FullHouse = 7;
    case FourOfAKind = 8;
    case FiveOfAKind = 11;
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
        if (!is_readable($path)) throw new \Exception("Unreadable input: '$path'");

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
