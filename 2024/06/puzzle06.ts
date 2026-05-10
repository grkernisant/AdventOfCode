import { Map } from './ts/types/Map.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const guardMapContent = await readFileIfExists(input);
if (guardMapContent !== null) {
  const map = new Map(guardMapContent);

  // part 1
  map.run();
  const tl = map.guard.trail.length;
  console.log(`Guard left after ${map.guard.trail[tl - 1].step} steps.`);
  console.log(`He visited ${map.guard.uniqueVisits.size} unique positions.`);

  // part 2
  // go to initial position and run again
  map.guard.iteration = 0;
  map.guard.goto(map.guard.initialPosition);
  map.run();
  console.log(`There are ${map.loopBlocks.size} possible loop blocks.`);
}
