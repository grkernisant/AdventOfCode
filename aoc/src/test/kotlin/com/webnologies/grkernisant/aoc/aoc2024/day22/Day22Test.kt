package com.webnologies.grkernisant.aoc.aoc2024.day22

import com.webnologies.grkernisant.aoc.aoc2024.Day22
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day22Test {
    val mockInput = Day22.readInput()

    @Test
    @DisplayName("mixes correctly")
    fun mixesCorrectly() {
        val sg = SequenceGenerator(42L)
        sg.mix(15L)
        Assertions.assertEquals(37L, sg.seed, "Expected 37 but found ${sg.seed}")
    }

    @Test
    @DisplayName("prunes correctly")
    fun prunesCorrectly() {
        val sg = SequenceGenerator(100000000L)
        sg.prune()
        Assertions.assertEquals(16113920L, sg.seed, "Expected 16113920 but found ${sg.seed}")
    }

    @Test
    @DisplayName("Generates next 10 pseudoRandom numbers")
    fun generatesNext10PseudoRandom() {
        val sg = SequenceGenerator(123L)
        val expectedResults = listOf(
            15887950L,
            16495136L,
            527345L,
            704524L,
            1553684L,
            12683156L,
            11100544L,
            12249484L,
            7753432L,
            5908254L,
        )

        expectedResults.forEachIndexed { i, result ->
            Assertions.assertEquals(result, sg.nextSequence())

            val localSg = SequenceGenerator(123L)
            Assertions.assertEquals(result, localSg.nextSequenceAfter(i + 1))
        }
    }

    @Test
    @DisplayName("Parses input and get 2000 pseudoRandom numbers for each input")
    fun parsesInputAndGetPseudoRandomAfter2000Iterations() {
        val expectedResults = mapOf(
            1L to 8685429L,
            10L to 4700978L,
            100L to 15273692L,
            2024L to 8667524L
        )

        var sum = 0L
        expectedResults.forEach { (seed, pseudoRandom) ->
            val sg = SequenceGenerator(seed)
            val pseudoSum = sg.nextSequenceAfter(2000)
            sum+= pseudoSum
            Assertions.assertEquals(pseudoRandom, pseudoSum)
        }

        Assertions.assertEquals(37327623L, sum)

        val p = Parser.of(mockInput)
        Assertions.assertEquals(37327623L, p.getTotalAfter(2000))
    }

    @Test
    @DisplayName("Find best sequence to sell")
    fun findMostProfitableSequence() {
        val parser = Parser.of(listOf("1", "2", "3", "2024"))
        val spots = List(parser.seeds.size) { index ->
            GoodHidingSpot(parser.seeds[index])
        }
        val sm = SpotManager(spots)

        Assertions.assertEquals("-2,1,-1,3", sm.bestSequence.first)
        Assertions.assertEquals(23, sm.bestSequence.second)
    }
}