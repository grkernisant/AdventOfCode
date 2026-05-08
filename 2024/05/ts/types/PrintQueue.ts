import type { PageNumber } from './PageNumber';

export const PRINT_PAGE_REGEX = /^(?:(\d+),?)+$/g;
export const PRINT_PAGE_DELIMITER = ',';

export interface PrintQueue {
  pages: PageNumber[],
  validSort: bool,
  middle: PageNumber
}