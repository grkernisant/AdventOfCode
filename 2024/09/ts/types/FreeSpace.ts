import type { Sector, SectorType } from './index.ts';

export class FreeSpace implements Sector {
  blockSize: number;
  type: SectorType;

  constructor(bs: number) {
    this.blockSize = bs;
    this.type = 'space';
  }

  toString(): string {
    return `FreeSpace { blockSize: ${this.blockSize} }`;
  }
}