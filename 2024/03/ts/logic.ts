import { readFile } from 'fs/promises';
import { MUL_REGEX, DO_DELIMITER, DONT_DELIMITER } from './types.ts'
import type { Mul } from './types.ts'

export function parseMuls(input: string): Mul[] | null {
  const matches = input.matchAll(MUL_REGEX);
  if (matches === null) return null;

  const arr = Array
    .from(matches, (m) => m.slice(0, 3))
    .reduce((acc, curr) => {
      const aMul = {
        operand1: Number(curr[1]),
        operand2: Number(curr[2]),    
      } as Mul;
      acc.push(aMul);
      return acc;
    }, []);
  return arr;
}

export function parseMulsWithDoDonts(input: string): Mul[] | null {
  const filteredInput = input
    .split(DONT_DELIMITER)
    .map((str, i) => {
      if (i > 0) {
        const do_split = str.split(DO_DELIMITER);
        return do_split.length > 1
          ? do_split.slice(1).join('')
          : '';
      }

      if (i === 0) return str;
    })
    .join(DONT_DELIMITER);
  return parseMuls(filteredInput);

  /*const matches = input.matchAll(DO_DONT_REGEX);
  if (matches === null) return null;

  let filteredInput = input;
  const arr = Array.from(matches);
  let i = arr.length - 1;
  while (i >= 0) {
    const indexDo = filteredInput.indexOf('do()', arr[i].index);
    filteredInput = indexDo > -1
      ? input.substring(0, arr[i].index) + input.substring(indexDo)
      : input.substring(indexDo);

    i--;
  }

  return null;*/
}

export function resolveMul(mul: Mul): number {
  return mul.operand1 * mul.operand2;
}

export function sumMuls(allMuls: Mul[]): number {
  return allMuls.reduce((acc, curr) => {
    acc+= resolveMul(curr);
    return acc;
  }, 0);
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
