import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';
import * as pdfjs from 'pdfjs-dist/legacy/build/pdf.mjs';
import { readHeader } from './register-header.mjs';
import { parseRecords, matchLabelledPhoto } from './register-records.mjs';

sharp.concurrency(1);
sharp.cache({ memory: 16, files: 0, items: 32 });

const [pdfPath, outDir] = process.argv.slice(2);
if (!pdfPath || !outDir) {
  console.error('Usage: node extract-register.mjs <file.pdf> <outDir>');
  process.exit(2);
}
fs.mkdirSync(outDir, { recursive: true });

const { OPS } = pdfjs;
const clean = (s) => s.replace(/\s+/g, ' ').replace(/\s+,/g, ',').trim();

// Matrix product: apply A first, then B (PDF row-vector convention).
const mul = (A, B) => [
  A[0] * B[0] + A[1] * B[2], A[0] * B[1] + A[1] * B[3],
  A[2] * B[0] + A[3] * B[2], A[2] * B[1] + A[3] * B[3],
  A[4] * B[0] + A[5] * B[2] + B[4], A[4] * B[1] + A[5] * B[3] + B[5],
];

// Left column first, then right column; top to bottom within each column.
const byColumnThenTop = (a, b) => a.column - b.column || b.y - a.y;

async function getItems(page) {
  const tc = await page.getTextContent();
  return tc.items
    .filter((i) => typeof i.str === 'string' && i.str.trim() !== '')
    .map((i) => ({ str: clean(i.str), x: i.transform[4], y: i.transform[5] }));
}

// Find every image on the page together with where it is drawn.
async function findPhotos(page, mid) {
  const ops = await page.getOperatorList();
  const top = page.view[3];
  let ctm = [1, 0, 0, 1, 0, 0];
  const stack = [];
  const found = [];

  for (let i = 0; i < ops.fnArray.length; i++) {
    const args = ops.argsArray[i];
    switch (ops.fnArray[i]) {
      case OPS.save: stack.push(ctm); break;
      case OPS.restore: ctm = stack.pop() ?? ctm; break;
      case OPS.transform: ctm = mul(args, ctm); break;
      case OPS.paintFormXObjectBegin:
        stack.push(ctm);
        if (args[0]) ctm = mul(args[0], ctm);
        break;
      case OPS.paintFormXObjectEnd: ctm = stack.pop() ?? ctm; break;
      case OPS.paintImageXObject: {
        const [a, b, c, d, e, f] = ctm;
        const xs = [e, e + a, e + c, e + a + c];
        const ys = [f, f + b, f + d, f + b + d];
        const left = Math.min(...xs), right = Math.max(...xs);
        const bottom = Math.min(...ys), imgTop = Math.max(...ys);
        found.push({
          id: args[0],
          distFromTop: top - imgTop,
          width: right - left,
          left,
          right,
          column: (left + right) / 2 > mid ? 1 : 0,
          y: (bottom + imgTop) / 2,
        });
        break;
      }
    }
  }
  // Drop the logos in the page header.
  return found.filter((im) => im.distFromTop > 90 && im.width > 25).sort(byColumnThenTop);
}

async function savePhoto(page, id, file) {
  const store = id.startsWith('g_') ? page.commonObjs : page.objs;
  const img = await Promise.race([
    new Promise((resolve) => store.get(id, resolve)),
    new Promise((_, reject) => setTimeout(() => reject(new Error(`Timed out reading image ${id}`)), 15000)),
  ]);
  if (!img?.data) throw new Error(`Image ${id} has no pixel data`);

  const channels = img.kind === 3 ? 4 : img.kind === 2 ? 3 : 0; // RGBA / RGB
  if (!channels) throw new Error(`Unsupported image format (kind ${img.kind}) for ${id}`);

  const raw = Buffer.from(img.data.buffer, img.data.byteOffset, img.data.byteLength);
  await sharp(raw, { raw: { width: img.width, height: img.height, channels } })
    .flatten({ background: '#ffffff' })
    .jpeg({ quality: 90 })
    .toFile(file);
}

// ---------------- main ----------------
const data = new Uint8Array(fs.readFileSync(pdfPath));
const doc = await pdfjs.getDocument({ data, verbosity: 0, useSystemFonts: true }).promise;

const { header, expected, printedYear } = readHeader(await getItems(await doc.getPage(1)));
const records = [];
const problems = [];

if (!header) {
  console.error('Location header not recognized; using the selected polling unit.');
}

for (let p = 1; p <= doc.numPages; p++) {
  const page = await doc.getPage(p);
  try {
    const mid = (page.view[0] + page.view[2]) / 2;

    const parsed = parseRecords(await getItems(page), mid, printedYear ?? new Date().getUTCFullYear());
    problems.push(...parsed.problems.map((problem) => `Page ${p}: ${problem}`));
    const recs = parsed.records;
    if (recs.length === 0) continue;
    const photos = await findPhotos(page, mid);

    const usedPhotos = new Set();
    for (let i = 0; i < recs.length; i++) {
      const r = recs[i];
      let photo = parsed.labelled ? matchLabelledPhoto(r, photos, usedPhotos) : null;
      if (!parsed.labelled) {
        const candidates = photos.filter((candidate) => !usedPhotos.has(candidate)
          && candidate.column === r.column && Math.abs(candidate.y - r.y) <= 30);
        if (candidates.length === 1) {
          photo = candidates[0];
          usedPhotos.add(photo);
        }
      }
      let file = null;
      if (photo) {
        const filename = `page-${p}-record-${i + 1}.jpg`;
        try {
          await savePhoto(page, photo.id, path.join(outDir, filename));
          file = filename;
        } catch (error) {
          console.error(`Page ${p}: record ${r.serial ?? i + 1}: photo unavailable; storing NULL. ${error.message}`);
        }
      }

      records.push({
        serial: r.serial ?? null,
        source_key: `page:${p}:record:${i + 1}`,
        vin: r.vin,
        full_name: r.full_name,
        surname: r.surname,
        other_names: r.other_names,
        occupation: r.occupation,
        gender: r.gender,
        age: r.age,
        photo: file,
      });
    }
  } finally {
    page.cleanup();
  }
}

if (expected !== null && records.length !== expected) {
  problems.push(`Expected ${expected} records but parsed ${records.length}`);
}

if (records.length === 0 && expected !== 0) {
  problems.push('No readable voter records found. The PDF may be scanned or use an unsupported layout. Upload a text-based polling-unit register; scanned PDFs require OCR.');
}

fs.writeFileSync(path.join(outDir, 'records.json'), JSON.stringify({ header, expected, records }));

if (problems.length) {
  console.error(problems.join('\n'));
  process.exit(1);
}
console.log(`Extracted ${records.length} records`);
process.exit(0);
