import { checkSafetyLevel, checkSafetyLevelWithDampener, parseLevels, readFileIfExists } from './ts/logic.ts';
import type { LevelData, SafetyType } from './ts/types.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const reportsContent = await readFileIfExists(input);
const reports = reportsContent?.split("\n").map((r) => parseLevels(r)) ?? [];
const nbSafeReports = reports.reduce((acc, curr) => {
  acc+= checkSafetyLevel(curr) ? 1 : 0;
  return acc;
}, 0);
const nbSafeReportsWithDampener = reports.reduce((acc, curr) => {
  acc+= checkSafetyLevelWithDampener(curr) ? 1 : 0;
  return acc;
}, 0);

console.log(`There are ${nbSafeReports} safe reports`);
console.log(`There are ${nbSafeReportsWithDampener} safe reports with a dampener`);
