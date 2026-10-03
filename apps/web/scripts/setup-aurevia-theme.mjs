import { copyFile, readFile, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import { resolve } from 'node:path';

const root = process.cwd();
const angularJsonPath = resolve(root, 'angular.json');
const appHtmlPath = resolve(root, 'src/app/app.html');
const appScssPath = resolve(root, 'src/app/app.scss');
const shellHtmlPath = resolve(root, 'src/theme/aurevia-shell.html');
const shellScssPath = resolve(root, 'src/theme/aurevia-shell.scss');

if (!existsSync(angularJsonPath)) {
  throw new Error('angular.json was not found. Run this script from apps/web.');
}

const workspace = JSON.parse(await readFile(angularJsonPath, 'utf8'));
let updatedStyleEntry = false;

for (const project of Object.values(workspace.projects ?? {})) {
  const styles = project?.architect?.build?.options?.styles ?? project?.targets?.build?.options?.styles;
  if (!Array.isArray(styles)) continue;

  const scssIndex = styles.findIndex((entry) => entry === 'src/styles.scss');
  if (scssIndex !== -1) {
    styles[scssIndex] = 'src/styles.css';
    updatedStyleEntry = true;
  } else if (!styles.includes('src/styles.css')) {
    styles.unshift('src/styles.css');
    updatedStyleEntry = true;
  }
}

await writeFile(angularJsonPath, `${JSON.stringify(workspace, null, 2)}\n`, 'utf8');
await copyFile(shellHtmlPath, appHtmlPath);
await copyFile(shellScssPath, appScssPath);

console.log(updatedStyleEntry ? 'Angular global style entry switched to src/styles.css.' : 'Angular already uses src/styles.css.');
console.log('Aurevia compact shell installed into src/app/app.html.');
console.log('Aurevia root component styles installed into src/app/app.scss.');
