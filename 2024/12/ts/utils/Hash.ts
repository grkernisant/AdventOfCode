import * as crypto from 'crypto';

export const getMd5Hash = (content: string): string => {
  return crypto.createHash('md5').update(content).digest("hex");
}