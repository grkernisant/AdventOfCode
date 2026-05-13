export const transformCache = new Map<string, string>();
export const countCache = new Map<string, number>();

export class Stone {
  static blinkResult(engraving: string): string {
    return engraving
      .trim().split(' ').map(e => {
        if (transformCache.has(e)) return transformCache.get(e);

        const br = Stone.transform(e);
        transformCache.set(e, br);
        return br;
      })
      .join(' ');
  }

  static getCountCacheKey(s: string, nb: number): string {
    return `[${s}]-${nb}`;;
  }

  static getTransformCache(key: string): string | undefined {
    return transformCache.get(key);
  }

  static setTransformCache(key: string, value: string) {
    transformCache.set(key, value);
  }

  static totalStones(s: string, nb: number = 1): number {
    if (nb <= 0) return 0;
    const countKey = Stone.getCountCacheKey(s, nb);
    if (countCache.has(countKey)) return countCache.get(countKey);

    const totalStonesCount = s.trim().split(' ').reduce((acc, curr) => {
      const currCountKey = Stone.getCountCacheKey(curr, nb);
      if (countCache.has(currCountKey)) {
        acc+= countCache.get(currCountKey);
      } else {
        const result = Stone.blinkResult(curr);
        if (nb > 1) {
          acc+= Stone.totalStones(result, nb - 1);
        } else {
          acc+= result.trim().split(' ').length;
        }
      }

      return acc;
    }, 0);

    countCache.set(countKey, totalStonesCount);
    return totalStonesCount;
  }

  static transform(engraving: string): string {
    if (engraving === '0') return '1';

    const l = engraving.length;
    if (l % 2 === 0) {
      return `${Number(engraving.substring(0, l/2))} ${Number(engraving.substring(l/2))}`;
    }

    return `${2024 * Number(engraving)}`;
  }
}