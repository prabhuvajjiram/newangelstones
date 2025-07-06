import boto3
import os
import json
import csv
from datetime import datetime, timedelta
from io import StringIO
from botocore.client import Config

# Load environment
BUCKET_NAME = os.environ.get('BUCKET_NAME')
EMAIL_FROM = os.environ.get('EMAIL_FROM')
EMAIL_TO = os.environ.get('EMAIL_TO')
REGION = os.environ.get('REGION', 'us-east-2')
LOG_GROUP = '/aws/sns/SES_Bounce_Notifications'  # Adjust if your log group is different

# Initialize AWS clients
s3 = boto3.client('s3', region_name=REGION, config=Config(signature_version='s3v4'))
ses = boto3.client('ses', region_name=REGION)
logs = boto3.client('logs', region_name=REGION)

def query_log_events(search_string):
    end_time = int(datetime.now().timestamp() * 1000)
    start_time = int((datetime.now() - timedelta(days=1)).timestamp() * 1000)
    try:
        response = logs.filter_log_events(
            logGroupName=LOG_GROUP,
            startTime=start_time,
            endTime=end_time,
            filterPattern=search_string
        )
        return [json.loads(event['message']) for event in response['events']]
    except logs.exceptions.ResourceNotFoundException:
        return []

def generate_csv_and_upload(events, event_type):
    if not events:
        return None

    now = datetime.now().strftime('%Y-%m-%d')
    filename = f"{event_type}_report_{now}.csv"
    print(f"Generating report: {filename} in bucket {BUCKET_NAME}")

    csv_buffer = StringIO()
    writer = csv.writer(csv_buffer)
    writer.writerow(['Email', 'Timestamp', 'Source'])

    for e in events:
        msg = e.get('mail', {})
        source = msg.get('source', '')
        timestamp = msg.get('timestamp', '')
        if event_type == 'Bounce':
            recipients = e.get('bounce', {}).get('bouncedRecipients', [])
        else:
            recipients = e.get('complaint', {}).get('complainedRecipients', [])
        for r in recipients:
            email = r.get('emailAddress', '')
            writer.writerow([email, timestamp, source])

    # Upload to S3
    s3.put_object(
        Bucket=BUCKET_NAME,
        Key=filename,
        Body=csv_buffer.getvalue(),
        ContentType='text/csv',
        CacheControl='no-cache'
    )

    # Generate presigned URL
    url = s3.generate_presigned_url(
        ClientMethod='get_object',
        Params={'Bucket': BUCKET_NAME, 'Key': filename},
        ExpiresIn=86400,  # 24 hours
        HttpMethod='GET'
    )

    print(f"{event_type} presigned URL: {url}")
    return url

def lambda_handler(event, context):
    print("EVENT:", event)

    # Manual test mode
    if event.get("manual"):
        print("Manual test: generating mock bounce/complaint")
        now = datetime.utcnow().isoformat()
        bounce_events = [{
            "mail": {"timestamp": now, "source": "marketing@email.theangelstones.com"},
            "bounce": {"bouncedRecipients": [{"emailAddress": "bounced@example.com"}]}
        }]
        complaint_events = [{
            "mail": {"timestamp": now, "source": "marketing@email.theangelstones.com"},
            "complaint": {"complainedRecipients": [{"emailAddress": "complaint@example.com"}]}
        }]
    else:
        bounce_events = query_log_events('"notificationType": "Bounce"')
        complaint_events = query_log_events('"notificationType": "Complaint"')

    bounce_url = generate_csv_and_upload(bounce_events, "Bounce")
    complaint_url = generate_csv_and_upload(complaint_events, "Complaint")

    body_lines = []
    if bounce_url:
        body_lines.append(f"Bounce Report: {bounce_url}")
    if complaint_url:
        body_lines.append(f"Complaint Report: {complaint_url}")
    if not body_lines:
        body_lines.append("No bounce or complaint events in the last 24 hours.")

    # Debug prints
    print("REGION:", REGION)
    print("BUCKET_NAME:", BUCKET_NAME)

    # Send report email
    ses.send_email(
        Source=EMAIL_FROM,
        Destination={'ToAddresses': [EMAIL_TO]},
        Message={
            'Subject': {'Data': 'Daily SES Bounce/Complaint Report'},
            'Body': {'Text': {'Data': "\n".join(body_lines)}}
        }
    )

    return {
        'statusCode': 200,
        'body': 'Email sent with report links.' if bounce_url or complaint_url else 'No events. Email skipped.'
    }