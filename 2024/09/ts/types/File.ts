import type { Sector, SectorType } from './index.ts';

export class File implements Sector {
  blockSize: number;
  fileID: number;
  type: SectorType;

  constructor(bs: number, id: number) {
    this.blockSize = bs;
    this.fileID = id;
    this.type = 'file';
  }
}