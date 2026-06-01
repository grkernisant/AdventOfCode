package com.webnologies.grkernisant.aoc.aoc2024.day16

enum class Tile (val c: Char) {
    FLOOR('.'),
    END('E'),
    START('S'),
    WALL('#');

    companion object {
        fun of(c: Char): Tile =
            Tile.entries.find { it.c == c } ?: throw IllegalArgumentException("Cannot convert $c to a MazeTile")
    }
}