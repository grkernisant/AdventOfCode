package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day20.CheatingCodeRule
import com.webnologies.grkernisant.aoc.aoc2024.day20.Parser
import com.webnologies.grkernisant.aoc.aoc2024.day20.RaceTrack

object Day20 : Day {
    override fun part1(input: List<String>): Any {
        val parser = Parser(input)
        val racetrack = RaceTrack.of(parser)
        racetrack.run()
        return racetrack.getCheatCountOver(100)
    }

    override fun part2(input: List<String>): Any {
        val parser = Parser(input)
        val racetrack = RaceTrack.of(parser)
        racetrack.run(CheatingCodeRule.TWENTY_PICO_SEC)
        return racetrack.getCheatCountOver(100)
    }
}