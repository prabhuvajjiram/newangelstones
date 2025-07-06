import json
import requests

def lambda_handler(event, context):
    print("Received event:", json.dumps(event))

    try:
        # Parse SES message from SNS or direct input
        if "Records" in event:
            sns_message = event["Records"][0]["Sns"]["Message"]
            ses_data = json.loads(sns_message)
        else:
            ses_data = event

        print("Parsed SES data:", json.dumps(ses_data))

        # Check type of notification
        notification_type = ses_data.get("notificationType")
        if notification_type not in ["Bounce", "Complaint"]:
            return {"statusCode": 200, "body": "Not a bounce or complaint notification"}

        # Set tag and reason based on type
        if notification_type == "Bounce":
            email = ses_data["bounce"]["bouncedRecipients"][0]["emailAddress"]
            tag_to_apply = "auto-bounced"
            reason = 3
        else:
            email = ses_data["complaint"]["complainedRecipients"][0]["emailAddress"]
            tag_to_apply = "spam-complaint"
            reason = 4

        print("Affected email:", email)

        # Step 1: Get Mautic OAuth token
        token_url = os.environ.get('token_url')
        client_id = os.environ.get('client_id')
        client_secret = os.environ.get('client_secret')
        token_data = {
            "client_id": client_id,
            "client_secret": client_secret,
            "grant_type": "client_credentials"
        }

        token_response = requests.post(token_url, data=token_data)
        token_response.raise_for_status()
        token = token_response.json()["access_token"]
        headers = {
            "Authorization": f"Bearer {token}",
            "Content-Type": "application/json"
        }
        print("Access token obtained")

        # Step 2: Ensure tag exists (optional)
        tag_list_url = "https://theangelstones.com/mautic/api/tags"
        tag_list_response = requests.get(tag_list_url, headers=headers)
        tag_list_response.raise_for_status()
        tag_list = tag_list_response.json().get("tags", {})

        if not any(tag.get("tag") == tag_to_apply for tag in tag_list.values()):
            print(f"Tag '{tag_to_apply}' not found, creating it.")
            tag_create_url = "https://theangelstones.com/mautic/api/tags/new"
            tag_create_resp = requests.post(
                tag_create_url,
                json={"tag": tag_to_apply, "isPublished": True},
                headers=headers
            )
            print("Tag create response:", tag_create_resp.status_code, tag_create_resp.text)

        # Step 3: Lookup contact by email
        contact_lookup_url = f"https://theangelstones.com/mautic/api/contacts?search={email}"
        lookup_response = requests.get(contact_lookup_url, headers=headers)
        lookup_response.raise_for_status()

        contacts = lookup_response.json().get("contacts", {})
        if not contacts:
            return {"statusCode": 404, "body": f"No contact found for {email}"}
        contact_id = next(iter(contacts))
        print("Contact ID:", contact_id)

        # Step 4: Add tag using PATCH (Mautic 6.x requirement)
        tag_edit_url = f"https://theangelstones.com/mautic/api/contacts/{contact_id}/edit"
        tag_payload = {
            "tags": [tag_to_apply]
        }
        tag_response = requests.patch(tag_edit_url, headers=headers, json=tag_payload)
        print(f"Tag PATCH response: {tag_response.status_code} - {tag_response.text}")

        # Step 5: Mark as DNC
        dnc_url = f"https://theangelstones.com/mautic/api/contacts/{contact_id}/dnc/email/add"
        dnc_payload = {
            "channel": "email",
            "reason": reason,
            "comments": f"Marked via SES {notification_type.lower()}"
        }
        dnc_response = requests.post(dnc_url, json=dnc_payload, headers=headers)
        print("Mautic DNC Response:", dnc_response.status_code, "-", dnc_response.text)

        return {
            "statusCode": 200,
            "body": f"Tagged and marked contact {contact_id} ({email}) as DNC"
        }

    except Exception as e:
        print("Unexpected error:", str(e))
        return {
            "statusCode": 500,
            "body": f"Unexpected error: {str(e)}"
        }