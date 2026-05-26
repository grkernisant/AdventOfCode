package com.webnologies.grkernisant.aoc.aoc2024.day15

enum class MapTile (
    val char: Char,
    val area: MapTileArea = MapTileArea(1, 1)
    ){
    BOX('O'),
    BOX_2X_WIDE_LEFT('[', MapTileArea(2, 1)),
    BOX_2X_WIDE_RIGHT(']', MapTileArea(2, 1)),
    FLOOR('.'),
    ROBOT('@'),
    WALL('#');

    companion object {
        val REGEX = Regex("^[#.O@]+$")

        fun of(c: Char): MapTile = entries.find { it.char == c } ?: throw IllegalArgumentException("Unknown map tile $c")
    }
}