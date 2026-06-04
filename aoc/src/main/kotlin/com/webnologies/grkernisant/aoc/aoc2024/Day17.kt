package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day17.Computer
import com.webnologies.grkernisant.aoc.aoc2024.day17.Parser

object Day17 : Day {
    override fun part1(input: List<String>): Any {
        val linux = Computer.of(Parser(input))
        linux.runProgram()
        return linux.getOutputBuffer()
    }

    override fun part2(input: List<String>): Any {
        val linux = Computer.of(Parser(input))

        linux.findMatchingProgramOutput()
        val sortedPalindromes = linux.fetchSortedPalindromes()
        return sortedPalindromes[2 * linux.program.size]?.first() ?: 0
    }
}