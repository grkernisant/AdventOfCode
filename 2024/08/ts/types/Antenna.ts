import type { Position } from './index.ts';

export const ANTENNA_REGEX = /[a-zA-Z0-9]/g;
export type AntennaFrequency = string;
export interface Antenna extends Position {
  antenna: AntennaFrequency,
}