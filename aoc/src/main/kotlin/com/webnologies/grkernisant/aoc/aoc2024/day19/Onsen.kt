package com.webnologies.grkernisant.aoc.aoc2024.day19

import kotlinx.serialization.json.Json

data class Onsen (
    override val patterns: List<TowelPattern>,
    override val towels: List<TowelDesign>,
) : OnsenInterface {
    fun getCombinationCount(): ULong {
        if (towels.isEmpty()) return 0UL

        var towelIndex: Int = 1
        val count = towels.fold(0UL) { acc, towel ->
            if (!towel.canDesign) return@fold acc + 0UL

            val patternsFiltered = patterns
                .mapNotNull { p ->
                    if (towel.pattern.indexOf(p.pattern) == -1) return@mapNotNull null
                    p
                }.sortedByDescending { it.pattern.length }
            towel.nbCombo = 0L
            towel.blocks.clear()
            towel.blocks.addAll(patternsFiltered.map { it.pattern })
            towel.countSets(
                towelPatterns = patternsFiltered,
                pattern = towel.pattern,
                currentSets = 1,
            )
            val newAcc = acc + towel.nbCombo.toULong()
            newAcc
        }

        return count
    }

    fun getPossiblePatternsCount(): Int {
        if (towels.isEmpty()) return 0

        val regex = Regex(towels.first().getPatternRegex(patterns))
        val count = towels.fold(0) { acc, towel ->
            val newAcc = if (regex.matches(towel.pattern)) acc + 1 else acc
            newAcc
        }

        return count
    }

    companion object {
        fun of(p: OnsenInterface): Onsen =
            Onsen(p.patterns, p.towels)
    }
}
