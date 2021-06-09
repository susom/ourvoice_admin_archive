# Ourvoice Data Portal
![TravisBuildStatus](https://api.travis-ci.com/susom/ourvoice_admin.svg?token=AJR43pg9jY6pqpktBy24&branch=master&status=unknown)

<img alt="PHP" src="https://img.shields.io/badge/php-%23777BB4.svg?style=for-the-badge&logo=php&logoColor=white"/>
<img alt="Google Cloud" src="https://img.shields.io/badge/GoogleCloud-%234285F4.svg?style=for-the-badge&logo=google-cloud&logoColor=white"/>

<img alt="TravisCI" src="https://img.shields.io/badge/travisci-%232B2F33.svg?style=for-the-badge&logo=travis&logoColor=white"/>

#

## Detail
The Ourvoice Data portal 

The ourvoice data portal currently runs in parallel to the django flask dashboard. 

## Local Development
This repository contains a docker-compose file pre-configured for local deployment. After obtaining the secrets folder from one of the maintainers of this project, simply run `docker-compose up` and navigate to `localhost:8201`

## Deployment

The ourvoice data portal is currently integrated with TravisCI & deployed via Cloud Run automatically.

Any commits to the travis branch will be built to configuration & deployed as a primary service on cloud run.

Secrets are injected at deploy time and stored within GCloud.

