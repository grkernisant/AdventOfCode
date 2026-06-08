package com.webnologies.grkernisant.aoc.aoc2024.day19

interface PatternInterface {
    val pattern: String
    val blocks: MutableSet<String>
    var nbCombo: Int
    val sets: MutableSet<String>

    fun buildSets(blocks: List<String>, pattern: String, currentSets: List<MutableSet<String>>) {
        if (pattern.isEmpty()) {
            currentSets.forEach { cs ->
                this.sets += cs.joinToString(",")
            }
            // patterns count as 1 set
            // where as towels do not
            val offset  = if(this.javaClass.simpleName == "TowelPattern") 1 else 0
            nbCombo = offset + this.sets.size
            return
        }

        blocks.forEach { block ->
            val regex = Regex("^($block)")
            val matches = regex.find(pattern)
            if (matches != null) {
                val newPattern = pattern.substring(matches.groupValues[1].length)
                val nextSets = currentSets.fold(currentSets) { acc, curr ->
                    val newAcc = acc.map{ a ->
                        (a + matches.groupValues[1]).toMutableSet()
                    }
                    newAcc
                }
                buildSets(blocks, newPattern, nextSets)
            }
        }
    }

    fun countSets(towelPatterns: List<TowelPattern>, pattern: String, currentSets: Int) {
        if (currentSets == 0) {
            nbCombo = 0
            return
        }

        if (pattern.isEmpty()) {
            nbCombo+= currentSets
            return
        }

        val regex = Regex(getPatternRegex(towelPatterns))
        val matchedPatterns: MutableList<TowelPattern> = mutableListOf()
        towelPatterns.forEach  { towelP ->
            val regexPattern = Regex("^(${towelP.pattern})")
            val matches = regexPattern.find(pattern)
            if (matches != null) {
                val newPattern = pattern.substring(matches.groupValues[1].length)
                if (newPattern.isEmpty() || regex.matchEntire(newPattern) != null) {
                    matchedPatterns.add(towelP)
                }
            }
        }

        matchedPatterns.forEach { matchedP ->
            val newPattern = pattern.substring(matchedP.pattern.length)
            val nbSets = if (newPattern.isNotEmpty()) currentSets * matchedPatterns.size else 1
            countSets(towelPatterns, newPattern, nbSets)
        }
    }

    fun setBlocks(b: List<String>) {
        this.blocks.addAll(b)
        this.buildSets(b, pattern, listOf(mutableSetOf()))
    }

    fun getPatternRegex(p: List<TowelPattern>): String {
        return "^(${p.joinToString("|") { it.pattern }})+$"
    }
}