import { countCache, transformCache, Stone } from './ts/types/Stone.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const stoneArrangementContent = await readFileIfExists(input);

// part 1
console.log(`Part 1\nAfter 25 times, we have ${Stone.totalStones(stoneArrangementContent, 25)} stones\n\nPart 2`);

// part 2
// let's cache blink for all possible keys for future look up performance
Array.from(transformCache.keys()).map(stone => {
  const stoneCountCacheKey = Stone.getCountCacheKey(stone, 25);
  countCache.set(stoneCountCacheKey, Stone.totalStones(stone, 25));
});

const iterations = [25, 50, 75];
const lastIteration = iterations[iterations.length-1];
iterations.forEach(it => {
  let totalStones = 0;
  stoneArrangementContent.trim().split(' ').map(ps => {
    const psStoneCountIteration = Stone.totalStones(ps, it);
    if (it === lastIteration) console.log(`After ${it} times, ${ps} transforms into ${psStoneCountIteration} stones`);
    totalStones+= psStoneCountIteration;
  });
  if (it === lastIteration) console.log(`\nafter (${it}) blinks, total stones: ${totalStones}\n\n`)
});