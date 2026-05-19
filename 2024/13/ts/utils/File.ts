import { readFile, writeFile } from 'fs/promises';
 
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

export function writeFileContent(filepath: string, content: string) {
  writeFile(filepath, content);
}
