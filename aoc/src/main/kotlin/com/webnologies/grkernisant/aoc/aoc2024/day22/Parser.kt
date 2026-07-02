package com.webnologies.grkernisant.aoc.aoc2024.day22

data class Parser(val seeds: List<Long>) {
    fun getTotalAfter(size: Int): Long {
        return seeds.fold(0L) { acc, curr ->
            val sg = SequenceGenerator(curr)
            val newAcc = acc + sg.nextSequenceAfter(size)
            newAcc
        }
    }

    companion object {
        fun of(input: List<String>): Parser {
            val seeds = input.map { s -> s.toLong() }
            return Parser(seeds)
        }
    }
}
