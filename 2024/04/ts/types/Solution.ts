import { Direction } from './Direction.ts';
import { CharacterTile } from './CharacterTile.ts';

export interface Solution extends CharacterTile {
  dir: Direction
}