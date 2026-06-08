package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day19.Parser
import com.webnologies.grkernisant.aoc.aoc2024.day19.Onsen

object Day19 : Day {
    override fun part1(input: List<String>): Any {
        val onsen = Onsen.of(Parser(input))
        return onsen.getPossiblePatternsCount()
    }

    override fun part2(input: List<String>): Any {
        val onsen = Onsen.of(Parser(input))
        return onsen.getCombinationCount()
    }
}