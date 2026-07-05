package com.webnologies.grkernisant.aoc.aoc2024.day23

import com.webnologies.grkernisant.aoc.aoc2024.Day23
import org.junit.jupiter.api.Assertions
import org.junit.jupiter.api.DisplayName
import org.junit.jupiter.api.Test

class Day23Test {
    val mockInput = Day23.readInput()

    @Test
    @DisplayName("find 12 LANs with 3 PCs and 7 where 1 PC starts with t")
    fun findLANsWith3PCsAndStartsWithT() {
        val expectedLANs = """
            aq,cg,yn
            aq,vc,wq
            co,de,ka
            co,de,ta
            co,ka,ta
            de,ka,ta
            kh,qp,ub
            qp,td,wh
            tb,vc,wq
            tc,td,wh
            td,wh,yn
            ub,vc,wq
        """.trimIndent()
            .split("\n")
            .filter { it.isNotEmpty() }
        val expectedTLANs = """
            co,de,ta
            co,ka,ta
            de,ka,ta
            qp,td,wh
            tb,vc,wq
            tc,td,wh
            td,wh,yn
        """.trimIndent()
            .split("\n")
            .filter { it.isNotEmpty() }
        val input = Day23.readInput()
        val lanParty = LanParty(input, false)
        val result = lanParty.getLANofSize(3)

        Assertions.assertEquals(
            expectedLANs.size,
            result.size,
            "Expected ${expectedLANs.size} lan size instead of ${result.size}"
        )

        expectedLANs.forEach { lan ->
            Assertions.assertTrue(result.contains(lan), "$lan NOT FOUND")
        }

        val startWithLetterT = lanParty.filterContainsNamesStartWith(result, "t")
        Assertions.assertEquals(
            expectedTLANs.size,
            startWithLetterT.size,
            "Expected ${expectedTLANs.size} T-lan size instead of ${result.size}"
        )

        expectedTLANs.forEach { lan ->
            Assertions.assertTrue(
                startWithLetterT.contains(lan),
                "$lan with T NOT FOUND"
            )
        }
    }

    @Test
    @DisplayName("largest All PC connected LAN password is co,de,ka,ta")
    fun findLargestAllConnectedLAN() {
        val input = Day23.readInput()
        val lanParty = LanParty(input, false)
        val pwd = lanParty.getLongestPassword()
        val expectedPW = "co,de,ka,ta"

        Assertions.assertEquals(
            expectedPW,
            pwd,
            "Longest Password does not match $pwd"
        )
    }
}