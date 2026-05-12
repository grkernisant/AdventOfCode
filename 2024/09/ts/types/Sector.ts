export type SectorType = 'file' | 'space';
export interface Sector {
	type: SectorType,
	blockSize: number
};