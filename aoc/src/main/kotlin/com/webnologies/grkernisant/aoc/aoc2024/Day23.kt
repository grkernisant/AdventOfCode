package com.webnologies.grkernisant.aoc.aoc2024

import com.webnologies.grkernisant.aoc.aoc2024.day23.LanParty

object Day23 : Day {
    override fun part1(input: List<String>): Any {
        val lanParty = LanParty(input, false)
        val result = lanParty.getLANofSize(3)
        val startsWithT = lanParty.filterContainsNamesStartWith(result, "t")
        return startsWithT.size
    }

    override fun part2(input: List<String>): Any {
        val lanParty = LanParty(input, false)
        val pwd = lanParty.getLongestPassword()
        return pwd
    }
}