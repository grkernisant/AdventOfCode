<?php

declare(strict_types=1);
error_reporting(E_ALL);
ini_set("display_errors", 1);


class Main
{
    const PATTERN = '/^\#+$/';

    public array $keys;
    public array $locks;
    public Parser $parser;

    public function __construct(public string $path)
    {
        $this->path = pathinfo($path, PATHINFO_FILENAME);
        $this->parser = new Parser($path);
        Logger::$logger = $this->path . '.log';

        $this->keys = array();
        $this->locks = array();
        $input = $this->parser->getInput();
        foreach($input as $in) {
            if (preg_match(static::PATTERN, $in[0])) {
                $this->locks[] = new Lock($this->parseSchema($in));
            } else {
                $this->keys[] = new Key($this->parseSchema($in));
            }
        }
    }

    public function parseSchema(array $schema): array
    {
        foreach($schema as $y => $line) {
            $line = str_replace(['.', '#'], [0, 1], $line);
            $schema[$y] = array_map(fn($x) => (int) $x, str_split($line));
        }

        return $schema;
    }

    public function run(): void
    {
        $nb = 0;
        foreach($this->locks as $lock) {
            foreach($this->keys as $key) {
                $rows = count($key->schema);
                $cols = count($key->schema[0]);
                $x = 0;
                $y = 1;
                do {
                    $fits = $this->partialFit($key, $lock, $y, $x);
                    $x++;
                    if ($x === $cols) {
                        $x = 0;
                        $y++;
                    }
                } while(($y < $rows - 1) && $fits);

                if ($fits && $y === $rows - 1) $nb++;
            }
        }

        echo sprintf('We found %d keys that fit', $nb), PHP_EOL;
    }

    public function partialFit(Key $k, Lock $l, int $y, int $x): bool
    {
        $no_overlap = ((int) $k->schema[$y][$x] ^ (int) $l->schema[$y][$x]) === 1;
        $is_hole = ((int) $k->schema[$y][$x] + (int) $l->schema[$y][$x]) === 0;

        return $no_overlap || $is_hole;
    }
}

class Schema
{
    public function __construct(public array $schema) {}

    public function __toString(): string
    {
        $out = '';
        foreach($this->schema as $line) {
            $out.= implode('', $line) . PHP_EOL;
        }

        return $out;
    }
}

class Lock extends Schema
{
    public function __construct(array $schema)
    {
        parent::__construct($schema);
    }
}

class Key extends Schema
{
    public function __construct(array $schema)
    {
        parent::__construct($schema);
    }
}

class SharedResources
{
    private static array $resources = array();

    public static function get(string $resource_name)
    {
        return static::$resources[$resource_name] ?? null;
    }

    public static function set(string $resource_name, $resource): void
    {
        static::$resources[$resource_name] = $resource;
    }
}

class Parser
{
    public array $input;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $block = array();
        $file = file($path);
        $this->input = array_reduce($file, function($acc, $curr) use (&$block) {
            if (!empty(trim($curr))) {
                $block[] = trim($curr);
            } else {
                $acc[] = $block;
                $block = array();
            }

            return $acc;
        }, []);
        if (!empty($block)) $this->input[] = $block;
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
