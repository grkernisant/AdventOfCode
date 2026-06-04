package com.webnologies.grkernisant.aoc.aoc2024.day17

data class ParsedInstruction(
    val opcode: Opcode,
    val operands: Pair<Operand, Operand>
)
