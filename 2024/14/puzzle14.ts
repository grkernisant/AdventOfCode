import { Bathroom, Parser } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test';
const env = input === 'input'
  ? { c: 101, r: 103 }
  : { c: 11, r: 7 };
const bathroomContent = await readFileIfExists(input);
const parser = new Parser(bathroomContent);
const restroom = new Bathroom(env);
restroom.addRobots(parser.getInput());
const quandrantCounts = restroom.getQuandrantCounts(restroom.getRobotsPositionAfter(100));

// Part 1
console.log(`The safety factor is ${restroom.getSafetyFactor(quandrantCounts)}\n\n`);

// Part 2
const easterEggTime = restroom.getEasterEgg();
console.log(restroom.print(restroom.getRobotsPositionAfter(easterEggTime)));
console.log(`Found a tree at ${easterEggTime}`);
