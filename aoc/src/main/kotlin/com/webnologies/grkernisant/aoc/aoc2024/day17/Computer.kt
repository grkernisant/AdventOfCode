package com.webnologies.grkernisant.aoc.aoc2024.day17

import kotlin.collections.fold
import kotlin.math.log2
import kotlin.math.pow

data class Computer (
    val buffer: MutableList<String> = mutableListOf(),
    var pointer: Int = 0,
    override val program: List<Instruction>,
    override val programStr: String,
    override val registers: RegisterMap,
    var palindromes: MutableMap<Int, MutableSet<ULong>> = mutableMapOf()
) : ComputerInterface {
    fun bitwiseDivision (nominator: ULong, denominator: ULong): ULong {
        val bitCount = log2(denominator.toDouble()).toInt()
        return nominator.shr(bitCount)
    }

    fun bitwiseXor(pi: ParsedInstruction) {
        val xorResult = pi.operands.first.getSafeValue().xor(pi.operands.second.getSafeValue())
        this.storeResult(pi.opcode.registerName, xorResult)
    }

    fun clear() {
        this.buffer.clear()
        this.pointer = 0
    }

    fun comboOperandCheck(c: ComboOperand) {
        if (c.reserved) throw IllegalArgumentException("Reserved operands cannot be used in program")

        if(c.registerName != null && !registers.contains(c.registerName)) {
            throw IllegalArgumentException("Unknown Register ${c.registerName}")
        }
    }

    fun fetchSortedPalindromes(): Map<Int, List<ULong>> {
        val keys = palindromes.keys.sorted()
        val sortedPalindromes = keys.fold(mutableMapOf<Int, List<ULong>>()) { acc, curr ->
            acc[curr] = palindromes[curr]!!.sorted()
            acc
        }

        return sortedPalindromes.toMap()
    }

    fun findMatchingProgramOutput(minSize: Int = 2 * program.size, maxSize: Int = 2 * program.size) {
        palindromes.clear()
        this.findMatchingProgramOutputWith(
            regA = 0UL,
            currentSize = 1,
            sizeBoundaries = Pair(minSize, maxSize),
            compareTo = programStr.replace(",", "")
        )
    }

    fun findMatchingProgramOutputWith(
        regA: ULong,
        currentSize: Int,
        sizeBoundaries: Pair<Int, Int>,
        compareTo: String
    ) {
        for(i in 0..7) {
            val registerA = regA + i.toULong()
            if (registerA == 0UL)  continue

            val raspberryPi = this.copy(
                buffer = mutableListOf<String>(),
                pointer = 0,
                registers = mapOf(
                    "A" to Operand(0UL),
                    "B" to Operand(0UL),
                    "C" to Operand(0UL),
                )
            )
            raspberryPi.storeResult("A", registerA)
            raspberryPi.runProgram()
            // start at the output end and backtrack
            val currentOutput = raspberryPi.getOutputBuffer("")
            val matchesEnd = currentOutput.length < sizeBoundaries.second && compareTo.endsWith(currentOutput)
            val matchesAll = compareTo == currentOutput
            if ((matchesEnd || matchesAll) && currentOutput.length >= currentSize) {
                if (currentOutput.length in sizeBoundaries.first..sizeBoundaries.second) {
                    if (!palindromes.contains(currentOutput.length)) palindromes[currentOutput.length] = mutableSetOf()
                    palindromes[currentOutput.length]!!.add(registerA)
                }
                val nextSize = currentOutput.length + 1
                if (nextSize > sizeBoundaries.second) continue

                findMatchingProgramOutputWith(
                    regA = 8UL * registerA,
                    currentSize = nextSize,
                    sizeBoundaries,
                    compareTo
                )
            }
        }
    }

    fun getComboOperandValue(c: ComboOperand, asLiteral: Boolean = false): ULong? {
        if (!asLiteral) this.comboOperandCheck(c)

        if(c.registerName != null && registers.contains(c.registerName) && !asLiteral) {
            return registers[c.registerName]?.value
        }

        return c.literal.toULong()
    }

    fun getOutputBuffer(delimiter: String = ","): String {
        return buffer.joinToString(delimiter)
    }

    fun getParsedInstruction(index: Int): ParsedInstruction {
        val instr = program[index]
        return when (instr.opcode) {
            Opcode.ADV,
            Opcode.BDV,
            Opcode.CDV -> {
                val numerator = getRegisterValue("A")
                val combo = this.getComboOperandValue(instr.combo) ?: throw IllegalArgumentException("Cannot divide by null or zero")
                val denominator = 2.0.pow(combo.toDouble()).toULong()
                ParsedInstruction(
                    instr.opcode,
                    Pair(
                        Operand(numerator),
                        Operand(denominator)
                    )
                )
            }

            Opcode.BXL -> {
                val regB = getRegisterValue("B")
                val literal = this.getComboOperandValue(instr.combo, asLiteral = true) ?: throw IllegalStateException("Literal not found")
                ParsedInstruction(
                    instr.opcode,
                    Pair(
                        Operand(regB),
                        Operand(literal)
                    )
                )
            }

            Opcode.BST,
            Opcode.OUT -> {
                val combo = this.getComboOperandValue(instr.combo) ?: throw IllegalArgumentException("Missing operand ${instr.combo}")
                ParsedInstruction(
                    instr.opcode,
                    Pair(
                        Operand(combo),
                        Operand(8UL)
                    )
                )
            }

            Opcode.JNZ -> {
                val regA = getRegisterValue("A")
                val literal = instr.combo.literal / 2
                ParsedInstruction(
                    instr.opcode,
                    Pair(
                        Operand(regA),
                        Operand(literal.toULong())
                    )
                )
            }

            Opcode.BXC -> {
                val regB = getRegisterValue("B")
                val regC = getRegisterValue("C")
                ParsedInstruction(
                    instr.opcode,
                    Pair(
                        Operand(regB),
                        Operand(regC)
                    )
                )
            }
        }
    }

    fun getRegisterValue(registerName: String): ULong {
        if (!registers.contains(registerName)) throw IllegalArgumentException("Register $registerName not found")

        return registers[registerName]?.value ?: 0UL
    }

    fun runProgram() {
        this.clear()
        val pz = program.size
        while (pointer < pz) {
            val pi = getParsedInstruction(pointer)
            when(pi.opcode) {
                Opcode.ADV, Opcode.BDV, Opcode.CDV -> {
                    val nominator = pi.operands.first.getSafeValue()
                    val denominator = pi.operands.second.getSafeValue()
                    if (denominator == 0UL) throw IllegalArgumentException("Cannot divide by zero")
                    val divisionResult = bitwiseDivision(nominator, denominator)

                    this.storeResult(pi.opcode.registerName, divisionResult)
                }

                Opcode.BXL,
                Opcode.BXC -> {
                    this.bitwiseXor(pi)
                }

                Opcode.BST,
                Opcode.OUT -> {
                    // perf: Bitwise AND faster than modulo for powers of 2 (https://en.wikipedia.org/wiki/Modulo)
                    // val mod8Result = pi.operands.first.getSafeValue() % pi.operands.second.getSafeValue()
                    val mod8Result = pi.operands.first.getSafeValue().and(pi.operands.second.getSafeValue() - 1UL)
                    if (pi.opcode.registerName != null) {
                        this.storeResult(pi.opcode.registerName, mod8Result)
                    }

                    if (pi.opcode == Opcode.OUT) {
                        this.buffer.add(mod8Result.toString())
                    }
                }

                Opcode.JNZ -> {
                    val regA = pi.operands.first.getSafeValue()
                    if (regA != 0UL) {
                        // -1 due to turn increment
                        pointer = (pi.operands.second.getSafeValue() - 1UL).toInt()
                    }
                }
            }

            pointer++
        }
    }

    fun storeResult(registerName: String? = null, result: ULong? = null) {
        if (registerName != null && registers.contains(registerName) && result != null) {
            registers[registerName]!!.value = result
        }
    }

    companion object {
        fun of(ci: ComputerInterface): Computer = Computer(
            program = ci.program,
            programStr = ci.programStr,
            registers = ci.registers
        )
    }
}
