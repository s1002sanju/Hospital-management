"""AWS Lambda: daily appointment reminders (EventBridge schedule -> Lambda -> SES).
Env vars: APP_URL (https://your-env.elasticbeanstalk.com), REMINDER_KEY, SES_SENDER
Runtime: Python 3.12. Role needs ses:SendEmail + CloudWatch Logs (AWSLambdaBasicExecutionRole)."""
import json, os, urllib.request
import boto3

APP_URL = os.environ["APP_URL"].rstrip("/")
KEY = os.environ["REMINDER_KEY"]
SENDER = os.environ["SES_SENDER"]
ses = boto3.client("ses")


def _call(method, body=None):
    req = urllib.request.Request(
        f"{APP_URL}/api/reminders.php", method=method,
        data=json.dumps(body).encode() if body else None,
        headers={"X-Reminder-Key": KEY, "Content-Type": "application/json"})
    with urllib.request.urlopen(req, timeout=20) as r:
        return json.loads(r.read())


def lambda_handler(event, context):
    appts = _call("GET").get("appointments", [])
    sent = []
    for a in appts:
        try:
            ses.send_email(
                Source=SENDER, Destination={"ToAddresses": [a["email"]]},
                Message={"Subject": {"Data": "Appointment reminder for tomorrow"},
                         "Body": {"Text": {"Data": f"Hello {a['name']},\nReminder: you have an appointment with "
                                                   f"{a['doctor']} on {a['slot_date']} at {a['start_time'][:5]}."}}})
            sent.append(a["id"])
        except Exception as ex:           # one failure must not stop the batch
            print("send failed", a["id"], ex)
    if sent:
        _call("POST", {"sent_ids": sent})
    print(f"reminders sent: {len(sent)}/{len(appts)}")
    return {"sent": len(sent), "total": len(appts)}
