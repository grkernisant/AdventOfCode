package com.webnologies.grkernisant.aoc.aoc2024.day24

import com.webnologies.grkernisant.aoc.aoc2024.Day24
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day24Test {
    val smallInput = """
            x00: 1
            x01: 1
            x02: 1
            y00: 0
            y01: 1
            y02: 0

            x00 AND y00 -> z00
            x01 XOR y01 -> z01
            x02 OR y02 -> z02
        """.trimIndent()
    val smallInputList = smallInput
        .split('\n')
        .filter { it.isNotBlank() }
    val mockInput = Day24.readInput()

    @Test
    @DisplayName("parses a small input correctly")
    fun parseSmallInput() {
        val parser = Parser(smallInputList)
        val expectedInputs = mapOf(
            "x00" to 1,
            "x01" to 1,
            "x02" to 1,
            "y00" to 0,
            "y01" to 1,
            "y02" to 0,
        )
        expectedInputs.forEach { (input, expected) ->
            Assertions.assertEquals(expected, parser.systemInit[input])
        }

        val md = MonitoringDevice.of(parser)
        md.run()
        Assertions.assertEquals(4L, md.getSystemOutput())
    }

    @Test
    @DisplayName("parses example input correctly")
    fun parseInput() {
        val parser = Parser(mockInput)
        val md = MonitoringDevice.of(parser)
        md.run()
        Assertions.assertEquals(2024L, md.getSystemOutput())
    }

    @Test
    @DisplayName("analyze and correct output errors")
    fun analyzeOutputs() {
        val largeInput = Day24.readInput("with46Gates")
        val parser = Parser(largeInput, mapOf(
            "z05" to "hdt",
            "z09" to "gbf",
            "mht" to "jgt",
            "z30" to "nbf"
        ))
        val md = MonitoringDevice.of(parser)
        md.run()
        val ww = md.findWrongWires()
        ww.forEach {
            println(md.getFullEquationFor(it))
        }

        Assertions.assertTrue(ww.isEmpty())
        Assertions.assertEquals(md.getSystemOutput(), md.getSumOfXY().second)
    }
}