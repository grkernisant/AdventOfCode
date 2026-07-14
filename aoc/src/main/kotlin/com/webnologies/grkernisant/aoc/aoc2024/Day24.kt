package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day24.GateOperator
import com.webnologies.grkernisant.aoc.aoc2024.day24.MonitoringDevice
import com.webnologies.grkernisant.aoc.aoc2024.day24.Parser

object Day24 : Day {
    override fun part1(input: List<String>): Any {
        val parser = Parser(input)
        val md = MonitoringDevice.of(parser)
        md.run()
        return md.getSystemOutput()
    }

    override fun part2(input: List<String>): Any {
        val parser = Parser(input, mapOf(
            "z05" to "hdt",
            "z09" to "gbf",
            "mht" to "jgt",
            "z30" to "nbf"
        ))
        val md = MonitoringDevice.of(parser)
        md.run()
        val swaps = if (md.getSystemOutput() == md.getSumOfXY().second) {
            parser.getSortedSwaps()
        } else ""

        return swaps
    }
}