import http from 'k6/http';
import { check, sleep, group } from 'k6';

const BASE_URL = __ENV.BASE_URL || 'http://127.0.0.1:8080';
const REGIONS = ['Riyadh', 'Jeddah', 'Dammam', 'Abha', 'Tabuk'];

export const options = {
    vus: 100,
    duration: '1m',
    thresholds: {
        http_req_duration: ['p(95)<1200'],
        http_req_failed: ['rate<0.05'],
    },
};

function randomInt(min, max) {
    return Math.floor(Math.random() * (max - min + 1)) + min;
}

function buildUser(vu) {
    return {
        userId: vu,
        walletId: `wallet-${vu}-${randomInt(1000, 9999)}`,
        orderId: `order-${vu}-${Date.now()}`,
        region: REGIONS[randomInt(0, REGIONS.length - 1)],
    };
}

function sendRequest(method, url, body = null, extraTags = {}) {
    const params = {
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            Accept: 'application/json',
        },
        tags: extraTags,
    };

    if (method === 'POST') {
        return http.post(url, body, params);
    }

    return http.get(url, params);
}

export default function () {
    const user = buildUser(__VU);
    const commonTags = {
        user_id: user.userId.toString(),
        wallet_id: user.walletId,
        region: user.region,
        order_id: user.orderId,
    };

    group('Full-system user journey', function () {
        group('1 - Browse products (first time)', function () {
            const res = sendRequest('GET', `${BASE_URL}/products`, null, {
                ...commonTags,
                step: 'browse_1',
            });
            check(res, {
                'browse first time is 200': (r) => r.status === 200,
                'browse first time has json': (r) => r.headers['Content-Type'] && r.headers['Content-Type'].includes('application/json'),
            });
        });

        sleep(Math.random() * 1.5 + 0.5);

        group('2 - Browse products (cache hit)', function () {
            const res = sendRequest('GET', `${BASE_URL}/products`, null, {
                ...commonTags,
                step: 'browse_2',
            });
            check(res, {
                'browse cache hit is 200': (r) => r.status === 200,
            });
        });

        sleep(Math.random() * 1.5 + 0.5);

        group('3 - Place order (buy endpoint)', function () {
            const body = `product_id=1&quantity=1&user_id=${user.userId}&wallet_id=${user.walletId}&region=${user.region}`;
            const res = sendRequest('POST', `${BASE_URL}/buy`, body, {
                ...commonTags,
                step: 'place_order',
            });
            check(res, {
                'buy status is 200 or 400/422/423': (r) => [200, 400, 422, 423, 429].includes(r.status),
            });
        });

        sleep(Math.random() * 1.5 + 0.5);

        group('4 - Validate product list after order', function () {
            const res = sendRequest('GET', `${BASE_URL}/products`, null, {
                ...commonTags,
                step: 'browse_after_order',
            });
            check(res, {
                'browse after order is 200': (r) => r.status === 200,
            });
        });

        sleep(Math.random() * 1.5 + 0.5);

        group('5 - Optional second buy or checkout', function () {
            if (Math.random() < 0.3) {
                const body = `product_id=1&quantity=1&user_id=${user.userId}&wallet_id=${user.walletId}&region=${user.region}`;
                const res = sendRequest('POST', `${BASE_URL}/buy`, body, {
                    ...commonTags,
                    step: 'second_buy',
                });
                check(res, {
                    'second buy returns valid status': (r) => [200, 400, 422, 423, 429].includes(r.status),
                });
            }
        });

        sleep(Math.random() * 1.5 + 0.5);

        group('6 - Test distributed lock purchase ( befor end)', function () {
            const body = `product_id=1&quantity=1&user_id=${user.userId}&wallet_id=${user.walletId}&region=${user.region}`;
            const res = sendRequest('POST', `${BASE_URL}/purchase-distributed-lock`, body, {
                ...commonTags,
                step: 'distributed_lock',
            });
            check(res, {
                'distributed lock status is 200 or 423/422': (r) => [200, 423, 422].includes(r.status),
            });
        });

        sleep(Math.random() * 1.5 + 0.5);

        group('7 - Periodic batch process request (iterator)', function () {
            if (Math.random() < 0.25) {
                const res = sendRequest('GET', `${BASE_URL}/start-parallel-test`, null, {
                    ...commonTags,
                    step: 'periodic_batch',
                });
                check(res, {
                    'periodic batch returns 200': (r) => r.status === 200,
                });
            }
        });
    });

    sleep(Math.random() * 2 + 1);
}
