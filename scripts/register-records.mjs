const legacyVin = /^[0-9A-Z]{4}(?: [0-9A-Z]{4}){3} \d{3}$/;
const legacyGender = /^(Male|Female)\b\s*(\d{1,3})?$/;
const sortRecords = (a, b) => a.column - b.column || b.y - a.y;

function parseLegacyPage(items, mid) {
  const anchors = items.filter((item) => legacyVin.test(item.str));
  for (const name of items.filter((item) => item.str.includes(',') && !/^(State|LGA|Registration|Polling)/i.test(item.str))) {
    const gender = items.find((item) => legacyGender.test(item.str) && Math.abs(item.x - name.x) < 4
      && item.y < name.y && name.y - item.y < 40);
    if (gender && !anchors.some((vin) => Math.abs(vin.x - name.x) < 4 && vin.y > name.y && vin.y - name.y < 20)) {
      anchors.push({str: null, x: name.x, y: name.y + 10});
    }
  }
  return anchors.map((vin) => {
    const column = items.filter((item) => Math.abs(item.x - vin.x) < 4 && item.y < vin.y && vin.y - item.y < 45)
      .sort((a, b) => b.y - a.y);
    const genderIndex = column.findIndex((item) => legacyGender.test(item.str));
    const gender = genderIndex < 0 ? null : column[genderIndex].str.match(legacyGender);
    const age = gender?.[2] ?? (genderIndex < 0 ? null : items.filter((item) => /^\d{1,3}$/.test(item.str)
      && Math.abs(item.y - column[genderIndex].y) < 3 && item.x > column[genderIndex].x)
      .sort((a, b) => a.x - b.x)[0]?.str);
    const fullName = genderIndex === 0 ? null : column[0]?.str ?? null;
    const [surname, ...otherNames] = (fullName ?? '').split(',');
    return {
      vin: vin.str, x: vin.x, y: vin.y, column: vin.x > mid ? 1 : 0,
      full_name: fullName, surname: surname.trim() || null, other_names: otherNames.join(',').trim() || null,
      occupation: genderIndex > 1 ? column.slice(1, genderIndex).map((item) => item.str).join(' ') : null,
      gender: gender?.[1] ?? null, age: age && Number(age) <= 130 ? Number(age) : null,
    };
  }).sort(sortRecords);
}

export function parseRecords(items, mid, referenceYear) {
  const labels = items.filter((item) => /^VIN\s*:/i.test(item.str));
  for (const birth of items.filter((item) => /^DOB-Y\s*:/i.test(item.str))) {
    if (!labels.some((label) => Math.abs(label.x - birth.x) < 3 && label.y > birth.y && label.y - birth.y < 20)) {
      labels.push({ str: 'VIN:', x: birth.x, y: birth.y + 9 });
    }
  }
  for (const gender of items.filter((item) => /^Gender\s*:/i.test(item.str))) {
    const x = gender.x - 50;
    if (!labels.some((label) => gender.x >= label.x && gender.x - label.x < 145 && label.y > gender.y && label.y - gender.y < 20)) {
      labels.push({ str: 'VIN:', x, y: gender.y + 9 });
    }
  }
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
    const inlineVin = label.str.replace(/^VIN\s*:\s*/i, '').trim();
    const rawVin = inlineVin || items.filter((item) => Math.abs(item.y - label.y) < 2
      && item.x > label.x && item.x - label.x < 145 && /^[A-Z0-9]{10,30}$/.test(item.str))
      .sort((a, b) => a.x - b.x)[0]?.str;
    const vin = rawVin && /^[A-Z0-9]{10,30}$/.test(rawVin) ? rawVin : null;
    const fullName = items.filter((item) => Math.abs(item.x - label.x) < 3
      && item.y > label.y && item.y - label.y < 35 && !/^\d+$|^(VIN|DOB-Y|Gender)\s*:/i.test(item.str))
      .sort((a, b) => b.y - a.y).map((item) => item.str).join(' ').trim();
    const birth = items.find((item) => Math.abs(item.x - label.x) < 3
      && item.y < label.y && label.y - item.y < 20 && /^DOB-Y:\s*\d{4}$/i.test(item.str));
    const gender = items.find((item) => Math.abs(item.y - (birth?.y ?? label.y - 9)) < 2
      && item.x >= label.x && item.x - label.x < 145 && /^Gender:\s*(M|F|Male|Female)$/i.test(item.str));
    const birthYear = birth ? Number(birth.str.match(/\d{4}/)[0]) : null;
    const derivedAge = birthYear === null ? NaN : referenceYear - birthYear;
    const age = Number.isInteger(derivedAge) && derivedAge >= 0 && derivedAge <= 130 ? derivedAge : null;
    const [surname, ...otherNames] = fullName.split(',');
    records.push({
      serial: serial && Number(serial) > 0 ? Number(serial) : null, vin, full_name: fullName || null, surname: surname.trim() || null,
      other_names: otherNames.join(',').trim() || null, occupation: null,
      gender: gender ? (/^Gender:\s*(M|Male)$/i.test(gender.str) ? 'Male' : 'Female') : null,
      age, birth_year: age === null ? null : birthYear, x: label.x, y: label.y,
      column: columns.findIndex((x) => Math.abs(x - label.x) < 4),
    });
  }
  return { records: records.sort(sortRecords), problems, labelled: true };
}

export function matchLabelledPhoto(record, photos, used) {
  const candidates = photos.filter((photo) => !used.has(photo)
    && photo.right <= record.x + 3 && record.x - photo.right < 40
    && Math.abs(photo.y - record.y) < 30);
  if (candidates.length !== 1) return null;
  used.add(candidates[0]);
  return candidates[0];
}
