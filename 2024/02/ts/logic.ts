import { readFile } from 'fs/promises';
import type { LevelData, SafetyType } from './types.js';

export const MAX_SAFETY_DELTA = 3;

export function checkSafetyLevel(ld: LevelData): bool {
  try {
    if (ld.levels.length <= 1) throw Error('Levels should have at least 2 entries');

    let safe = true;
    let n = 0;
    while (n < ld.levels.length-1 && safe) {
      const diff = ld.levels[n+1] - ld.levels[n];
      const st = getSafetyType(diff);
      if (n === 0) ld.safetyType = st;
      if (n > 0) safe = st === ld.safetyType;

      n++;
    }

    return safe;
  } catch(err: unknown) {
    ld.safetyType = null;
  }

  return false;
}

export function checkSafetyLevelWithDampener(ld: LevelData): bool {
  if (checkSafetyLevel(ld)) return true;

  let found = false;
  let n = 0;
  while (n < ld.levels.length && !found) {
    let levels = [...ld.levels];
    levels.splice(n, 1);
    let dampenedLevels = {
      levels
    };
    if (checkSafetyLevel(dampenedLevels)) {
      found = true;
      ld.dampedLevel = n;
    }

    n++;
  }

  return found;
}

export function getSafetyType(diff: number): SafetyType {
  if (Math.abs(diff) > MAX_SAFETY_DELTA) { throw new Error(`MAX_DIFF_EXCEEDED ${diff}`); }
  if (diff === 0) { throw new Error('NO_DIFF'); }

  if (diff > 0) return "increasing";

  return "decreasing";
}

export function parseLevels(input: string): LevelData {
  const levels = input.trim().split(' ').map((i) => Number(i));

  return { levels };
}

export async function readFileIfExists(filepath: string): Promise<string | null> {
  try {
    const content = await readFile(filepath, 'utf8');
    return content;
  } catch (error: unknown) {
    const err = error as NodeJS.ErrnoException;

    if (err.code === 'ENOENT') {
      console.log(`File not found: ${filepath}`);
      return null;
    }

    throw error;
  }
}
