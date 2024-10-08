#!/bin/bash

aws --endpoint-url=http://${LOCALSTACK_HOST}:${LOCALSTACK_PORT} ses verify-email-identity --region ${AWS_DEFAULT_REGION} --email-address ${MAIL_FROM_ADDRESS}
aws --endpoint-url=http://${LOCALSTACK_HOST}:${LOCALSTACK_PORT} ses list-identities --region ${AWS_DEFAULT_REGION} --identity-type EmailAddress