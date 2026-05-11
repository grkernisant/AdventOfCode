import { File, FreeSpace } from './index.ts';

export const MOVABLE_FILE_BLOCK_REGEX = /([0-9])\.*$/;
export type Disk = File | FreeSpace;