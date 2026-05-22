import type { BoxType, FloorType, RobotType, Wall } from './index.ts';

export const TILE_REGEX = /^(#|O|\.|@)+$/;

export type Tile = FloorType | BoxType | Wall;
