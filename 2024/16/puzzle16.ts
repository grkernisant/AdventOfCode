import { MazeMap, Parser } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test';
const mazeMapContent = await readFileIfExists(input);
const mazeMap = new MazeMap(new Parser(mazeMapContent));
mazeMap.run();
console.log(`Minimum score: ${mazeMap.getDistanceEnd()}`);
console.log(`Best places: ${mazeMap.getNbOnBestPaths()}`);
