package com.webnologies.grkernisant.aoc.aoc2024.day22

class SpotManager {
    val sequenceCounter: MutableMap<String, Int> = mutableMapOf()
    val spots: List<GoodHidingSpot>
    val bestSequence: Pair<String, Int>

    constructor(spots: List<GoodHidingSpot>) {
        this.spots = spots
        spots.forEach { spot ->
            spot.priceChanges.forEach { (k, values) ->
                if (!sequenceCounter.contains(k)) sequenceCounter[k] = 0
                sequenceCounter[k] = (sequenceCounter[k] ?: 0) + values.first().nbBananas
            }
        }

        val maxBananas = sequenceCounter.values.max()
        var maxIndex = sequenceCounter.entries.toList().indexOfFirst { it.value == maxBananas }
        bestSequence = Pair(sequenceCounter.entries.toList()[maxIndex].key, maxBananas)
    }
}
