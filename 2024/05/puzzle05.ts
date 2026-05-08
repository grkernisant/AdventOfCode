import { ElfPrinterQ } from './ts/types/ElfPrinterQ.ts';
import { readFileIfExists } from './ts/utils/file.ts';

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const printQueueContent = await readFileIfExists(input);
const printQueue = new ElfPrinterQ(printQueueContent ?? '');
console.log(`The middle page number sum is: ${printQueue.middleSum()}`);

console.log(`The repaired middle page number sum is: ${printQueue.repairedQueueSum()}`);
