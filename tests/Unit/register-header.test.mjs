import assert from 'node:assert/strict';
import test from 'node:test';
import { readHeader } from '../../scripts/register-header.mjs';

test('reads labelled cover-page locations, record total and print year', () => {
  const pairs = [['State', 'ONDO'], ['LGA', 'OWO'], ['Ward', 'EHINOGBE'], ['PU', 'TEST SCHOOL'], ['Printed', '26-09-2026'], ['Records', '630']];
  const items = pairs.flatMap(([label, value], index) => [
    { str: label, x: 103, y: 705 - index * 25 },
    { str: value, x: 208, y: 705 - index * 25 },
  ]);
  assert.deepEqual(readHeader(items), {
    header: { state: 'ONDO', lga: 'OWO', registration_area: 'EHINOGBE', polling_unit: 'TEST SCHOOL' },
    expected: 630, printedYear: 2026, delimitationCode: null,
  });
});

test('retains a split record total when the location header is missing', () => {
  assert.deepEqual(readHeader([
    { str: 'Total Records:', x: 10, y: 100 },
    { str: '23', x: 100, y: 100 },
  ]), { header: null, expected: 23, delimitationCode: null });
});

test('reads an inline total independently of the location header', () => {
  assert.deepEqual(readHeader([{ str: 'TOTAL RECORDS: 0', x: 10, y: 100 }]), {
    header: null, expected: 0, delimitationCode: null,
  });
});

test('returns unknown metadata for a page without a header or total', () => {
  assert.deepEqual(readHeader([]), { header: null, expected: null, delimitationCode: null });
});

test('supports different dash spacing and a wrapped polling-unit name', () => {
  assert.deepEqual(readHeader([
    { str: '28 - ONDO', x: 10, y: 200 },
    { str: '01–LGA', x: 10, y: 180 },
    { str: '02 — WARD', x: 10, y: 160 },
    { str: '003-PRIMARY', x: 10, y: 140 },
    { str: 'SCHOOL', x: 10, y: 130 },
    { str: 'Total Records: 42', x: 10, y: 100 },
  ]), {
    header: { state: '28 - ONDO', lga: '01–LGA', registration_area: '02 — WARD', polling_unit: '003-PRIMARY SCHOOL' },
    expected: 42, delimitationCode: '28/01/02/003',
  });
});

test('reads the Delim field regardless of the polling unit name and normalizes separators', () => {
  for (const code of ['28-18-01-003', '28/18/01/003', '28 – 18 – 1 – 3']) {
    const result = readHeader([
      {str: 'PU', x: 103, y: 630}, {str: 'RELOCATED POLLING UNIT', x: 208, y: 630},
      {str: 'Delim', x: 103, y: 555}, {str: code, x: 208, y: 555},
    ]);
    assert.equal(result.delimitationCode, '28/18/01/003');
  }
});

test('reads an inline delimitation code and code fragments on the same header row', () => {
  assert.equal(readHeader([{str: 'Delimitation Code: 28/18/01/003', x: 100, y: 555}]).delimitationCode, '28/18/01/003');
  const items = ['Delim', '28', '-', '18', '-', '01', '-', '003'].map((str, index) => ({str, x: index * 30, y: 555}));
  assert.equal(readHeader(items).delimitationCode, '28/18/01/003');
});

test('does not treat a partial unit number or printed date as a full delimitation code', () => {
  assert.equal(readHeader([{str: '003', x: 100, y: 555}, {str: '26-09-2026', x: 100, y: 530}]).delimitationCode, null);
});
