import { PAGE_ORDER_RULE_REGEX } from './PageOrderRule.ts';
import { PRINT_PAGE_DELIMITER, PRINT_PAGE_REGEX } from './PrintQueue.ts';
import type { PageOrderRule } from './PageOrderRule.ts';
import type { PageNumber } from './PageNumber.ts';
import type { PrintQueue } from './PrintQueue.ts';

export class ElfPrinterQ {
  pagesMap: Map<string, PageOrder>;
  pageOrderRules: PageOrderRule[];
  printQueues: PrintQueue[];

  constructor(input: string) {
    this.pagesMap = new Map<string, PageOrder>();
    this.pageOrderRules = [];
    this.printQueues = [];

    this.parse(input);
  }

  addOrderRule(por: PageOrderRule) {
    this.pageOrderRules.push(por);
  }

  findPageOrderRule(por: PageOrderRule): PageOrderRule | undefined {
    return this.pageOrderRules.find((r) => r.pageX === por.pageX && r.pageY === por.pageY);
  }

  isPageOrderValid(pages: PageNumber[]): boolean {
    // check for reverse to invalidate
    const l = pages.length;
    let i = 0;
    let valid = true;
    while (i <= l-2 && valid) {
      const remaining = pages.slice(i+1);
      valid = remaining.find((r) => {
        const por = { pageX: r, pageY: pages[i] } as PageOrderRule;
        return this.findPageOrderRule(por) !== undefined;
      }) === undefined;

      i++;
    }

    return valid;
  }

  middleSum(): number {
    const sum = this.printQueues.reduce((acc, curr) => {
      acc+= curr.validSort ? curr.middle : 0;
      return acc;
    }, 0);

    return sum;
  }

  parse(input: string) {
    input
      .trim()
      .split("\n")
      .map((str) => {
        const line = str.trim();
        const matchesPageOrder = line.match(PAGE_ORDER_RULE_REGEX);
        const matchesPrintQueue = line.match(PRINT_PAGE_REGEX);

        if (matchesPageOrder !== null) {
          this.addOrderRule({
            pageX: Number(matchesPageOrder[1]),
            pageY: Number(matchesPageOrder[2]),
          } as PageOrderRule);
        }

        if (matchesPrintQueue !== null) {
          this.printQueues.push({
            pages: line.split(PRINT_PAGE_DELIMITER).map((p) => Number(p)) as PageNumber[],
          });
        }
      });

    this.printQueues.map((pq) => {
      pq.validSort = this.isPageOrderValid(pq.pages);
      if (pq.validSort) {
        pq.middle = pq.pages[Math.floor((pq.pages.length-1)/2)];
      }
    });
  }

  repairQueueMiddle(pages: PageNumber[]): PageNumber[] {
    const l = pages.length;
    let i = 0;
    while (i <= l-2) {
      const remaining = pages.slice(i+1);
      const invalidIndex = remaining.findIndex((r) => {
        const por = { pageX: r, pageY: pages[i] } as PageOrderRule;
        return this.findPageOrderRule(por) !== undefined;
      });

      // swap pages
      if (invalidIndex > -1) {
        const copy = pages[i];
        pages[i] = pages[i+invalidIndex+1];
        pages[i+invalidIndex+1] = copy;
        return this.repairQueueMiddle(pages);
      }

      i++;
    }

    return pages;
  }

  repairedQueueSum(pq: PrintQueue): number {
    return this.printQueues.reduce((acc, curr) => {
      if (!curr.validSort) {
        const repairedPages = this.repairQueueMiddle(curr.pages);
        if (this.isPageOrderValid(repairedPages)) {
          const l = repairedPages.length;
          const repairedMiddle = repairedPages[Math.floor(l-1)/2];
          acc+= repairedMiddle;
        }
      }

      return acc;
    }, 0);
  }
}
