package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day15.Parser
import com.webnologies.grkernisant.aoc.aoc2024.day15.Parser2xWide
import com.webnologies.grkernisant.aoc.aoc2024.day15.Warehouse

object Day15 : Day {
    override fun part1(input: List<String>): Any {
        val warehouse = Warehouse.of(Parser(input))
        warehouse.runRobot()

        return warehouse.getGPS()
    }

    override fun part2(input: List<String>): Any {
        val warehouse2x = Warehouse.of(Parser2xWide.of(Parser(input)))
        warehouse2x.runRobot()

        return warehouse2x.getGPS()
    }
}