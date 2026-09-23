const fs = require('fs');

const filename = process.argv[2] || 'jmeter-results.jtl';
const content = fs.readFileSync(filename, 'utf8');
const lines = content.trim().split('\n');
const headers = lines[0].split(',');

let total = 0;
let passed = 0;
let failed = 0;
let totalLatency = 0;
let minLatency = Infinity;
let maxLatency = 0;
const failureReasons = {};

for (let i = 1; i < lines.length; i++) {
    const parts = lines[i].split(',');
    if (parts.length < 8) continue;
    total++;
    const latency = parseInt(parts[1], 10) || 0;
    const success = parts[7] === 'true';
    totalLatency += latency;
    if (latency < minLatency) minLatency = latency;
    if (latency > maxLatency) maxLatency = latency;

    if (success) {
        passed++;
    } else {
        failed++;
        const msg = parts[4] || 'Unknown error';
        failureReasons[msg] = (failureReasons[msg] || 0) + 1;
    }
}

const avgLatency = Math.round(totalLatency / (total || 1));
const stats = {
    totalRequests: total,
    passedRequests: passed,
    failedRequests: failed,
    successRate: ((passed / (total || 1)) * 100).toFixed(2) + '%',
    avgLatencyMs: avgLatency,
    minLatencyMs: minLatency,
    maxLatencyMs: maxLatency,
    failureReasons
};

console.log(JSON.stringify(stats, null, 2));
