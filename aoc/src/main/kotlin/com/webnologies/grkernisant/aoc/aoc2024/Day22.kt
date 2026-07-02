package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day22.GoodHidingSpot
import com.webnologies.grkernisant.aoc.aoc2024.day22.Parser
import com.webnologies.grkernisant.aoc.aoc2024.day22.SpotManager

object Day22 : Day {
    override fun part1(input: List<String>): Any {
        val parser = Parser.of(input)
        return parser.getTotalAfter(2000)
    }

    override fun part2(input: List<String>): Any {
        val parser = Parser.of(input)
        val spots = List(parser.seeds.size) { index ->
            GoodHidingSpot(parser.seeds[index], 2000)
        }
        val sm = SpotManager(spots)
        return sm.bestSequence.second
    }
}