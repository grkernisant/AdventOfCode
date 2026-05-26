package com.webnologies.grkernisant.aoc.aoc2024.day15

enum class RobotMove (
    val char: Char,
    val offset: List<Int>,
) {
    UP(char = '^', offset = listOf(0, -1)),
    RIGHT(char = '>', offset = listOf(1, 0)),
    DOWN(char = 'v', offset = listOf(0, 1)),
    LEFT(char = '<', offset = listOf(-1, 0)),;

    companion object {
        val OFFSETS = entries.associateBy(RobotMove::char)
        val REGEX = Regex("^[<v>^]+$")

        fun of(c: Char): RobotMove = entries.find { it.char == c } ?: throw IllegalArgumentException("Unknown robot move: $c")
    }
}