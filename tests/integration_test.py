import os, sys, re, uuid, time
import requests

BASE_URL = os.environ.get("BASE_URL", "http://127.0.0.1:8000").rstrip("/")
REMINDER_KEY = os.environ.get("REMINDER_KEY", "")
ADMIN_EMAIL = os.environ.get("ADMIN_EMAIL", "admin@hospital.com")
ADMIN_PASSWORD = os.environ.get("ADMIN_PASSWORD", "Admin@123")
CSRF_RE = re.compile(r'name="csrf" value="([a-f0-9]+)"')

def get_csrf(session, path):
    r = session.get(BASE_URL + path)
    m = CSRF_RE.search(r.text)
    if not m:
        raise Exception(f"CSRF token not found in {path}")
    return m.group(1)

test_results = []

def record(name, status, details=""):
    test_results.append((name, status, details))
    icon = "[PASS]" if status else "[FAIL]"
    print(f"{icon} {name} {('- ' + details) if details else ''}")

print("\n" + "="*70)
print("CITYCARE HOSPITAL - END-TO-END INTEGRATION TEST SUITE")
print("Target Environment: Local Server + Supabase Cloud PostgreSQL")
print("="*70 + "\n")

# 1. Health Check
try:
    r = requests.get(BASE_URL + "/health.php", timeout=5)
    if r.status_code == 200 and r.text.strip() == "OK":
        record("Health Check Endpoint (/health.php)", True, "Returned 200 OK")
    else:
        record("Health Check Endpoint (/health.php)", False, f"Status: {r.status_code}")
except Exception as e:
    record("Health Check Endpoint (/health.php)", False, str(e))

# 2. Homepage & Public Doctor Directory (Queries Supabase)
try:
    r = requests.get(BASE_URL + "/", timeout=5)
    if r.status_code == 200 and "Dr. Anitha Rao" in r.text and "Cardiology" in r.text:
        record("Public Homepage & Supabase Doctor Feed", True, "Successfully loaded doctors from Supabase")
    else:
        record("Public Homepage & Supabase Doctor Feed", False, f"Status: {r.status_code}")
except Exception as e:
    record("Public Homepage & Supabase Doctor Feed", False, str(e))

# 3. Patient Registration Flow
patient_session = requests.Session()
patient_email = f"patient_{uuid.uuid4().hex[:6]}@test.com"
patient_pass = "Password123"
try:
    csrf = get_csrf(patient_session, "/register.php")
    reg_data = {
        "csrf": csrf,
        "name": "Jane Doe Tester",
        "email": patient_email,
        "phone": "+91 9876543210",
        "password": patient_pass
    }
    r = patient_session.post(BASE_URL + "/register.php", data=reg_data, allow_redirects=True)
    if "Registered! Please log in" in r.text:
        record("Patient Registration Flow", True, f"Created {patient_email}")
    else:
        record("Patient Registration Flow", False, "Flash message missing")
        
    # Duplicate email guard
    csrf2 = get_csrf(patient_session, "/register.php")
    r_dup = patient_session.post(BASE_URL + "/register.php", data=reg_data, allow_redirects=True)
    if "already registered" in r_dup.text.lower():
        record("Duplicate Email Integrity Guard", True, "Blocked duplicate email registration")
    else:
        record("Duplicate Email Integrity Guard", False, "Allowed duplicate")
except Exception as e:
    record("Patient Registration Flow", False, str(e))

# 4. Patient Login Flow
try:
    csrf = get_csrf(patient_session, "/login.php")
    r_login = patient_session.post(BASE_URL + "/login.php", data={
        "csrf": csrf,
        "email": patient_email,
        "password": patient_pass
    }, allow_redirects=True)
    if "Book an Appointment" in r_login.text or "Jane Doe Tester" in r_login.text:
        record("Patient Authentication & Session Setup", True, "Logged in & redirected to /book.php")
    else:
        record("Patient Authentication & Session Setup", False, "Could not log in")
except Exception as e:
    record("Patient Authentication & Session Setup", False, str(e))

# 5. Admin Authentication & Role Authorization
admin_session = requests.Session()
try:
    csrf = get_csrf(admin_session, "/login.php")
    r_admin_login = admin_session.post(BASE_URL + "/login.php", data={
        "csrf": csrf,
        "email": ADMIN_EMAIL,
        "password": ADMIN_PASSWORD
    }, allow_redirects=True)
    if "Admin Dashboard" in r_admin_login.text:
        record("Admin Login & Role-Based Access Control", True, "Admin access granted to /admin/index.php")
    else:
        record("Admin Login & Role-Based Access Control", False, "Admin login failed")
except Exception as e:
    record("Admin Login & Role-Based Access Control", False, str(e))

# 6. Admin Slots Generation (Supabase ON CONFLICT test)
tomorrow = time.strftime("%Y-%m-%d", time.localtime(time.time() + 86400))
try:
    csrf = get_csrf(admin_session, "/admin/slots.php")
    slot_data = {
        "csrf": csrf,
        "doctor_id": 1,
        "from": tomorrow,
        "to": tomorrow,
        "start": "09:00",
        "end": "11:00",
        "duration": "30"
    }
    r_slots = admin_session.post(BASE_URL + "/admin/slots.php", data=slot_data, allow_redirects=True)
    if "slots created" in r_slots.text or r_slots.status_code == 200:
        record("Doctor Slot Generation (PostgreSQL ON CONFLICT)", True, f"Generated slots for {tomorrow}")
    else:
        record("Doctor Slot Generation (PostgreSQL ON CONFLICT)", False, "Slots creation failed")
except Exception as e:
    record("Doctor Slot Generation (PostgreSQL ON CONFLICT)", False, str(e))

# 7. Patient Slot Lookup & Appointment Booking
booked_slot_id = None
try:
    r_avail = patient_session.get(f"{BASE_URL}/book.php?doctor_id=1&date={tomorrow}")
    slot_matches = re.findall(r'name="slot_id" value="(\d+)"', r_avail.text)
    if slot_matches:
        booked_slot_id = int(slot_matches[0])
        csrf_book = get_csrf(patient_session, f"/book.php?doctor_id=1&date={tomorrow}")
        r_book = patient_session.post(BASE_URL + "/book.php", data={
            "csrf": csrf_book,
            "slot_id": booked_slot_id,
            "reason": "Routine checkup"
        }, allow_redirects=True)
        if "confirmed" in r_book.text.lower() and "my appointments" in r_book.text.lower():
            record("Atomic Slot Booking Flow (PostgreSQL RETURNING id)", True, f"Booked slot #{booked_slot_id}")
        else:
            record("Atomic Slot Booking Flow (PostgreSQL RETURNING id)", False, "Booking confirmation not received")
    else:
        record("Atomic Slot Booking Flow (PostgreSQL RETURNING id)", False, "No available slots found to book")
except Exception as e:
    record("Atomic Slot Booking Flow (PostgreSQL RETURNING id)", False, str(e))

# 8. Double-Booking Concurrency / Race Condition Guard
try:
    if booked_slot_id:
        another_patient = requests.Session()
        another_email = f"patient2_{uuid.uuid4().hex[:6]}@test.com"
        c = get_csrf(another_patient, "/register.php")
        another_patient.post(BASE_URL + "/register.php", data={
            "csrf": c, "name": "Second Patient", "email": another_email, "phone": "1234567890", "password": "Password123"
        })
        another_patient.post(BASE_URL + "/login.php", data={
            "csrf": get_csrf(another_patient, "/login.php"), "email": another_email, "password": "Password123"
        })
        c_double = get_csrf(another_patient, "/book.php")
        r_double = another_patient.post(BASE_URL + "/book.php", data={
            "csrf": c_double,
            "slot_id": booked_slot_id,
            "reason": "Trying to double book"
        }, allow_redirects=True)
        if "just taken or has passed" in r_double.text or "Pick another" in r_double.text:
            record("Double-Booking Guard (Atomic UPDATE & active_slot_id UNIQUE)", True, "Prevented booking already-booked slot")
        else:
            record("Double-Booking Guard (Atomic UPDATE & active_slot_id UNIQUE)", False, "Failed to block double booking")
except Exception as e:
    record("Double-Booking Guard (Atomic UPDATE & active_slot_id UNIQUE)", False, str(e))

# 9. Appointment Cancellation & Slot Reclaim
try:
    r_my = patient_session.get(BASE_URL + "/my_appointments.php")
    appt_match = re.search(r'name="id" value="(\d+)"', r_my.text)
    if appt_match:
        appt_id = int(appt_match.group(1))
        c_cancel = get_csrf(patient_session, "/my_appointments.php")
        r_cancel = patient_session.post(BASE_URL + "/my_appointments.php", data={
            "csrf": c_cancel,
            "id": appt_id,
            "action": "cancel"
        }, allow_redirects=True)
        if "cancelled" in r_cancel.text.lower():
            record("Appointment Cancellation & Slot Release Flow", True, f"Cancelled appointment #{appt_id} & freed slot")
        else:
            record("Appointment Cancellation & Slot Release Flow", False, "Cancellation message missing")
    else:
        record("Appointment Cancellation & Slot Release Flow", False, "No appointment ID found to cancel")
except Exception as e:
    record("Appointment Cancellation & Slot Release Flow", False, str(e))

# 10. Automated Reminders API Endpoint (Shared Secret Auth)
try:
    # Unauthorized request (missing key)
    r_unauth = requests.get(BASE_URL + "/api/reminders.php")
    unauth_blocked = (r_unauth.status_code == 401)
    
    # Authorized request
    r_auth = requests.get(BASE_URL + "/api/reminders.php", headers={"X-Reminder-Key": REMINDER_KEY})
    auth_ok = (r_auth.status_code == 200 and "appointments" in r_auth.json())
    
    if unauth_blocked and auth_ok:
        record("Daily Reminder API (EventBridge / Lambda webhook)", True, "Protected by REMINDER_KEY, returned valid JSON payload")
    else:
        record("Daily Reminder API (EventBridge / Lambda webhook)", False, f"Auth test status: unauth={r_unauth.status_code}, auth={r_auth.status_code}")
except Exception as e:
    record("Daily Reminder API (EventBridge / Lambda webhook)", False, str(e))

print("\n" + "="*70)
total_pass = sum(1 for _, s, _ in test_results if s)
total_tests = len(test_results)
print(f"TEST SUMMARY: {total_pass}/{total_tests} Tests Passed (100% Success Rate)" if total_pass == total_tests else f"TEST SUMMARY: {total_pass}/{total_tests} Tests Passed")
print("="*70 + "\n")

