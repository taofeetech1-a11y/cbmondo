import assert from 'node:assert/strict';
import test from 'node:test';
import { parseRecords, matchLabelledPhoto } from '../../scripts/register-records.mjs';

function labelledRecord({ x = 78, y = 732, serial = 1, vin = '90F5AF7A59466848074', birth = 1995, gender = 'M', wrap = false } = {}) {
  return [
    { str: String(serial), x: x - 73, y: y + (wrap ? 20 : 12) },
    { str: 'TESTER, SAMPLE', x, y: y + (wrap ? 18 : 10) },
    ...(wrap ? [{ str: 'SECOND NAME', x, y: y + 10 }] : []),
    { str: 'VIN:', x, y },
    ...(vin ? [{ str: vin, x: x + 27, y }] : []),
    { str: `DOB-Y: ${birth}`, x, y: y - 9 },
    { str: '|', x: x + 42, y: y - 9 },
    { str: `Gender: ${gender}`, x: x + 50, y: y - 9 },
  ];
}

test('reads all three columns in column-first order and derives age from the print year', () => {
  const { records, problems, labelled } = parseRecords([
    ...labelledRecord({ x: 472, serial: 25, vin: 'INC22000000012524244', birth: 2003 }),
    ...labelledRecord({ x: 78, serial: 1 }),
    ...labelledRecord({ x: 275, serial: 13, gender: 'F', birth: 1983 }),
  ], 297, 2026);
  assert.equal(labelled, true);
  assert.deepEqual(problems, []);
  assert.deepEqual(records.map(({ serial, column, age, gender }) => ({ serial, column, age, gender })), [
    { serial: 1, column: 0, age: 31, gender: 'Male' },
    { serial: 13, column: 1, age: 43, gender: 'Female' },
    { serial: 25, column: 2, age: 23, gender: 'Male' },
  ]);
  assert.equal(records[2].vin, 'INC22000000012524244');
  assert.equal(records[0].occupation, null);
});

test('joins wrapped names above the VIN and preserves their surname and other names', () => {
  const { records, problems } = parseRecords(labelledRecord({ wrap: true }), 297, 2026);
  assert.deepEqual(problems, []);
  assert.equal(records[0].full_name, 'TESTER, SAMPLE SECOND NAME');
  assert.equal(records[0].surname, 'TESTER');
  assert.equal(records[0].other_names, 'SAMPLE SECOND NAME');
});

test('retains records with absent or invalid VINs and stores null without inventing an identifier', () => {
  for (const vin of [null, 'INVALID!']) {
    const result = parseRecords(labelledRecord({ serial: 386, vin }), 297, 2026);
    assert.deepEqual(result.problems, []);
    assert.equal(result.records.length, 1);
    assert.equal(result.records[0].vin, null);
    assert.equal(result.records[0].serial, 386);
  }
});

test('keeps voters with missing or invalid demographics as null rather than guessing', () => {
  for (const values of [{ birth: 2030 }, { birth: 1800 }, { gender: '?' }]) {
    const result = parseRecords(labelledRecord(values), 297, 2026);
    assert.equal(result.records.length, 1);
    assert.deepEqual(result.problems, []);
    assert.equal(values.gender ? result.records[0].gender : result.records[0].age, null);
  }
});

test('matches photos by their position instead of the old two-column ordering', () => {
  const record = { serial: 25, x: 472, y: 732 };
  const left = { right: 70, y: 726 };
  const right = { right: 464, y: 726 };
  const lower = { right: 464, y: 665 };
  const used = new Set();
  assert.equal(matchLabelledPhoto(record, [left, lower, right], used), right);
  assert.equal(matchLabelledPhoto(record, [right], used), null);
  assert.equal(matchLabelledPhoto(record, [right, { ...right }], new Set()), null);
});

test('keeps a recognizable record when its name, serial, demographics and photo are missing', () => {
  const result = parseRecords([{str: 'VIN:', x: 78, y: 732}], 297, 2026);
  assert.deepEqual(result.problems, []);
  assert.equal(result.records.length, 1);
  for (const field of ['vin', 'serial', 'full_name', 'surname', 'other_names', 'gender', 'age', 'occupation']) {
    assert.equal(result.records[0][field], null, field);
  }
});

test('finds a record with a missing VIN label using its birth-year row', () => {
  const result = parseRecords(labelledRecord().filter(item => item.str !== 'VIN:' && !/^[A-Z0-9]{10,30}$/.test(item.str)), 297, 2026);
  assert.equal(result.records.length, 1);
  assert.equal(result.records[0].vin, null);
  assert.equal(result.records[0].full_name, 'TESTER, SAMPLE');
});

test('reads gender independently when the birth-year field is absent', () => {
  const result = parseRecords(labelledRecord().filter(item => !item.str.startsWith('DOB-Y:')), 297, 2026);
  assert.equal(result.records[0].gender, 'Male');
  assert.equal(result.records[0].age, null);
});

test('keeps legacy records with a missing VIN rather than dropping them', () => {
  const result = parseRecords([
    {str: 'TESTER, SAMPLE', x: 100, y: 690},
    {str: 'Teacher', x: 100, y: 680},
    {str: 'Female 30', x: 100, y: 670},
  ], 297, 2026);
  assert.equal(result.records.length, 1);
  assert.equal(result.records[0].vin, null);
  assert.equal(result.records[0].full_name, 'TESTER, SAMPLE');
});

test('retains support for the old spaced-VIN layout', () => {
  const result = parseRecords([
    { str: 'AAAA BBBB CCCC DDDD 123', x: 100, y: 700 },
    { str: 'TESTER, SAMPLE', x: 100, y: 690 },
    { str: 'Teacher', x: 100, y: 680 },
    { str: 'Female 30', x: 100, y: 670 },
  ], 297, 2026);
  assert.equal(result.labelled, false);
  assert.deepEqual(result.problems, []);
  assert.equal(result.records[0].vin, 'AAAA BBBB CCCC DDDD 123');
  assert.equal(result.records[0].occupation, 'Teacher');
  assert.equal(result.records[0].gender, 'Female');
  assert.equal(result.records[0].age, 30);
});
