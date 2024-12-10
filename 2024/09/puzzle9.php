<?php

class Parser
{
    public DiskMap $disk_map;

    public function __construct(public string $path)
    {
        if (!is_readable($path)) throw new Exception('Unreadable input');

        $file = fopen($path, 'r');
        if (!$file) throw new Exception('Could not open file: ' . $path);

        try {
            $this->disk_map = new DiskMap($this->path);
            $n = 0;
            while (($char = fgetc($file)) !== false) {
                if (!is_numeric($char)) throw new Exception('Unreadable filesize: ' . $char);

                $f = new File(file_id: $n, filesize: (int) $char);
                $dbf = new DiskBlock(type: DiskBlock::BLOCK_TYPE_FILE, element: $f);
                $this->disk_map->addBlock($dbf);

                $char = fgetc($file);
                if ($char === false) break;
                if (!empty(trim($char)) && !is_numeric($char)) throw new Exception('Corrupted space size: ' . $char);

                if (is_numeric($char)) {
                    $s = new Space((int) $char);
                    $dbs = new DiskBlock(type: DiskBlock::BLOCK_TYPE_SPACE, element: $s);
                    $this->disk_map->addBlock($dbs);
                }
                $n++;
            }
            echo 'we have read ' . (2*$n) . ' chars', PHP_EOL;
        } catch(\Throwable $e) {
            fclose($file);
            throw $e;
        }

        fclose($file);
    }

    public function run()
    {
        // part 1
        // $this->disk_map->defrag();
        // part 2
        $debug = $this->disk_map . PHP_EOL;
        $this->disk_map->moveFiles();
        $debug.= $this->disk_map . PHP_EOL;
        file_put_contents('disk_map-' . pathinfo($this->path, PATHINFO_BASENAME), $debug);
        /*$a = array_filter($this->disk_map->disk, function($db) {
            return $db->type === DiskBlock::BLOCK_TYPE_FILE && $db->element->moved === false;
        });
        print_r($a);*/
        echo sprintf('The checksum after compacting is %d', $this->disk_map->getChecksum()), PHP_EOL;
    }
}

class DiskMap
{
    const SPACE = '.';

    public static $filesizes = array();

    public array $disk;

    public function __construct(public string $basename)
    {
        $this->disk = array();
        $this->basename = pathinfo($basename, PATHINFO_BASENAME);
    }

    public function __toString(): string
    {
        $output = '';
        foreach($this->disk as $disk_block) {
            if ($disk_block->type == DiskBlock::BLOCK_TYPE_FILE) {
                for($i=0; $i<$disk_block->element->filesize; $i++) {
                    $output.= $disk_block->element->file_id;
                }
            } else {
                for($i=0; $i<$disk_block->element->size; $i++) {
                    $output.= static::SPACE;
                }
            }
        }

        return $output;
    }

    public function addBlock(DiskBlock $db): void
    {
        $this->disk[] = $db;

        if ($db->type === DiskBlock::BLOCK_TYPE_FILE && !isset(static::$filesizes[$db->element->file_id])) {
            static::$filesizes[$db->element->file_id] = array();
        }

        if ($db->type === DiskBlock::BLOCK_TYPE_FILE && array_search(
            $db->element->filesize,
            static::$filesizes[$db->element->file_id]) === false
        ) {
            static::$filesizes[$db->element->file_id][] = $db->element->filesize;
        }
    }

    public function defrag()
    {
        do {
            $file_index = $this->lastIndexOf(DiskBlock::BLOCK_TYPE_FILE);
            $space_index = $this->getNextFreeSpaceIndex();
            $swapped = false;
            if ($file_index>-1 && $space_index>-1 && $file_index > $space_index) {
                $swapped = $this->swapFileToSpace($file_index, $space_index);
            }
        } while($file_index>-1 && $space_index>-1 && $file_index > $space_index && $swapped);
    }

    public function find(string $type, ?int $from = null, ?bool $incr = true): int
    {
        $l = count($this->disk);
        if ($l === 0) return -1;

        $f = isset($from) && is_numeric($from) ? $from : ($incr ? 0 : $l - 1);
        if ($f < 0 && $incr) $f = 0;
        if ($f > $l && !$incr) $f = $l - 1;
        if ($f < 0 || $f > $l-1) return -1;

        $found = false;
        while ($f > -1 && $f < $l && !$found) {
            if (($found = $this->disk[$f]->type === $type)) return $f;
            $f+= $incr ? 1 : -1;
        }

        return -1;
    }

    public function getChecksum(): int
    {
        file_put_contents('checksum-' . $this->basename, '');
        $checksum = 0;
        $index = 0;
        foreach($this->disk as $disk_block) {
            if ($disk_block->type == DiskBlock::BLOCK_TYPE_FILE) {
                $range = range($index, $index+$disk_block->element->filesize-1);
                foreach($range as $r) {
                    $checksum+= $r * $disk_block->element->file_id;
                    $debug = sprintf('%d += %d * %d (%d)', $checksum, $r, $disk_block->element->file_id, $r * $disk_block->element->file_id) . PHP_EOL;
                    file_put_contents('checksum-' . $this->basename, $debug , FILE_APPEND | LOCK_EX);
                }
                $index = end($range) + 1;
            } else {
                $index+= $disk_block->element->size;
            }
        }

        return $checksum;
    }

    public function getFreeSpaceIndex(?bool $incr = true, ?int $from = 0, ?int $larger_than = 0): int
    {
        $found = false;
        $method = $incr ? 'indexOf' : 'lastIndexOf';
        do {
            $free_index = $this->{$method}(DiskBlock::BLOCK_TYPE_SPACE, $from);
            if ($free_index > -1) {
                if ($incr && $from < $free_index) $from = $free_index;
                if (!$incr && $from > $free_index) $from = $free_index;
            }
            $found = $free_index > -1 && isset($this->disk[$free_index]) && $this->disk[$free_index]->element->size>$larger_than;
            if (!$found) $from+= $incr ? 1 : -1;
        } while($free_index !== -1 && !$found);

        return $free_index;
    }

    public function getNextFreeSpaceIndex(?int $from = 0, ?int $larger_than = 0): int
    {
        return $this->getFreeSpaceIndex(incr: true, from: $from, larger_than: $larger_than);
    }

    public function getPreviousFreeSpaceIndex(?int $from = 0): int
    {
        return $this->getFreeSpaceIndex(incr: false, from: $from);
    }

    public function indexOf(string $type, ?int $from = null): int
    {
        return $this->find(type: $type, from: $from, incr: true);
    }

    public function lastIndexOf(string $type, ?int $from = null): int
    {
        return $this->find(type: $type, from: $from, incr: false);
    }

    public function lastIndexOfUnmoved(?int $from = null): int
    {
        $found = false;
        $from = $from ?? (count($this->disk) - 1);
        do {
            $file_index = $this->lastIndexOf(DiskBlock::BLOCK_TYPE_FILE, $from);
            if ($file_index > -1 && $from > $file_index) $from = $file_index;
            $found = $file_index > -1 && isset($this->disk[$file_index]) && $this->disk[$file_index]->element->moved === false;
            if (!$found) $from--;
        } while($file_index !== -1 && !$found);

        if ($file_index > -1 && isset($this->disk[$file_index])) {
            $this->disk[$file_index]->element->moved = true;
        }

        return $file_index;
    }

    public function moveFiles(): void
    {
        file_put_contents('move-' . $this->basename, '');
        // echo $this, str_repeat(PHP_EOL, 1);
        do {
            $file_id = -1;
            $file_index = $this->lastIndexOfUnmoved();
            if ($file_index > -1) $from = $file_index;
            $filesize = 0;
            if ($file_index > -1 && isset($this->disk[$file_index])) $filesize = $this->disk[$file_index]->element->filesize - 1;
            $space_index = $file_index > -1 ? $this->getNextFreeSpaceIndex(0, $filesize) : -1;
            if ($file_index > -1 && isset($this->disk[$file_index])) {
                $file_id = $this->disk[$file_index]->element->file_id;
            }
            $debug = sprintf("Trying to move %d @ %d to %d with a space larger_than %d", $file_id, $file_index, $space_index, $filesize) . PHP_EOL;
            file_put_contents('move-' . $this->basename, $debug, FILE_APPEND | LOCK_EX);
            if ($file_index > -1 && $space_index > -1 && $file_index > $space_index) {
                $this->swapFileToSpace($file_index, $space_index, true);
                // echo $this, str_repeat(PHP_EOL, 1);
            }
        } while($file_id > -1);
    }

    public function sortBy(?string $order_by = 'file_id DESC, filesize DESC'): array
    {
        $file_ids = array_keys(static::$filesizes);
        foreach(static::$filesizes as $k => $v) {
            $values = array(...$v);
            sort($values);
            static::$filesizes[$k] = array_reverse($values);
        }

        $keys = array_reverse($file_ids);
        $sort_by = array();
        foreach ($keys as $file_id) {
            foreach(static::$filesizes[$file_id] as $filesize) {
                $sort_by[] = new File($file_id, $filesize);
            }
        }

        return $sort_by;
    }

    public function swapFileToSpace(int $file_index, int $space_index, ?bool $moved = true): bool
    {
        $file_db = isset($this->disk[$file_index]) ? $this->disk[$file_index] : null;
        $space_db = isset($this->disk[$space_index]) ? $this->disk[$space_index] : null;
        if ($file_db === null || $space_db === null) return false;

        if ($file_db->type !== DiskBlock::BLOCK_TYPE_FILE) return false;
        if ($space_db->type !== DiskBlock::BLOCK_TYPE_SPACE) return false;
        if ($file_db->element->filesize <= 0) return false;
        if ($space_db->element->size <= 0) return false;

        // how much can we swap?
        $file_id = $file_db->element->file_id;
        $src = $file_db->element->filesize;
        $dst = $space_db->element->size;
        $transfer_size = min($src, $dst);
        $space_remainder = $dst - $transfer_size;
        // space created
        $next_space = $this->indexOf(DiskBlock::BLOCK_TYPE_SPACE, $file_index + 1);
        $space_after = $next_space === ($file_index + 1);
        $prev_space = $this->lastIndexOf(DiskBlock::BLOCK_TYPE_SPACE, $file_index - 1);
        $space_before = $prev_space === ($file_index - 1);
        $space_created = $transfer_size;
        $remove_from = $file_index;
        $remove_nb = 1;
        if ($space_before && $space_after) {
            // merge space + increase by transfer
            $space_created+= $this->disk[$prev_space]->element->size;
            $space_created+= $this->disk[$next_space]->element->size;
            $remove_from--;
            $remove_nb = 3;
        } else if ($space_before) {
            // increase space before
            $space_created+= $this->disk[$prev_space]->element->size;
            $remove_from--;
            $remove_nb = 2;
        } else if ($space_after) {
            // increase space after
            $space_created+= $this->disk[$next_space]->element->size;
            $remove_nb = 2;
        } else {
            // create space after
        }
        // inserted files
        $space_type = DiskBlock::BLOCK_TYPE_SPACE;
        $file_type = DiskBlock::BLOCK_TYPE_FILE;
        $insert_files = array();
        if ($transfer_size < $src) {
            $f = new File($file_id, ($src - $transfer_size));
            $insert_files[] = new DiskBlock($file_type, $f);
        }
        $s = new Space($space_created);
        $insert_files[] = new DiskBlock($space_type, $s);
        array_splice($this->disk, $remove_from, $remove_nb, $insert_files);
        // move file
        $moved_files = array(
            new DiskBlock($space_type, new Space(0)),
            new DiskBlock($file_type, new File($file_id, $transfer_size, $moved)),
            new DiskBlock($space_type, new Space($space_remainder)),
        );
        array_splice($this->disk, $space_index, 1, $moved_files);

        return true;
    }
}

class DiskBlock
{
    const BLOCK_TYPE_FILE = 'FILE';
    const BLOCK_TYPE_SPACE = 'SPACE';

    public function __construct(public string $type, public File | Space $element) {}
}

class File
{
    public function __construct(public int $file_id, public int $filesize, public ?bool $moved = false) {}
}

class Space
{
    public function __construct(public int $size) {}
}


$default = './test.txt';
$path = isset($argv[1]) ? $argv[1] : $default;

try {
    $parser = new Parser($path);
    $parser->run();
} catch (Throwable $e) {
    die(sprintf('Error (%d): %s%s%s', $e->getLine(), $e->getMessage(), PHP_EOL, $e->getTraceAsString()));
}