package com.webnologies.grkernisant.aoc.aoc2024.day16

interface ParserInterface {
    fun fetchCols(): Int
    fun fetchEnd(): Position
    fun fetchMazeTile(): List<List<MazeTile>>
    fun fetchStart(): Position
    fun fetchRows(): Int
}