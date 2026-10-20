function normalizeCode(parts) {
  if (parts.length !== 4 || !parts.every((part, index) => new RegExp(`^\\d{1,${index === 3 ? 3 : 2}}$`).test(part))) return null;
  return parts.map((part, index) => part.padStart(index === 3 ? 3 : 2, '0')).join('/');
}

function fullCode(text) {
  const match = text.match(/\b(\d{1,2})\s*[-/–—]\s*(\d{1,2})\s*[-/–—]\s*(\d{1,2})\s*[-/–—]\s*(\d{1,3})\b/);
  return match ? normalizeCode(match.slice(1)) : null;
}

function headerCode(items, locations) {
  const delim = items.find(item => /^(?:Delim(?:itation)?(?:\s+Code)?|PU\s+Code|Polling\s+Unit\s+Code)\s*:?/i.test(item.str));
  if (delim) {
    const line = items.filter(item => Math.abs(item.y - delim.y) < 3 && item.x >= delim.x)
      .sort((a, b) => a.x - b.x).map(item => item.str).join(' ');
    const code = fullCode(line);
    if (code) return code;
  }
  for (const item of items) {
    const code = fullCode(item.str);
    if (code) return code;
  }
  return normalizeCode(locations.map(location => location?.match(/^\s*(\d{1,3})\s*[-–—]\s*\S/)?.[1] ?? ''));
}

export function readHeader(items) {
  const coded = items
    .filter((item) => /^\d{2,3}\s*[-–—]\s*\S/.test(item.str))
    .sort((a, b) => b.y - a.y);
  const label = items.find((item) => /^(?:Total\s+)?Records\s*:?\s*$/i.test(item.str));
  const inlineTotal = items.map((item) => item.str.match(/^(?:Total\s+)?Records\s*:?\s*(\d+)\s*$/i)).find(Boolean);
  const totalItem = label && items
    .filter((item) => /^\d+$/.test(item.str) && Math.abs(item.y - label.y) < 3 && item.x > label.x)
    .sort((a, b) => a.x - b.x)[0];
  const expected = inlineTotal ? Number(inlineTotal[1]) : totalItem ? Number(totalItem.str) : null;

  const valueFor = (pattern) => {
    const field = items.find((item) => pattern.test(item.str));
    return field && items.filter((item) => Math.abs(item.y - field.y) < 3 && item.x > field.x)
      .sort((a, b) => a.x - b.x)[0]?.str;
  };
  const stateName = valueFor(/^State\s*:?$/i);
  const lgaName = valueFor(/^(?:LGA|Local Govt\. Area)\s*:?$/i);
  const areaName = valueFor(/^(?:Ward|Registration Area)\s*:?$/i);
  const puName = valueFor(/^PU\s*:?$/i);
  if (stateName && lgaName && areaName && puName) {
    const printed = valueFor(/^(?:Printed|Time Printed)\s*:?$/i);
    const year = printed?.match(/\b(\d{2})[-/](\d{2})[-/](\d{4})\b/)?.[3];
    return {
      header: { state: stateName, lga: lgaName, registration_area: areaName, polling_unit: puName },
      expected,
      delimitationCode: headerCode(items, [stateName, lgaName, areaName, puName]),
      printedYear: year ? Number(year) : null,
    };
  }

  if (coded.length < 4) {
    return { header: null, expected, delimitationCode: headerCode(items, []) };
  }

  const [state, lga, area, pu] = coded;
  const second = items
    .filter((item) => Math.abs(item.x - pu.x) < 3 && item.y < pu.y && pu.y - item.y < 14)
    .sort((a, b) => b.y - a.y)[0];

  return {
    header: {
      state: state.str,
      lga: lga.str,
      registration_area: area.str,
      polling_unit: [pu.str, second?.str].filter(Boolean).join(' ').replace(/\s+/g, ' ').trim(),
    },
    expected,
    delimitationCode: headerCode(items, [state.str, lga.str, area.str, pu.str]),
  };
}
