<?php

$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;

if (!is_readable($path)) Throw new Exception('Unreadable input');
$lines = file($path);

$parser = new Parser($lines);
$parser->run();

class PageOrderRules
{
    static public $pages_per_rule = 2;
    static public $flat = array();
    static public $rules = array();

    public function __construct(){}

    public function addPageOrderRule (PageOrderRule $por): void
    {
        array_push(static::$rules, $por);
        array_push(static::$flat, $por->a, $por->b);
    }

    public static function sortPages(int $a, int $b): int
    {
        // have rule about page?
        $rk_a = array_keys(self::$flat, $a);
        $rk_b = array_keys(self::$flat, $b);

        // no rules
        if (empty($rk_a) && empty($rk_b)) return -1;

        // we know b
        if (empty($rk_a)) return 1;

        // we know a
        if (empty($rk_b)) return -1;

        // we know a and b
        $rk_a = array_map(fn($i) => floor($i / PageOrderRules::$pages_per_rule), $rk_a);
        $rk_b = array_map(fn($i) => floor($i / PageOrderRules::$pages_per_rule), $rk_b);
        $common_index = array_intersect($rk_a, $rk_b);
        $common_rule = static::$rules[reset($common_index)];
        return $a === $common_rule->a ? -1 : 1;
    }
}

class PageOrderRule
{
    public function __construct(public int $a, public int $b) {}
}

class Update
{
    public function __construct(public array $page_numbers) {}
}

class Parser
{
    const PRINTING_RULE_DELIMITER = '|';
    const UPDATE_PAGE_DELIMITER = ',';

    static public $last_corrected_order = null;

    private $page_order_rules;
    private $updates;

    public function __construct(array $lines)
    {
        $this->page_order_rules = new PageOrderRules();
        $this->updates = array();

        foreach($lines as $line)
        {
            $line = trim($line);
            if (strpos($line, self::PRINTING_RULE_DELIMITER) !== false)
            {
                $input = explode(static::PRINTING_RULE_DELIMITER, $line);
                $por = new PageOrderRule((int) $input[0], (int) $input[1]);

                $this->page_order_rules->addPageOrderRule($por);
            } else if (strpos($line, self::UPDATE_PAGE_DELIMITER) !== false) {
                $input = explode(static::UPDATE_PAGE_DELIMITER, $line);
                $input = array_map(fn($p) => (int) $p, $input);
                $update = new Update($input);
                array_push($this->updates, $update);
            }
        }
    }

    private function getCorrectOrder(Update $u): array
    {
        $correct_order = array(...$u->page_numbers);
        usort($correct_order, ['PageOrderRules', 'sortPages']);

        return $correct_order;
    }

    private function checkUpdateOrder(Update $u): bool
    {
        $correct_order = $this->getCorrectOrder($u);
        // save last correct order for performance
        $this->saveLastCorrectedOrder($correct_order);

        return $this->getUpdateQRCode($u->page_numbers) === $this->getUpdateQRCode($correct_order);
    }

    private function getLastCorrectedOrder(): array | null
    {
        return static::$last_corrected_order;
    }

    private function getMiddleNumber(array $page_numbers): int
    {
        return $page_numbers[floor(count($page_numbers)/2)];
    }

    private function getUpdateQRCode(array $page_numbers): string
    {
        return implode(static::UPDATE_PAGE_DELIMITER, $page_numbers);
    }

    public function run()
    {
        $middle_numbers = (object) array(
            'correct' => array(),
            'incorrect' => array(),
        );
        foreach($this->updates as $u)
        {
            if ($this->checkUpdateOrder($u)) {
                array_push($middle_numbers->correct, $this->getMiddleNumber($u->page_numbers));
            } else {
                if ($corrected_order = $this->getLastCorrectedOrder()) {
                    array_push($middle_numbers->incorrect, $this->getMiddleNumber($corrected_order));
                    // remove saved corrected order
                    $this->saveLastCorrectedOrder(null);
                }
            }
        }

        echo sprintf('The sum of correct middle page numbers is %s', count($middle_numbers->correct) > 0 ? array_sum($middle_numbers->correct) : 'N/A'), PHP_EOL;
        echo sprintf('The sum of incorrect middle page numbers is %s', count($middle_numbers->incorrect) > 0 ? array_sum($middle_numbers->incorrect) : 'N/A'), PHP_EOL;
    }

    private function saveLastCorrectedOrder(array $page_numbers = null): void
    {
        static::$last_corrected_order = $page_numbers;
    }
}