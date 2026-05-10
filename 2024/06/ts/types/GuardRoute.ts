import { Direction } from "./Direction.ts";
import { Position } from "./Position.ts";

export type GuardTrailType = 'move' | 'rotate';
export type GuardRoute = 'X' | '|' | '-' | '+';
export type Obstacle = '#' | 'O';

export interface GuardTrail extends Position{
    type: GuardTrailType;
    dir: Direction;
    step: number;
}
