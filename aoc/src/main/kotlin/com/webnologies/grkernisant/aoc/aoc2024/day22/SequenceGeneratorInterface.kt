package com.webnologies.grkernisant.aoc.aoc2024.day22

interface SequenceGeneratorInterface {
    var seed: Long
    val pruner: Long

    fun divide(operand: Long): Long
    fun multi(operand: Long): Long
    fun mix(operand: Long)
    fun prune()

    fun nextSequence(): Long
    fun nextSequenceAfter(size: Int): Long
}