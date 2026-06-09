package com.webnologies.grkernisant.aoc.aoc2024.day20

enum class MapTile (val c: Char) {
    FLOOR('.'),
    START('S'),
    END('E'),
    WALL('#');

    companion object {
        fun of(c: Char): MapTile =
            MapTile.entries.find { c == it.c } ?: throw IllegalArgumentException("$c is not a valid MapTile")
    }
}