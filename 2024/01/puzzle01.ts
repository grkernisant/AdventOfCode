import { readFile } from 'fs/promises';

const MIXED_LOCATION_IDS = /^(\d+)\s+(\d+)$/;
type LocationID = number;
type LocationIDs = LocationID[];
type LocationIDsMap = Map<string, LocationIDs>;

async function readFileIfExists(filepath: string): Promise<string | null> {
  try {
    const content = await readFile(filepath, 'utf8');
    return content;
  } catch (error: unknown) {
    const err = error as NodeJS.ErrnoException;

    if (err.code === 'ENOENT') {
      console.log(`File not found at: ${filePath}`);
      return null;
    }

    throw error;
  }
}

function parseLocationIDs(locations: string): LocationIDsMap {
  const locArr = locations.split("\n").filter((s) => s.trim() !== "");
  const locMap = locArr.reduce((acc, curr) => {
    const matches = curr.match(MIXED_LOCATION_IDS);
    if (matches !== null) {
      acc.left.push(parseInt(matches[1]));
      acc.right.push(parseInt(matches[2]));
    }

    return acc;
  }, {left: [], right: []});
  return {left: locMap.left.sort(), right: locMap.right.sort()} as unknown as LocationIDsMap;
}

function locationDistance(loc1: LocationID, loc2: LocationID): number {
  return Math.abs(loc2 - loc1);
}

function checkLocationMap(locMap: LocationIDsMap, key1: string, key2: string): number {
  if (locMap[key1] === undefined) throw Error(`Missing key ${key1} on location map`);
  if (locMap[key2] === undefined) throw Error(`Missing key ${key2} on location map`);

  const tot1 = locMap[key1].length;
  const tot2 = locMap[key1].length;
  if(tot1 !== tot2) throw Error(`Missing location IDs`);

  return tot1;
}

function totalLocationDistance(locMap: LocationIDsMap, key1: string, key2: string): number {
  const tot1 = checkLocationMap(locMap, key1, key2);
  if (tot1 === 0) return 0;

  const totalDistance = [];
  let n = 0;
  while (n < tot1) {
    totalDistance.push(locationDistance(locMap[key1][n], locMap[key2][n]));
    n++;
  }
  return totalDistance.reduce((acc, curr) => acc + curr, 0);
}

const args = process.argv.slice(1);
const input = args[1] ?? 'test.txt';
const sortedLocationIDsMap = parseLocationIDs(await readFileIfExists(input));
const listTotalDistance = totalLocationDistance(sortedLocationIDsMap, "left", "right");
console.log(`List total distance ${listTotalDistance}`);
