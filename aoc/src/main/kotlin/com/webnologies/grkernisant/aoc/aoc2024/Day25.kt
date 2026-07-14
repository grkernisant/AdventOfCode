package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day25.Parser

object Day25 : Day {
    override fun part1(input: List<String>): Any {
        val parser = Parser(input)
        return parser.getKeyLockFitCount()
    }

    override fun part2(input: List<String>): Any {
        return 0
    }
}