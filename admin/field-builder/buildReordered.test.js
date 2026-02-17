/**
 * Unit tests for the field-builder reorder logic (onEnd uses evt.oldIndex/newIndex).
 * Run with: node admin/field-builder/buildReordered.test.js
 *
 * Ensures the array-move logic never produces undefined or wrong length.
 */
(function () {
    'use strict';

    function moveItem(activeFields, oldIndex, newIndex) {
        var n = activeFields.length;
        if (oldIndex < 0 || oldIndex >= n || newIndex < 0 || newIndex >= n) return null;
        var arr = activeFields.slice();
        var item = arr.splice(oldIndex, 1)[0];
        arr.splice(newIndex, 0, item);
        return arr;
    }

    var passed = 0;
    var failed = 0;

    function ok(cond, msg) {
        if (cond) { passed++; return; }
        failed++;
        console.error('FAIL: ' + msg);
    }

    var f0 = { _uid: 'u0', type: 'text', label: 'Text' };
    var f1 = { _uid: 'u1', type: 'email', label: 'Email' };
    var f2 = { _uid: 'u2', type: 'wysiwyg', label: 'WYSIWYG' };
    var fields = [f0, f1, f2];

    var r1 = moveItem(fields, 0, 2);
    ok(r1 && r1.length === 3, 'move 0->2 length');
    ok(r1[0] === f1 && r1[1] === f2 && r1[2] === f0, 'move 0->2 order');

    var r2 = moveItem(fields, 2, 0);
    ok(r2 && r2.length === 3 && r2[0] === f2 && r2[1] === f0 && r2[2] === f1, 'move 2->0');

    var r3 = moveItem(fields, 1, 1);
    ok(r3 && r3.length === 3 && r3[0] === f0 && r3[1] === f1 && r3[2] === f2, 'no-op move 1->1');

    ok(moveItem(fields, -1, 0) === null, 'invalid oldIndex returns null');
    ok(moveItem(fields, 0, 99) === null, 'invalid newIndex returns null');

    var r4 = moveItem(fields, 0, 1);
    ok(r4 && r4.every(function (x) { return x != null; }), 'no undefined in result');

    console.log('Field builder reorder tests: ' + passed + ' passed, ' + failed + ' failed');
    process.exit(failed > 0 ? 1 : 0);
})();
