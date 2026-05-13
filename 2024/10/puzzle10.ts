import { TopoMap } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const topoMapContent = await readFileIfExists(input);

const tm = new TopoMap(topoMapContent ?? '');
console.log(`Topographic Map Score: ${tm.getScore().total}`);
console.log(`Topographic Map Rating: ${tm.getRatings().total}`);
