package com.webnologies.grkernisant.aoc.aoc2024.day17

import com.webnologies.grkernisant.aoc.aoc2024.Day17
import org.junit.jupiter.api.Test
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Assertions

class Day17Test {
    val mockInput = Day17.readInput()

    @Test
    @DisplayName("Parses the example input correctly")
    fun parsesInputCorrectly() {
        val parser = Parser(mockInput)
        Assertions.assertEquals("729", parser.registers["A"]?.value?.toString())
        Assertions.assertEquals("0", parser.registers["B"]?.value?.toString())
        Assertions.assertEquals("0", parser.registers["C"]?.value?.toString())

        Assertions.assertEquals(3, parser.program.size)
        Assertions.assertEquals(Opcode.ADV, parser.program[0].opcode)
        Assertions.assertEquals(ComboOperand.COMBO_1, parser.program[0].combo)
        Assertions.assertEquals(Opcode.OUT, parser.program[1].opcode)
        Assertions.assertEquals(ComboOperand.COMBO_4, parser.program[1].combo)
        Assertions.assertEquals(Opcode.JNZ, parser.program[2].opcode)
        Assertions.assertEquals(ComboOperand.COMBO_0, parser.program[2].combo)
    }

    @Test
    @DisplayName("Can run the program C=9 2,6 -> B=1")
    fun runProgramRegisterC9setRegisterB1() {
        val parser = Parser("""
            Register C: 9
            
            Program: 2,6
        """.trimIndent().split("\n"))
        Assertions.assertEquals("9", parser.registers["C"]?.value?.toString())
        Assertions.assertEquals(1, parser.program.size)
        Assertions.assertEquals(Opcode.BST, parser.program[0].opcode)
        Assertions.assertEquals(ComboOperand.COMBO_6, parser.program[0].combo)

        val linux = Computer.of(parser)
        Assertions.assertEquals("9", linux.getRegisterValue("C").toString())
        Assertions.assertEquals(1, linux.program.size)
        Assertions.assertEquals(Opcode.BST, linux.program[0].opcode)
        Assertions.assertEquals(ComboOperand.COMBO_6, linux.program[0].combo)

        linux.runProgram()
        Assertions.assertEquals(1UL, linux.getRegisterValue("B"))
    }

    @Test
    @DisplayName("Can run the program A=10 5,0,5,1,5,4 -> OUT=0,1,2")
    fun runProgramRegisterA10toOutputBuffer() {
        val parser = Parser("""
            Register A: 10
            
            Program: 5,0,5,1,5,4
        """.trimIndent().split("\n"))

        val linux = Computer.of(parser)
        Assertions.assertEquals("10", parser.registers["A"]?.value?.toString())
        Assertions.assertEquals(3, parser.program.size)

        linux.runProgram()
        Assertions.assertEquals("0,1,2", linux.getOutputBuffer())
    }

    @Test
    @DisplayName("Can run the program B=29 1,7 -> B=26")
    fun runProgramRegisterB29Program17RegisterB26() {
        val parser = Parser("""
            Register B: 29
            
            Program: 1,7
        """.trimIndent().split("\n"))

        val linux = Computer.of(parser)
        Assertions.assertEquals("29", parser.registers["B"]?.value?.toString())
        Assertions.assertEquals(1, parser.program.size)

        linux.runProgram()
        Assertions.assertEquals("26", linux.getRegisterValue("B").toString())
    }

    @Test
    @DisplayName("Can run the program B=2024 C=43690 4,0 -> B=44354")
    fun runProgramRegisterB2024Register43690() {
        val parser = Parser("""
            Register B: 2024
            Register C: 43690

            Program: 4,0
        """.trimIndent().split("\n"))

        val linux = Computer.of(parser)
        linux.runProgram()
        Assertions.assertEquals(44354UL, linux.getRegisterValue("B"))
    }

    @Test
    @DisplayName("Runs the example input correctly")
    fun runInputAndOutputsCorrectly() {
        val linux = Computer.of(Parser(mockInput))
        linux.runProgram()
        Assertions.assertEquals("4,6,3,5,6,3,5,2,1,0", linux.getOutputBuffer())
    }

    @Test
    @DisplayName("Can find the program palindrome short input")
    fun findPalindromeShortInput() {
        val input = """
            Register A: 2024
            Register B: 0
            Register C: 0
    
            Program: 0,3,5,4,3,0
        """.trimIndent().split("\n")
        val parser = Parser(input)
        val linux = Computer.of(parser)
        Assertions.assertEquals(2024UL, linux.getRegisterValue("A"))
        Assertions.assertEquals(0UL, linux.getRegisterValue("B"))
        Assertions.assertEquals(0UL, linux.getRegisterValue("C"))
        Assertions.assertEquals(3, linux.program.size)

        linux.findMatchingProgramOutput()
        val sortedPalindromes = linux.fetchSortedPalindromes()
        val regA = sortedPalindromes[2 * linux.program.size]?.first() ?: 0UL
        linux.storeResult("A", regA)
        linux.runProgram()
        Assertions.assertEquals(linux.programStr, linux.getOutputBuffer())
    }

    @Test
    @DisplayName("Can find the program palindrome for a larger input")
    fun findPalindromeLargeInput() {
        val parser = Parser("""
            Register A: 38610541
            Register B: 0
            Register C: 0

            Program: 2,4,1,1,7,5,1,5,4,3,5,5,0,3,3,0
        """.trimIndent().split("\n"))

        val linux = Computer.of(parser)
        linux.findMatchingProgramOutput()
        val sortedPalindromes = linux.fetchSortedPalindromes()
        val regA = sortedPalindromes[2 * linux.program.size]?.first() ?: 0UL
        linux.storeResult("A", regA)
        linux.runProgram()
        Assertions.assertEquals(linux.programStr, linux.getOutputBuffer())
    }
}