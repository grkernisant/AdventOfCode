package com.webnologies.grkernisant.aoc.aoc2024.day24

enum class GateOperator {
    AND,
    OR,
    XOR;

    companion object {
        fun of(op: String): GateOperator {
            return when (op) {
                "AND" -> AND
                "OR" -> OR
                "XOR" -> XOR
                else -> throw IllegalArgumentException("Unknown operator: $op")
            }
        }
    }
}