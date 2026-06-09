package com.webnologies.grkernisant.aoc.aoc2024.day20

import com.webnologies.grkernisant.aoc.aoc2024.Day20
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day20Test {
    val mockInput = Day20.readInput()

    @Test
    @DisplayName("parses an example input correctly")
    fun parsesExampleInput() {
        val parser = Parser(mockInput)
        val rt = RaceTrack.of(parser)

        Assertions.assertEquals(15, rt.cols)
        Assertions.assertEquals(15, rt.rows)
        Assertions.assertEquals(1, rt.start.x)
        Assertions.assertEquals(3, rt.start.y)
        Assertions.assertEquals(5, rt.end.x)
        Assertions.assertEquals(7, rt.end.y)
    }

    @Test
    @DisplayName("explore race track and sets distances")
    fun exploreRaceTrackAndSetDistance() {
        val parser = Parser(mockInput)
        val rt = RaceTrack.of(parser)
        rt.run()
        Assertions.assertEquals(84, rt.distances[rt.end.toPositionKey()])
    }

    @Test
    @DisplayName("find cheat positions")
    fun trackCheatPositions() {
        val parser = Parser(mockInput)
        val rt = RaceTrack.of(parser)
        rt.run()

        val expectedCheatPositions = mapOf(
            2 to 14,
            4 to 14,
            6 to 2,
            8 to 4,
            10 to 2,
            12 to 3,
            20 to 1,
            36 to 1,
            38 to 1,
            40 to 1,
            64 to 1
        )
        expectedCheatPositions.forEach { (picoSecondsSaved, nbCheats) ->
            Assertions.assertEquals(nbCheats, rt.cheatPositions[picoSecondsSaved]?.size)
        }
    }

    @Test
    @DisplayName("gets circular neighbors from a given position")
    fun getCircularNeighborsFromAPosition() {
        val parser = Parser(mockInput)
        val rt = RaceTrack.of(parser)
        val neighbors = rt.getCircularNeighbors(rt.start, 2..20)
        val distance2 = neighbors.filter { n -> n.distance == 2 }
        Assertions.assertEquals(7, distance2.size)
    }

    @Test
    @DisplayName("finds cheats for new 20 pico rule")
    fun getCheatCodesFor20PicoRule() {
        val parser = Parser(mockInput)
        val rt = RaceTrack.of(parser)
        rt.run(CheatingCodeRule.TWENTY_PICO_SEC)
        val expectedCheatPositions: Map<Int, Int> = mapOf(
            50 to 32,
            52 to 31,
            54 to 29,
            56 to 39,
            58 to 25,
            60 to 23,
            62 to 20,
            64 to 19,
            66 to 12,
            68 to 14,
            70 to 12,
            72 to 22,
            74 to 4,
            76 to 3
        )
        val result: MutableMap<Int, Int> = mutableMapOf()
        rt.cheatPositions.forEach { (key, value) ->
            if (key >= 50) { result[key] = value.size }
        }
        expectedCheatPositions.forEach { (key, _) ->
            Assertions.assertEquals(expectedCheatPositions[key], result[key])
        }
    }
}