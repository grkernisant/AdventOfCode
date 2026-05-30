---
name: pathfinding-state-dedup
description: Debugging Dijkstra/A*-style grid pathfinding where state includes direction — deduplicate by (position, direction), not by full (position, direction, score, prev) to prevent exponential blowup
source: auto-skill
extracted_at: '2026-05-30T02:05:45.268Z'
---

## Problem

A Dijkstra/A*-style pathfinding algorithm on a grid where each step has a **direction** (costs: 1 to move forward, 1001 to turn + move) runs out of memory on large inputs but works on small tests.

## Root cause

The "visited" / "unvisited" sets use a key that includes **score** and/or **previous position** (e.g. `(x,y,dir,score,prev_x_y)`). This creates unique entries for every distinct path to the same `(position, direction)` — exponentially many for large grids.

For a grid with `R` rows, `C` cols, and 4 directions, there are at most `R × C × 4` meaningful states. If score/prev are in the key, you get orders of magnitude more.

## Fix

1. **Add a `BestScore` map** keyed by `(position, direction)` (a string like `"(x,y)dir"`) tracking the best score found so far for each state.
2. **In `addVisit`**: before inserting, check if `bestScore[posDir]` exists and is `≤ current.score`. If so, return early (this path is not better).
3. **In neighbor generation (`getUnvisitedNeighbors` / etc.)**: after the usual bounds/wall/end-score checks, check `bestScore[posDir]` for each candidate neighbor. If a better or equal path already exists for that `(position, direction)`, don't add it.
4. **Clear `BestScore`** in the init/reset method alongside other data structures.

## Key insight

In a grid pathfinding problem with direction-dependent costs, state is `(position, direction)` — not `(position, direction, score, prev)`. Score only matters as the *minimum* for each state. Two paths that reach the same cell facing the same direction with different scores: only the lower-score one can lead to an optimal solution.

## Related bugs to check

- **`outOfBounds` missing `return`** — a function returning a boolean expression without the `return` keyword always yields `undefined` (falsy), so bounds checks silently pass and all invalid tiles are explored.
- **Turn cost estimation in pruning heuristics** — when estimating minimum remaining score, account for at least `1000` per necessary turn (0 if already aligned, 1000 if one turn, 2000 if two). The Manhattan distance alone is insufficient.