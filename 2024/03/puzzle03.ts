import {
  parseMuls,
  parseMulsWithDoDonts,
  readFileIfExists,
  sumMuls
} from './ts/logic.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const mulsContent = await readFileIfExists(input);
const allParsedMuls = parseMuls(mulsContent ?? '');
const uncorruptedMultiResult = sumMuls(allParsedMuls ?? []);
console.log(`Scanning the uncorrupted memory, result = ${uncorruptedMultiResult}`);

const allDontDosParsedMul = parseMulsWithDoDonts(mulsContent ?? '');
const uncorruptedDontDosMultiResult = sumMuls(allDontDosParsedMul ?? []);
console.log(`Scanning the uncorrupted memory with dos and dont's, result = ${uncorruptedDontDosMultiResult}`);
