package com.webnologies.grkernisant.aoc.aoc2024.day16

import kotlin.math.abs

enum class Direction(
    val facing: Char,
    val offset: Pair<Int, Int>
) {
    LEFT('<', Pair(-1, 0)),
    UP('^', Pair(0, -1)),
    RIGHT('>', Pair(1, 0)),
    DOWN('v', Pair(0, 1));

    fun getNextDirection(delta: Int = 0): Direction {
        val nb = Direction.entries.size
        val nextOffset = (nb + Direction.entries.indexOf(this) + delta) % nb
        return Direction.entries[nextOffset]
    }

    companion object {
        fun of(facing: Char): Direction =
            Direction.entries.find { it.facing == facing } ?: throw IllegalArgumentException("Unknown facing: $facing")
    }
}