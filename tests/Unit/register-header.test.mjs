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
    expected: 630, printedYear: 2026,
  });
});

test('retains a split record total when the location header is missing', () => {
  assert.deepEqual(readHeader([
    { str: 'Total Records:', x: 10, y: 100 },
    { str: '23', x: 100, y: 100 },
  ]), { header: null, expected: 23 });
});

test('reads an inline total independently of the location header', () => {
  assert.deepEqual(readHeader([{ str: 'TOTAL RECORDS: 0', x: 10, y: 100 }]), {
    header: null, expected: 0,
  });
});

test('returns unknown metadata for a page without a header or total', () => {
  assert.deepEqual(readHeader([]), { header: null, expected: null });
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
    expected: 42,
  });
});
