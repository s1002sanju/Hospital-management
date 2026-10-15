"""Double-booking race test: N patients try to book the SAME slot at the same moment.
Expected result: exactly 1 success, N-1 rejections.
Usage:  pip install requests
        python tests/race_test.py --base http://localhost:8000 --slot-id 1 --users 50
"""
import argparse, re, threading, uuid
import requests

p = argparse.ArgumentParser()
p.add_argument("--base", default="http://localhost:8000")
p.add_argument("--slot-id", type=int, required=True)
p.add_argument("--users", type=int, default=50)
a = p.parse_args()
TOKEN = re.compile(r'name="csrf" value="([a-f0-9]+)"')


def token(s, path):
    return TOKEN.search(s.get(a.base + path).text).group(1)


def make_user(i):
    s = requests.Session(); email = f"race_{uuid.uuid4().hex[:8]}@test.com"
    s.post(a.base + "/register.php", data={"csrf": token(s, "/register.php"), "name": f"Race {i}",
           "email": email, "phone": "9999999999", "password": "Passw0rd123"})
    s.post(a.base + "/login.php", data={"csrf": token(s, "/login.php"), "email": email, "password": "Passw0rd123"})
    return s, token(s, "/book.php")


print(f"Preparing {a.users} users...")
sessions = [make_user(i) for i in range(a.users)]
results, barrier = [], threading.Barrier(a.users)


def attempt(s, csrf):
    barrier.wait()                                   # release all requests together
    r = s.post(a.base + "/book.php", data={"csrf": csrf, "slot_id": a.slot_id}, allow_redirects=True)
    results.append("confirmed" in r.text.lower() and "just taken" not in r.text.lower())


threads = [threading.Thread(target=attempt, args=sc) for sc in sessions]
[t.start() for t in threads]; [t.join() for t in threads]
ok = sum(results)
print(f"Successful bookings: {ok} | Rejected: {len(results) - ok}")
print("PASS: no double booking" if ok == 1 else "FAIL: check booking logic")
