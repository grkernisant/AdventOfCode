package com.webnologies.grkernisant.aoc.aoc2024.day19

import com.webnologies.grkernisant.aoc.aoc2024.day19.TowelDesign.Companion.TOWEL_DESIGN_REGEX
import com.webnologies.grkernisant.aoc.aoc2024.day19.TowelPattern.Companion.TOWEL_PATTERN_REGEX

class Parser : OnsenInterface {
    override lateinit var patterns: List<TowelPattern>
    override var towels: List<TowelDesign>

    constructor(input: List<String>) {
        var t: MutableList<TowelDesign> = mutableListOf()
        input.forEachIndexed { index, line ->
            val matchPatterns = TOWEL_PATTERN_REGEX.matchEntire(line.trim())
            if (matchPatterns != null && index == 0) {
                val blocks = matchPatterns.groupValues[0]
                    .split(", ")
                    .filter { it.isNotBlank() }
                patterns = blocks.map { TowelPattern(pattern = it) }
                patterns.forEach { pattern ->
                    val subblocks = blocks.filter { block ->
                        block.length < pattern.pattern.length
                    }.map { TowelPattern(it) }

                    if (subblocks.isNotEmpty()) {
                        val regex = Regex(pattern.getPatternRegex(subblocks))
                        val matches = regex.matchEntire(pattern.pattern)
                        if (matches != null) {
                            pattern.setBlocks(subblocks.mapNotNull {
                                if (pattern.pattern.indexOf(it.pattern) == -1) return@mapNotNull null
                                it.pattern
                            })
                        }
                    }
                }
            }

            val matchTowelDesigns = TOWEL_DESIGN_REGEX.matchEntire(line.trim())
            if (matchTowelDesigns != null) {
                val currentTowel = TowelDesign(matchTowelDesigns.groupValues[0])
                val regex = Regex(currentTowel.getPatternRegex(patterns))
                if (regex.matches(currentTowel.pattern)) {
                    currentTowel.canDesign = true
                } else {
                    currentTowel.nbCombo = 0L
                }

                t+= currentTowel
            }
        }
        towels = t
    }
}
