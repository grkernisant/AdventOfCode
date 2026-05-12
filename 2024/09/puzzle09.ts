import { DiskMap } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const diskMapContent = await readFileIfExists(input);

const diskMap = new DiskMap(diskMapContent ?? '');
console.log(`The amphipod hard drive checksum is: ${diskMap.defrag().getDiskChecksum()}`);
