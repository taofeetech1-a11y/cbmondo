import fs from 'node:fs';
import path from 'node:path';
import sharp from 'sharp';
import * as pdfjs from 'pdfjs-dist/legacy/build/pdf.mjs';
import { readHeader } from './register-header.mjs';

const [pdfPath, outDir] = process.argv.slice(2);
if (!pdfPath || !outDir) {
  console.error('Usage: node extract-register.mjs <file.pdf> <outDir>');
  process.exit(2);
}
fs.mkdirSync(outDir, { recursive: true });

const { OPS } = pdfjs;
const VIN_RE = /^[0-9A-Z]{4}(?: [0-9A-Z]{4}){3} \d{3}$/;
const GENDER_RE = /^(Male|Female)\b\s*(\d{1,3})?$/;
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

function parsePage(items, mid) {
  return items
    .filter((i) => VIN_RE.test(i.str))
    .map((v) => {
      // Values stacked under the VIN in the same column: name, [occupation], gender (+age)
      const col = items
        .filter((i) => Math.abs(i.x - v.x) < 4 && i.y < v.y && v.y - i.y < 45)
        .sort((a, b) => b.y - a.y);

      const gi = col.findIndex((i) => GENDER_RE.test(i.str));
      if (gi < 1) throw new Error(`Could not parse record with VIN ${v.str}`);

      const m = col[gi].str.match(GENDER_RE);
      let age = m[2];
      if (!age) {
        const ageItem = items
          .filter((i) => /^\d{1,3}$/.test(i.str) && Math.abs(i.y - col[gi].y) < 3 && i.x > col[gi].x)
          .sort((a, b) => a.x - b.x)[0];
        age = ageItem?.str;
      }

      const fullName = col[0].str;
      const [surname, ...rest] = fullName.split(',');
      return {
        vin: v.str,
        x: v.x,
        y: v.y,
        column: v.x > mid ? 1 : 0,
        full_name: fullName,
        surname: surname.trim(),
        other_names: rest.join(',').trim(),
        occupation: gi > 1 ? col.slice(1, gi).map((i) => i.str).join(' ') : null,
        gender: m[1],
        age: Number(age),
      };
    })
    .sort(byColumnThenTop);
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

const { header, expected } = readHeader(await getItems(await doc.getPage(1)));
const records = [];
const problems = [];

if (!header) {
  console.error('Location header not recognized; using the selected polling unit.');
}

for (let p = 1; p <= doc.numPages; p++) {
  const page = await doc.getPage(p);
  const mid = (page.view[0] + page.view[2]) / 2;

  const recs = parsePage(await getItems(page), mid);
  if (recs.length === 0) continue;
  const photos = await findPhotos(page, mid);

  if (recs.length !== photos.length) {
    problems.push(`Page ${p}: ${recs.length} records but ${photos.length} photos; refusing to guess`);
    continue;
  }

  for (let i = 0; i < recs.length; i++) {
    const r = recs[i];
    if (Math.abs(photos[i].y - r.y) > 70) {
      problems.push(`Page ${p}: photo ${i + 1} is not level with its record (${r.vin})`);
    }
    const file = `${r.vin.replace(/ /g, '')}.jpg`;
    await savePhoto(page, photos[i].id, path.join(outDir, file));

    records.push({
      serial: records.length + 1,
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
