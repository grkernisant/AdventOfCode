package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day21.Parser
import com.webnologies.grkernisant.aoc.aoc2024.day21.Ship

object Day21 : Day {
    override fun part1(input: List<String>): Any {
        val parser = Parser(input)
        val ship = Ship.of(parser)
        ship.init(3)
        return ship.getComplexitySum()
    }

    override fun part2(input: List<String>): Any {
        return 0
    }
}