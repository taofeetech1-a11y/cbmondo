export function readHeader(items) {
  const coded = items
    .filter((item) => /^\d{2,3}\s*[-–—]\s*\S/.test(item.str))
    .sort((a, b) => b.y - a.y);
  const label = items.find((item) => /^Total\s+Records\s*:?\s*$/i.test(item.str));
  const inlineTotal = items.map((item) => item.str.match(/^Total\s+Records\s*:?\s*(\d+)\s*$/i)).find(Boolean);
  const totalItem = label && items
    .filter((item) => /^\d+$/.test(item.str) && Math.abs(item.y - label.y) < 3 && item.x > label.x)
    .sort((a, b) => a.x - b.x)[0];
  const expected = inlineTotal ? Number(inlineTotal[1]) : totalItem ? Number(totalItem.str) : null;

  if (coded.length < 4) {
    return { header: null, expected };
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
  };
}
