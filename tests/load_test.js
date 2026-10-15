// k6 load test.  Install k6: https://k6.io/docs/get-started/installation/
// Run:  k6 run -e BASE=https://YOUR-ENV.elasticbeanstalk.com -e DOCTOR=1 -e DATE=2026-11-02 tests/load_test.js
// Stages = 10, 50, 100, 500 concurrent virtual users (as suggested in the evaluation rubric).
import http from 'k6/http';
import { check, sleep } from 'k6';

const BASE = __ENV.BASE || 'http://localhost:8000';
export const options = {
  stages: [
    { duration: '30s', target: 10 }, { duration: '1m', target: 10 },
    { duration: '30s', target: 50 }, { duration: '1m', target: 50 },
    { duration: '30s', target: 100 }, { duration: '1m', target: 100 },
    { duration: '1m', target: 500 }, { duration: '2m', target: 500 },
    { duration: '30s', target: 0 },
  ],
  thresholds: { http_req_failed: ['rate<0.02'], http_req_duration: ['p(95)<2000'] },
};
export default function () {
  const r1 = http.get(`${BASE}/index.php`);
  const r2 = http.get(`${BASE}/api/slots.php?doctor_id=${__ENV.DOCTOR || 1}&date=${__ENV.DATE}`);
  check(r1, { 'home 200': (r) => r.status === 200 });
  check(r2, { 'slots 200': (r) => r.status === 200 });
  sleep(1);
}
