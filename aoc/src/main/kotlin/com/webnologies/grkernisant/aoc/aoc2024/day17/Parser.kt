package com.webnologies.grkernisant.aoc.aoc2024.day17

class Parser : ComputerInterface {
    override val registers: Map<String, Operand> = mapOf(
        "A" to Operand(0UL),
        "B" to Operand(0UL),
        "C" to Operand(0UL),
    )
    override lateinit var program: List<Instruction>
    override lateinit var programStr: String

    constructor(input: List<String>) {
        input.forEach { line ->
            val matchesRegister = REGISTER_REGEX.matchEntire(line.trim())
            if (matchesRegister != null) {
                this.registers[matchesRegister.groupValues[1]]!!.value = matchesRegister.groupValues[2].toULong()
            }

            val matchesProgram = PROGRAM_REGEX.matchEntire(line.trim())
            if (matchesProgram != null) {
                this.programStr = matchesProgram.groupValues[1].trim()
                this.program = this.programStr
                    .split(",")
                    .filter { it.isNotBlank() }
                    .chunked(2)
                    .map { chunk ->
                        if (chunk.size == 1) throw IllegalArgumentException("Corrupted program: $chunk missing operand")

                        Instruction(
                            Opcode.of(chunk[0].toInt()),
                            ComboOperand.of(chunk[1].toInt())
                        )
                    }
            }
        }
    }

    companion object {
        val PROGRAM_REGEX = Regex("^Program: ((?:(\\d),?)+)$")
    }
}
