import http from 'k6/http';
import { check, sleep } from 'k6';


export const options = {
  stages: [
    { duration: '20s', target: 50 },  
    { duration: '40s', target: 100 }, 
    { duration: '1m', target: 100 }, 
    { duration: '20s', target: 0 },  
  ],
  
  thresholds: {
    'http_req_duration': ['p(95)<500', 'p(99)<1000'], 
    'http_req_failed': ['rate<0.01'],               
  },
};

export default function () {

  const url = 'http://localhost:8000/api/buy'; 
  
  const payload = JSON.stringify({
    product_id: 1,
    quantity: 1,
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
    },
  };

  const res = http.post(url, payload, params);

  check(res, {
    'is status 200 or 201': (r) => r.status === 200 || r.status === 201,
  });

  sleep(1);
}