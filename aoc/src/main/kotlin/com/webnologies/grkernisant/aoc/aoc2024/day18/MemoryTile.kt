package com.webnologies.grkernisant.aoc.aoc2024.day18

data class MemoryTile (
    var mt: MemoryType,
    val neighbors: MutableList<Coords> = mutableListOf(),
    var score: Int = -1,
)