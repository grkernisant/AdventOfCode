package com.webnologies.grkernisant.aoc.aoc2024.day17

enum class Opcode(val opcode: Int, val registerName: String? = null) {
    ADV(0, registerName = "A"),
    BXL(1, registerName = "B"),
    BST(2, registerName = "B"),
    JNZ(3),
    BXC(4, registerName = "B"),
    OUT(5),
    BDV(6, registerName = "B"),
    CDV(7, registerName = "C");

    companion object {
        fun of(opcode: Int): Opcode =
            Opcode.entries.find { it.opcode == opcode } ?: throw IllegalArgumentException("Invalid opcode $opcode")
    }
}