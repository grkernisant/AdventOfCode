import { MOVABLE_FILE_BLOCK_REGEX } from './index.ts';
import type { Disk, File, FreeSpace } from './index.ts';

export class DiskMap {
  disk: Disk[];
  zipped: string;
  unzipped: string;

  constructor(input: string) {
    this.disk = [];
    this.zipped = input;

    this.init();
  }

  defrag() {
    let j = this.nextMovableFileBlock();
    let i = this.nextFreeSpaceFrom(0);

    while(i < j) {
        // copy fileblock to freespace
        this.unzipped = `${this.unzipped.split('').toSpliced(i, 1, this.unzipped[j]).join('')}`;
        // pluck old value, creating free space
        this.unzipped = `${this.unzipped.split('').toSpliced(j, 1, '.').join('')}`;
        j = this.nextMovableFileBlock();
        i = this.nextFreeSpaceFrom(i);
    }
  }

  getDiskChecksum(): number {
    const cs = this.unzipped
      .split('')
      .reduce((acc, curr) => {
        if (!isNaN(curr)) {
          acc.subtotal+= acc.index * Number(curr);
        }
        acc.index++;

        return acc;
      }, {subtotal: 0, index: 0});

    return cs.subtotal;
  }

  init() {
    this.unzipped = '';
    this.zipped
      .trim()
      .split('')
      .map((n, i) => {
        // file block
        if (i % 2 === 0) {
          const filesize = Number(n);
          const fileID = Number(i/2);

          this.unzipped+= String(fileID).repeat(filesize);
          this.disk.push({ filesize, fileID });
        }
        // free space
        if (i % 2 === 1) {
          const freeSpace = Number(n);

          this.unzipped+= '.'.repeat(freeSpace);
          this.disk.push(freeSpace);
        }
      });
  }

  nextFreeSpaceFrom(i: number): number {
    return this.unzipped.indexOf('.', i);
  }

  nextMovableFileBlock(): number {
    const matches = this.unzipped.match(MOVABLE_FILE_BLOCK_REGEX);
    if (matches === null) throw new Error(`NO_AVAILABLE_MOVABLE_FILE_BLOCK`);

    return matches.index;
  }
}