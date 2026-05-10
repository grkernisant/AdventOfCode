import { AntennaMap } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const antennaMapContent = await readFileIfExists(input);

const antennaMap = new AntennaMap(antennaMapContent, false);
console.log(`There are ${antennaMap.antinodes.size} unique antinodes in the area`);

const antennaMapWithResonance = new AntennaMap(antennaMapContent, true);
console.log(`There are ${antennaMapWithResonance.antinodes.size} unique antinodes in the area`);