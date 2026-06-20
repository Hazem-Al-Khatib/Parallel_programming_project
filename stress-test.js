import http from 'k6/http';
import { check, sleep } from 'k6';

export const options = {
  stages: [
    { duration: '10s', target: 10 },  
    { duration: '30s', target: 10 },  
    { duration: '10s', target: 0 }, 
  ],
  
   thresholds: {
   
    'http_req_duration': ['p(95)<30000'], 
 
    'http_req_failed': ['rate<0.05'], 
  },
};

export default function () {

  const url = 'http://127.0.0.1:8000/api/purchase';
  
  const payload = JSON.stringify({
    product_id: 1,  
    quantity: 1
  });
  
  const params = {
    headers: { 
      'Content-Type': 'application/json',
      'Accept': 'application/json'
    },
  };

  
  const res = http.post(url, payload, params);

check(res, {
    'Success or Rejected': (r) => r.status === 200 || r.status === 422 ||  r.status === 423 || r.status === 500,
    'Response contains message': (r) => r.body.includes('message'),
});

  if (res.status !== 200) {
    console.log(' Failed request: Status ${res.status} | Body: ${res.body}');
    console.log('Response: ${res.body}');
  } else {
    console.log('Successful request: Status ${res.status}');
  }

  sleep(0.1);
}