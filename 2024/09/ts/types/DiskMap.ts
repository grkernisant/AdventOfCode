import { File, FreeSpace } from './index.ts';
import type { DiskBlock, SectorType } from './index.ts';

export class DiskMap {
  disk: DiskBlock[];
  zipped: string;

  constructor(input: string) {
    this.disk = [];
    this.zipped = input;

    this.init();
  }

  checkFileAt(fileIndex: number) {
    if (this.disk[fileIndex].type !== 'file') throw new Error(`NOT_FILE_BLOCK_TYPE: ${fileIndex}`);
  }

  checkSpaceAt(spaceIndex: number) {
    if (this.disk[spaceIndex].type !== 'space') throw new Error(`NOT_SPACE_BLOCK_TYPE: ${spaceIndex}`);
  }

  defrag(): DiskMap {
    let i = this.getFirstAvailableFreeSpaceIndex();
    let j = this.getFirstMovableFileBlockIndex();
    while(i < j && i !== -1 && j !== -1) {
      // swap file block with freespace block
      this.swapBlock(j, i);
      i = this.getFirstAvailableFreeSpaceIndex();
      j = this.getFirstMovableFileBlockIndex();
    }

    // clean up end
    j = this.getFirstMovableFileBlockIndex();
    if (j !== -1 && (j + 1) < this.disk.length && this.disk[j+1].type === 'space') {
      const merged = this.disk.slice(j+1).reduce((acc, curr) => {
        acc.blockSize+= curr.blockSize;
        acc.count++;
        return acc;
      }, {blockSize: 0, count: 0});
      this.disk.splice(j+1, merged.count, new FreeSpace(merged.blockSize));
    }

    return this;
  }

  getDiskChecksum(): number {
    const j = this.getFirstMovableFileBlockIndex();
    const cs = this.disk
      .slice(0, j+1)
      .reduce((acc, curr) => {
        let i = 0;
        while(i < curr.blockSize) {
          if (curr.type === 'file') {
            acc.subtotal+= acc.index * Number(curr.fileID);
          }

          acc.index++;
          i++;
        }

        return acc;
      }, {subtotal: 0, index: 0});

    return cs.subtotal;
  }

  getFiles(): File[] {
    return this.disk.filter(e => e.type === 'file' as SectorType);
  }

  getFirstAvailableFreeSpaceIndex(): number {
    return this.disk.findIndex(e => (e.type === 'space' as SectorType) &&  e.blockSize > 0);
  }

  getFirstMovableFileBlockIndex(): number {
    return this.disk.findLastIndex((e => e.type === 'file' as SectorType));
  }

  getFreeSpace(): FreeSpace[] {
    return this.disk.filter(e => e.type === 'space' as SectorType);
  }

  init(): DiskMap {
    this.zipped
      .trim()
      .split('')
      .map((n, i) => {
        // file block
        if (i % 2 === 0) {
          const blockSize = Number(n);
          const fileID = Number(i/2);
          this.disk.push(new File(blockSize, fileID));
        }
        // free space
        if (i % 2 === 1) {
          const blockSize = Number(n);
          this.disk.push(new FreeSpace(blockSize));
        }
      });

    return this;
  }

  mergeSpaces(srcIndex: number, dstIndex: number): DiskMap {
    this.checkSpaceAt(srcIndex);
    this.checkSpaceAt(dstIndex);

    this.disk[dstIndex].blockSize+= this.disk[srcIndex].blockSize;
    this.disk[srcIndex].blockSize = 0;

    return this;
  }

  moveFiles(): DiskMap {
    const j = this.getFirstMovableFileBlockIndex();
    let currFileID = this.disk[j].fileID;
    let currBlockSize: number;
    let fileIndex: number;
    let spaceIndex: number;

    while (currFileID > 0) {
      fileIndex  = this.disk.findLastIndex(f => f.type === 'file' && f.fileID === currFileID);
      if (fileIndex === -1) {
        currFileID--;
        continue;
      }

      currBlockSize = this.disk[fileIndex].blockSize;
      spaceIndex = this.disk.findIndex(s => s.type === 'space' && s.blockSize >= currBlockSize);
      if (spaceIndex === -1 || spaceIndex > fileIndex) {
        currFileID--;
        continue;
      }

      this.swapFileToSpace(fileIndex, spaceIndex);

      currFileID--;
    }

    return this;
  }

  removeFileAt(fileIndex: number): DiskMap {
    this.checkFileAt(fileIndex);

    if (fileIndex > 0) {
      // increase space before file
      this.disk[fileIndex-1].blockSize+= this.disk[fileIndex].blockSize;
    }

    if (fileIndex === 0 && this.disk.length > 2 && this.disk[1].type === 'space') {
      this.disk[1].blockSize+= this.disk[fileIndex].blockSize;
    }

    this.disk.splice(fileIndex, 1);

    return this;
  }

  swapBlock(srcIndex: number, dstIndex: number): DiskMap {
    if (this.disk[srcIndex].type === 'file' && this.disk[dstIndex].type === 'file') {
      return this.swapFileToFile(srcIndex, dstIndex);
    }

    if (this.disk[srcIndex].type === 'file' && this.disk[dstIndex].type === 'space') {
      return this.swapFileToSpace(srcIndex, dstIndex);
    }

    if (this.disk[srcIndex].type === 'space' && this.disk[dstIndex].type === 'file') {
      return this.swapFileToSpace(dstIndex, srcIndex);
    }

    if (this.disk[srcIndex].type === 'space' && this.disk[dstIndex].type === 'space') {
      return this.swapSpaceToSpace(srcIndex, dstIndex);
    }

    return this;
  }

  swapFileToFile(fileSrcIndex: number, fileDstIndex: number): DiskMap {
    this.checkFileAt(fileSrcIndex);
    this.checkFileAt(fileDstIndex);

    if (
      this.disk[fileSrcIndex].blockSize !== this.disk[fileDstIndex].blockSize &&
      this.disk[fileSrcIndex].fileID !== this.disk[fileDstIndex].fileID
    ) {
      const fileSrc = new File(this.disk[fileSrcIndex].blockSize, this.disk[fileSrcIndex].fileID);
      const fileDst = new File(this.disk[fileDstIndex].blockSize, this.disk[fileDstIndex].fileID);

      this.disk.splice(fileDstIndex, 1, fileSrc);
      this.disk.splice(fileSrcIndex, 1, fileDst);
    }

    return this;
  }

  swapFileToSpace(fileIndex: number, spaceIndex: number): DiskMap {
    this.checkFileAt(fileIndex);
    this.checkSpaceAt(spaceIndex);

    const fileBlock = this.disk[fileIndex];
    const fileBlockSize = fileBlock.blockSize;
    const fileID = fileBlock.fileID;
    const spaceBlock = this.disk[spaceIndex];
    const spaceBlockSize = spaceBlock.blockSize;

    if (fileBlock.blockSize <= spaceBlock.blockSize) {
      // move all the file blocks
      if (fileIndex < spaceIndex) {
        // move file blocks to reduced space
        this.disk.splice(spaceIndex, 1, ...[
          new FreeSpace(0),
          new File(fileBlockSize, fileID),
          new FreeSpace(spaceBlockSize - fileBlockSize)
        ]);
        // remove file and merge surrounding space
        this.removeFileAt(fileIndex);
      }

      if (spaceIndex < fileIndex) {
        // remove file and merge surrounding space
        this.removeFileAt(fileIndex);
        // move file blocks to space
        this.disk.splice(spaceIndex, 1, ...[
          new FreeSpace(0),
          new File(fileBlockSize, fileID),
          new FreeSpace(spaceBlockSize - fileBlockSize)
        ]);
      }
    }

    if (fileBlock.blockSize > spaceBlock.blockSize) {
      // partial move
      if (fileIndex < spaceIndex) {
        // reduce file block size
        this.disk[fileIndex].blockSize-= spaceBlockSize;
        // move file blocks to space
        this.disk.splice(spaceIndex, 1, ...[
          new FreeSpace(0),
          new File(spaceBlockSize, fileID),
          new FreeSpace(0)
        ]);
      }

      if (spaceIndex < fileIndex) {
        // reduce file block size
        this.disk[fileIndex].blockSize-= spaceBlockSize;
        if (fileIndex < (this.disk.length - 1) && this.disk[fileIndex+1].type === 'space') {
          this.disk[fileIndex+1].blockSize+= spaceBlockSize;
        }

        // move file blocks to space
        this.disk.splice(spaceIndex, 1, ...[
          new FreeSpace(0),
          new File(spaceBlockSize, fileID),
          new FreeSpace(0)
        ]);
      }
    }

    return this;
  }

  swapSpaceToSpace(spaceSrcIndex: number, spaceDstIndex: number): DiskMap {
    this.checkSpaceAt(spaceSrcIndex);
    this.checkSpaceAt(spaceDstIndex);

    const spaceSrc = new FreeSpace(this.disk[spaceSrcIndex].blockSize);
    const spaceDst = new FreeSpace(this.disk[spaceDstIndex].blockSize);

    this.disk.splice(spaceDstIndex, 1, spaceSrc);
    this.disk.splice(spaceSrcIndex, 1, spaceDst);

    return this;
  }

  toString(): string {
    return this.disk.reduce((acc, curr) => {
      if (curr.type === 'file') {
        const id = curr.fileID < 10 ? `${curr.fileID}` : `<${curr.fileID}>`;
        acc+= id.repeat(curr.blockSize);
      }
      if (curr.type === 'space') {
        acc+= '.'.repeat(curr.blockSize);
      }

      return acc;
    }, '');
  }
}