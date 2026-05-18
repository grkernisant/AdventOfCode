import { Garden } from './ts/types/index.ts';
import { readFileIfExists } from './ts/utils/File.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test';
const gardenContent = await readFileIfExists(input);

const garden = new Garden(gardenContent);
console.log(`The price of the fence for this garden is: ${garden.getFencePrice()}`);
console.log(`The bulk price of the fence for this garden is: ${garden.getBulkFencePrice()}`);
