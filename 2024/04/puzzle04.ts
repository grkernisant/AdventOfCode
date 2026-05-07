import { readFileIfExists } from './ts/utils/file.ts';
import { PuzzleSearch } from './ts/types/PuzzleSearch.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const puzzleContent = await readFileIfExists(input);
const search = 'XMAS';
const puzzleSearch = new PuzzleSearch(puzzleContent ?? '', search);
console.log(`The puzzle contains '${search}' ${puzzleSearch.solve()} times`);

const searchX = 'MAS';
const puzzleSearchX = new PuzzleSearch(puzzleContent ?? '', searchX);
console.log(`The puzzle contains '${searchX}' ${puzzleSearchX.solveX()} times`);
