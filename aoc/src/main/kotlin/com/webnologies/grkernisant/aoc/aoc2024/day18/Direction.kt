package com.webnologies.grkernisant.aoc.aoc2024.day18

enum class Direction(val offset: List<Int>) {
    RIGHT(listOf(1, 0)),
    DOWN(listOf(0, 1)),
    LEFT(listOf(-1, 0)),
    UP(listOf(0, -1));
}