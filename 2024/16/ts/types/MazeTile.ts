import type { Position } from './index.ts';

export const Floor = '.';
export const EndTile = 'E';
export const StartTile = 'S';
export const Wall = '#';
export type Tile = Floor | EndTile | StartTile | Wall;

export const END_TILE_REGEX = /E/;
export const START_TILE_REGEX = /S/;

export interface MazeTile extends Position {
  tile: Tile,
}