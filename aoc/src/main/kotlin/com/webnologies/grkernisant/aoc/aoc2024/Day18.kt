package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day18.MemorySpace
import com.webnologies.grkernisant.aoc.aoc2024.day18.Parser
import kotlin.text.toInt

object Day18 : Day {
    override fun part1(input: List<String>): Any {
        val env = readEnv()
        val parser = Parser(input, env)
        val fallenBytes = env["FALLEN_BYTES"]?.toInt() ?: 0
        val ms = MemorySpace.of(parser)
        val escapeRoutes = ms.getEscapeRoute(fallenBytes)
        val nbSteps = if (escapeRoutes.isNotEmpty()) escapeRoutes.first().size - 1 else 0
        return nbSteps
    }

    override fun part2(input: List<String>): Any {
        val env = readEnv()
        val parser = Parser(input, env)
        val fallenBytes = env["FALLEN_BYTES"]?.toInt() ?: 0
        val ms = MemorySpace.of(parser)
        val blockingBytes = ms.findBlockingEscapeTile(fallenBytes)
        return blockingBytes
    }
}