import africastalking

# Sandbox credentials
username = "sandbox"  # or your live username
api_key = "atsk_f4a9fd8ce8e57cbba4c3df1cc06940af90d3ba5ecf7f959e731d1a0463f23816ed8f8b6d"  # replace with your actual key

# Initialize the SDK
africastalking.initialize(username, api_key)

# Get the SMS service
sms = africastalking.SMS

# Define your message and recipients
recipients = ["+250726661885"]  # use your test or real phone number
message = "Hello from Vaultwise using Africa's Talking!"

# Send the SMS
try:
    response = sms.send(message, recipients)
    print(response)
except Exception as e:
    print(f"Error: {e}")
