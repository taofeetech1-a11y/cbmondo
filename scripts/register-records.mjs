const legacyVin = /^[0-9A-Z]{4}(?: [0-9A-Z]{4}){3} \d{3}$/;
const legacyGender = /^(Male|Female)\b\s*(\d{1,3})?$/;
const sortRecords = (a, b) => a.column - b.column || b.y - a.y;

function parseLegacyPage(items, mid) {
  return items.filter((item) => legacyVin.test(item.str)).map((vin) => {
    const column = items.filter((item) => Math.abs(item.x - vin.x) < 4 && item.y < vin.y && vin.y - item.y < 45)
      .sort((a, b) => b.y - a.y);
    const genderIndex = column.findIndex((item) => legacyGender.test(item.str));
    if (genderIndex < 1) throw new Error(`Could not parse record with VIN ${vin.str}`);
    const gender = column[genderIndex].str.match(legacyGender);
    const age = gender[2] ?? items.filter((item) => /^\d{1,3}$/.test(item.str)
      && Math.abs(item.y - column[genderIndex].y) < 3 && item.x > column[genderIndex].x)
      .sort((a, b) => a.x - b.x)[0]?.str;
    const fullName = column[0].str;
    const [surname, ...otherNames] = fullName.split(',');
    return {
      vin: vin.str, x: vin.x, y: vin.y, column: vin.x > mid ? 1 : 0,
      full_name: fullName, surname: surname.trim(), other_names: otherNames.join(',').trim(),
      occupation: genderIndex > 1 ? column.slice(1, genderIndex).map((item) => item.str).join(' ') : null,
      gender: gender[1], age: Number(age),
    };
  }).sort(sortRecords);
}

export function parseRecords(items, mid, referenceYear) {
  const labels = items.filter((item) => /^VIN\s*:/i.test(item.str));
  if (!labels.length) return { records: parseLegacyPage(items, mid), problems: [], labelled: false };

  const columns = [];
  for (const label of [...labels].sort((a, b) => a.x - b.x)) {
    if (!columns.some((x) => Math.abs(x - label.x) < 4)) columns.push(label.x);
  }
  const records = [];
  const problems = [];
  for (const label of labels) {
    const serial = items.filter((item) => /^\d+$/.test(item.str) && item.x < label.x
      && label.x - item.x < 100 && item.y > label.y && item.y - label.y < 35)
      .sort((a, b) => Math.abs(a.y - label.y) - Math.abs(b.y - label.y))[0]?.str;
    const context = `Record ${serial ?? 'at '+label.x+','+label.y}`;
    const inlineVin = label.str.replace(/^VIN\s*:\s*/i, '').trim();
    const vin = inlineVin || items.filter((item) => Math.abs(item.y - label.y) < 2
      && item.x > label.x && item.x - label.x < 145 && /^[A-Z0-9]{10,30}$/.test(item.str))
      .sort((a, b) => a.x - b.x)[0]?.str;
    if (!vin || !/^[A-Z0-9]{10,30}$/.test(vin)) {
      problems.push(`${context}: VIN is missing or invalid; correct the PDF before importing.`);
      continue;
    }
    const fullName = items.filter((item) => Math.abs(item.x - label.x) < 3
      && item.y > label.y && item.y - label.y < 35)
      .sort((a, b) => b.y - a.y).map((item) => item.str).join(' ').trim();
    const birth = items.find((item) => Math.abs(item.x - label.x) < 3
      && item.y < label.y && label.y - item.y < 20 && /^DOB-Y:\s*\d{4}$/i.test(item.str));
    const gender = birth && items.find((item) => Math.abs(item.y - birth.y) < 2
      && item.x >= label.x && item.x - label.x < 145 && /^Gender:\s*(M|F|Male|Female)$/i.test(item.str));
    const birthYear = birth ? Number(birth.str.match(/\d{4}/)[0]) : null;
    const age = birthYear === null ? NaN : referenceYear - birthYear;
    if (!fullName || !gender || !Number.isInteger(age) || age < 0 || age > 130 || !serial) {
      problems.push(`${context}: name, serial number, birth year or gender could not be read reliably.`);
      continue;
    }
    const [surname, ...otherNames] = fullName.split(',');
    records.push({
      serial: Number(serial), vin, full_name: fullName, surname: surname.trim(),
      other_names: otherNames.join(',').trim(), occupation: null,
      gender: /^Gender:\s*(M|Male)$/i.test(gender.str) ? 'Male' : 'Female',
      age, birth_year: birthYear, x: label.x, y: label.y,
      column: columns.findIndex((x) => Math.abs(x - label.x) < 4),
    });
  }
  return { records: records.sort(sortRecords), problems, labelled: true };
}

export function matchLabelledPhoto(record, photos, used) {
  const candidates = photos.filter((photo) => !used.has(photo)
    && photo.right <= record.x + 3 && record.x - photo.right < 40
    && Math.abs(photo.y - record.y) < 30);
  if (candidates.length !== 1) {
    throw new Error(`Record ${record.serial}: expected one aligned photo, found ${candidates.length}; refusing to guess.`);
  }
  used.add(candidates[0]);
  return candidates[0];
}
