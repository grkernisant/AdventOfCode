import { Direction } from './Direction.ts';
import { GuardRoute, Obstacle } from './GuardRoute.ts';
import { Position } from './Position.ts';
import { Tile } from './Tile.ts';

export interface MapTile extends Position {
  char: Tile | Obstacle | Direction | GuardRoute;
}