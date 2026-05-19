import { PRIZE_ADJUSTMENT, Parser } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test';
const clawMachinesContent = await readFileIfExists(input);
const cm = new Parser(clawMachinesContent);

// Part 1
console.log(`Min tokens: ${cm.getTokens()}`);

// Part 2
cm.machines.map(m => {
  const newPrize = {
   x: m.prize.x + PRIZE_ADJUSTMENT,
   y: m.prize.y + PRIZE_ADJUSTMENT,
  };
  m.setPrize(newPrize);
  m.reset();
  m.setMaxHits(0);
  m.solve();
});
console.log(`Min tokens after adjustment: ${cm.getTokens()}`);
