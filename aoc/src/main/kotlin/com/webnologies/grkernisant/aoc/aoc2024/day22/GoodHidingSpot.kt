package com.webnologies.grkernisant.aoc.aoc2024.day22

class GoodHidingSpot {
    var sg: SequenceGenerator
    val prices: MutableList<Int> = mutableListOf()
    val diff: MutableList<Int> = mutableListOf()
    val priceChanges: MutableMap<String, MutableList<PriceChange>> = mutableMapOf()

    constructor(seed: Long, iteration: Int = 2000) {
        this.sg = SequenceGenerator(seed)
        prices.add(getPrice())

        for(i in 0..iteration) {
            sg.nextSequence()
            val price = getPrice()
            prices.add(price)
            diff.add(price - prices[i])

            if (i >= 3) {
                val deltas = diff.subList(i - 3, i + 1).toList()
                val priceChange = PriceChange(deltas, price, i)
                addPriceChange(priceChange)
            }
        }

        priceChanges.forEach { (key, pcs) ->
            priceChanges[key]
                ?.sortedByDescending { it.nbBananas }
                ?.sortedBy { it.time }
        }
    }

    fun addPriceChange(pc: PriceChange) {
        val key = pc.toKey()
        if (!priceChanges.contains(key)) priceChanges[key] = mutableListOf()

        priceChanges[key]?.add(pc)
    }

    fun getPrice(): Int = (sg.seed % 10).toInt()
}