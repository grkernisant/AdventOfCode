package com.webnologies.grkernisant.aoc.aoc2024.day22

data class SequenceGenerator (
    override var seed: Long,
    override val pruner: Long = 16777216L,
) : SequenceGeneratorInterface {
    override fun divide(operand: Long): Long = seed / operand

    override fun multi(operand: Long): Long = seed * operand

    override fun mix(operand: Long) {
        seed = seed.xor(operand)
    }

    override fun prune() {
        seed = seed.and(pruner - 1)
    }

    override fun nextSequence(): Long {
        mix(multi(64))
        prune()

        mix(divide(32))
        prune()

        mix(multi(2048))
        prune()

        return seed
    }

    override fun nextSequenceAfter(size: Int): Long {
        if (size < 1) return seed

        var result: Long = seed
        for (n in 0..<size) {
            result = nextSequence()
        }

        return result
    }
}
